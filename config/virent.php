<?php

return [

    'company_name' => env('VIRENT_COMPANY_NAME', config('app.name')),

    'company_legal_name' => env('VIRENT_COMPANY_LEGAL_NAME', 'ViRent Fleet Rentals Ltd.'),

    'company_address' => env('VIRENT_COMPANY_ADDRESS', '123 Rental Street, Dublin, Ireland'),

    'company_registration_number' => env('VIRENT_COMPANY_REGISTRATION_NUMBER', 'IE123456'),

    'company_email' => env('VIRENT_COMPANY_EMAIL', config('mail.from.address')),

];
