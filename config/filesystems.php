<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'), // standar laravel
            'throw'  => false,
        ],

        // Disk publik untuk file yang akan diakses WAHA lewat URL
        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            // gunakan ASSET_URL kalau ada, fallback ke APP_URL, lalu tambahkan /storage
            'url'        => env('ASSET_URL', env('APP_URL')) .'/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | PERBAIKAN: Konfigurasi S3 diberi komentar untuk menghindari error.
        | Hapus komentar jika Anda ingin menggunakannya dan pastikan semua
        | variabel environment (AWS_...) sudah diatur di file .env Anda.
        |--------------------------------------------------------------------------
        */
        // 's3' => [
        //     'driver' => 's3',
        //     'key' => env('AWS_ACCESS_KEY_ID'),
        //     'secret' => env('AWS_SECRET_ACCESS_KEY'),
        //     'region' => env('AWS_DEFAULT_REGION'),
        //     'bucket' => env('AWS_BUCKET'),
        //     'url' => env('AWS_URL'),
        //     'endpoint' => env('AWS_ENDPOINT'),
        //     'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        //     'throw' => false,
        //     'report' => false,
        // ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        // SYMLINK WAJIB untuk expose storage publik:
        public_path('storage') => storage_path('app/public'),
        // PERBAIKAN: Link ini akan membuat public/uploads -> storage/app/public/uploads
        public_path('uploads') => storage_path('app/public/uploads'),
    ],

];
