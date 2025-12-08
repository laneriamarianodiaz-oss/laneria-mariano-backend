<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    */

    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
    'api_key' => env('CLOUDINARY_API_KEY'),
    'api_secret' => env('CLOUDINARY_API_SECRET'),
    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),
    'secure' => true,

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Upload Folder
    |--------------------------------------------------------------------------
    */
    'folder' => env('CLOUDINARY_FOLDER', 'laneria-mariano'),

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Notification URL
    |--------------------------------------------------------------------------
    */
    'notification_url' => env('CLOUDINARY_NOTIFICATION_URL'),
];