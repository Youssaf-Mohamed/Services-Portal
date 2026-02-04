<?php

namespace App\Exports;

use App\Models\TransportSubscriptionRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TransportBookingsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $status;
    protected $routeId;
    protected $fromDate;
    protected $toDate;

    public function __construct($status = null, $routeId = null, $fromDate = null, $toDate = null)
    {
        $this->status = $status;
        $this->routeId = $routeId;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function collection()
    {
        $query = TransportSubscriptionRequest::with(['user', 'route', 'slot', 'paymentMethod', 'subscription'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->routeId) {
            $query->where('route_id', $this->routeId);
        }

        if ($this->fromDate) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            '#',
            'Student Name',
            'Academic ID',
            'Email',
            'Phone',
            'Route Name',
            'Route Direction',
            'Slot Time',
            'Slot Day',
            'Selected Days',
            'Plan Type',
            'Amount (EGP)',
            'Payment Method',
            'Payment Status',
            'Request Status',
            'Subscription Status',
            'Start Date',
            'End Date',
            'Submitted At',
            'Approved At',
        ];
    }

    public function map($request): array
    {
        $selectedDays = $request->selected_days 
            ? implode(', ', array_map('ucfirst', $request->selected_days))
            : 'N/A';

        $slotTime = $request->slot ? date('h:i A', strtotime($request->slot->time)) : 'N/A';
        $slotDay = $request->slot ? $this->getDayName($request->slot->day_of_week) : 'N/A';

        return [
            $request->id,
            $request->user->name ?? 'N/A',
            $request->user->academic_id ?? 'N/A',
            $request->user->email ?? 'N/A',
            $request->user->phone ?? 'N/A',
            $request->route->name_en ?? 'N/A',
            $request->route->direction ?? 'Round Trip',
            $slotTime,
            $slotDay,
            $selectedDays,
            ucfirst($request->plan_type),
            number_format($request->amount_expected, 2),
            $request->paymentMethod->name_en ?? 'N/A',
            ucfirst($request->payment_status),
            ucfirst($request->status),
            $request->subscription ? ucfirst($request->subscription->status) : 'N/A',
            $request->subscription && $request->subscription->starts_at 
                ? $request->subscription->starts_at->format('Y-m-d') 
                : 'N/A',
            $request->subscription && $request->subscription->ends_at 
                ? $request->subscription->ends_at->format('Y-m-d') 
                : 'N/A',
            $request->created_at->format('Y-m-d H:i'),
            $request->approved_at ? $request->approved_at->format('Y-m-d H:i') : 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];
    }

    protected function getDayName($dayNumber)
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return $days[$dayNumber] ?? 'N/A';
    }
}
