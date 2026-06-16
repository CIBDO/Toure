<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GED - Gestion Électronique des Documents
    |--------------------------------------------------------------------------
    */

    'max_file_size' => env('GED_MAX_FILE_SIZE_MB', 20) * 1024, // en Ko pour validation

    'allowed_mimes' => [
        'pdf',
        'jpg',
        'jpeg',
        'png',
        'doc',
        'docx',
        'xls',
        'xlsx',
    ],

    'allowed_mime_types' => [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],

    'documentable_types' => [
        'avis'       => \App\Models\Avis::class,
        'pv'         => \App\Models\Pv::class,
        'contrats'   => \App\Models\Contrat::class,
        'ordre_services' => \App\Models\OrdreService::class,
        'engagements' => \App\Models\Engagement::class,
        'payments'   => \App\Models\Paiement::class,
        'receptions' => \App\Models\Reception::class,
    ],

    'categories' => [
        'contrat_signe'   => 'Contrat signé',
        'pv_signe'        => 'PV signé',
        'pv_reception'    => 'PV de réception signé',
        'bordereau'       => 'Bordereau',
        'dao'             => 'DAO',
        'facture'         => 'Facture',
        'mandat'          => 'Mandat',
        'preuve_paiement'  => 'Preuve de paiement',
        'os_signe'        => 'OS signé',
        'piece_justificative' => 'Pièce justificative',
        'autres'          => 'Autres',
    ],

];
