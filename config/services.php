<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | Almacenamiento de ficheros (Google Drive). En local/tests DRIVE_DRIVER=fake.
    | Para producción, DRIVE_DRIVER=google y GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON debe
    | apuntar a un fichero JSON de credenciales o contener el JSON en crudo.
    */
    'drive' => [
        'driver' => env('DRIVE_DRIVER', 'fake'),
        'service_account_json' => env('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON'),
        'root_folder_id' => env('GOOGLE_DRIVE_ROOT_FOLDER_ID'), // Antiguo genérico
        'secretary_root_folder_id' => env('GOOGLE_DRIVE_SECRETARY_ROOT_FOLDER_ID'), // ID de "SECRETARÍA DE GRUPO"
    ],

];
