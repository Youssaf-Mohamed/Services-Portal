<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Transport Module Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the transport module including proof upload constraints
    | and transport-related settings.
    |
    */

    // Proof upload constraints
    'proof_max_size' => env('PROOF_MAX_SIZE', 2048), // in kilobytes (2MB default)
    'proof_allowed_mimes' => explode(',', env('PROOF_ALLOWED_MIMES', 'image/jpeg,image/png,image/webp')),

    // Proof storage configuration
    'proof_disk' => 'proofs', // Uses the 'proofs' disk from filesystems.php

];
