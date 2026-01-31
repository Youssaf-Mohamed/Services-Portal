<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\TransportSubscriptionRequest;
use App\Models\BusRoute;
use App\Models\BusScheduleSlot;
use App\Models\PaymentMethod;
use App\Models\TransportSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransportSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $student;
    protected $route;
    protected $slot;
    protected $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles (assuming Spatie permissions or similar)
        $this->seed(); 

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        $this->route = BusRoute::create(['name_ar' => 'Test', 'name_en' => 'Test Route', 'price_one_way' => 50, 'active' => true]);
        $this->slot = BusScheduleSlot::create(['route_id' => $this->route->id, 'day_of_week' => 0, 'time' => '08:00', 'capacity' => 10, 'direction' => 'to_campus', 'active' => true]);
        $this->paymentMethod = PaymentMethod::create(['name' => 'Cash', 'account_number' => '123456', 'active' => true]);
        
        TransportSetting::create([
            'term_start_date' => now(),
            'term_end_date' => now()->addMonths(3),
            'registration_start_date' => now()->subDay(),
            'registration_end_date' => now()->addMonth(),
        ]);
    }

    public function test_cannot_approve_request_without_verified_payment()
    {
        $request = TransportSubscriptionRequest::create([
            'user_id' => $this->student->id,
            'route_id' => $this->route->id,
            'slot_id' => $this->slot->id,
            'payment_method_id' => $this->paymentMethod->id,
            'plan_type' => 'term',
            'direction' => 'to_campus',
            'status' => 'pending',
            'amount_expected' => 1000,
            'payment_status' => 'pending_verification',
            'paid_from_number' => '0501234567',
            'paid_at' => now(),
            'proof_path' => 'proofs/dummy.jpg',
            'pricing_snapshot' => ['price' => 1000, 'plan_type' => 'term'],
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/transport/requests/{$request->id}/approve", [
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Payment must be verified before approval. Current status: pending_verification']);
    }

    public function test_admin_can_verify_payment()
    {
        $request = TransportSubscriptionRequest::create([
            'user_id' => $this->student->id,
            'route_id' => $this->route->id,
            'slot_id' => $this->slot->id,
            'payment_method_id' => $this->paymentMethod->id,
            'plan_type' => 'term',
            'direction' => 'to_campus',
            'status' => 'pending',
            'amount_expected' => 1000,
            'payment_status' => 'pending_verification',
            'paid_from_number' => '0501234567',
            'paid_at' => now(),
            'proof_path' => 'proofs/dummy.jpg',
            'pricing_snapshot' => ['price' => 1000, 'plan_type' => 'term'],
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/transport/requests/{$request->id}/verify-payment");

        $response->assertStatus(200)
            ->assertJsonPath('data.payment_status', 'verified');
            
        $this->assertEquals('verified', $request->refresh()->payment_status);
        $this->assertNotNull($request->payment_verified_at);
    }

    public function test_student_cannot_verify_payment()
    {
        $request = TransportSubscriptionRequest::create([
            'user_id' => $this->student->id,
            'route_id' => $this->route->id,
            'slot_id' => $this->slot->id,
            'payment_method_id' => $this->paymentMethod->id,
            'plan_type' => 'term',
            'direction' => 'to_campus',
            'status' => 'pending',
            'amount_expected' => 1000,
            'payment_status' => 'pending_verification',
            'paid_from_number' => '0501234567',
            'paid_at' => now(),
            'proof_path' => 'proofs/dummy.jpg',
            'pricing_snapshot' => ['price' => 1000, 'plan_type' => 'term'],
        ]);

        // Role Middleware should catch this (403 or 401 depending on detailed handling, usually 403 Forbidden)
        $response = $this->actingAs($this->student)
            ->postJson("/api/admin/transport/requests/{$request->id}/verify-payment");

        $response->assertStatus(403);
    }
    
    public function test_proof_download_is_protected()
    {
        Storage::fake('proofs');
        $file = UploadedFile::fake()->image('proof.jpg');
        $path = $file->store('proofs', 'proofs');

        $request = TransportSubscriptionRequest::create([
            'user_id' => $this->student->id,
            'route_id' => $this->route->id,
            'slot_id' => $this->slot->id,
            'payment_method_id' => $this->paymentMethod->id,
            'plan_type' => 'term',
            'direction' => 'to_campus',
            'status' => 'pending',
            'amount_expected' => 1000,
            'payment_status' => 'pending_verification',
            'proof_path' => $path,
            'paid_from_number' => '0501234567',
            'paid_at' => now(),
            'pricing_snapshot' => ['price' => 1000, 'plan_type' => 'term'],
        ]);
        
        // Student cannot download via admin endpoint
        $response = $this->actingAs($this->student)
            ->getJson("/api/admin/transport/requests/{$request->id}/proof");
            
        $response->assertStatus(403);
        
        // Admin can download
        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/transport/requests/{$request->id}/proof");
            
        $response->assertStatus(200);
    }

    public function test_waitlist_logic_when_capacity_full()
    {
        // Set capacity to 0
        $this->slot->update(['capacity' => 0]);
        
        // Create request and verify payment
        $request = TransportSubscriptionRequest::create([
            'user_id' => $this->student->id,
            'route_id' => $this->route->id,
            'slot_id' => $this->slot->id,
            'payment_method_id' => $this->paymentMethod->id,
            'plan_type' => 'term',
            'direction' => 'to_campus',
            'status' => 'pending',
            'amount_expected' => 1000,
            'payment_status' => 'verified', // Pre-verified
            'paid_from_number' => '0501234567',
            'paid_at' => now(),
            'proof_path' => 'proofs/dummy.jpg',
            'pricing_snapshot' => ['price' => 1000, 'plan_type' => 'term'],
        ]);

        // Approve
        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/transport/requests/{$request->id}/approve", [
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(200);
        
        // Check if waitlisted
        // The API returns 'subscription_status' => 'waitlisted'
        $response->assertJsonPath('data.subscription_status', 'waitlisted');
        
        // Check database
        $this->assertDatabaseHas('transport_subscriptions', [
            'request_id' => $request->id,
            'status' => 'waitlisted',
        ]);
    }
}
