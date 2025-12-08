<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class TestController extends BaseController
{
    /**
     * 🧪 Probar configuración de Cloudinary
     */
    public function testCloudinary()
    {
        try {
            // Verificar que las variables de entorno existen
            $cloudName = config('cloudinary.cloud_name');
            $apiKey = config('cloudinary.api_key');
            
            if (!$cloudName || !$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variables de Cloudinary NO configuradas',
                    'cloud_name' => $cloudName ?? 'NULL',
                    'api_key' => $apiKey ?? 'NULL',
                    'env_vars' => [
                        'CLOUDINARY_CLOUD_NAME' => env('CLOUDINARY_CLOUD_NAME'),
                        'CLOUDINARY_API_KEY' => env('CLOUDINARY_API_KEY'),
                        'CLOUDINARY_API_SECRET' => env('CLOUDINARY_API_SECRET') ? 'CONFIGURADO' : 'NULL',
                    ]
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => '✅ Cloudinary está configurado correctamente',
                'cloud_name' => $cloudName,
                'api_key_length' => strlen($apiKey),
                'facade_exists' => class_exists('CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar Cloudinary',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}