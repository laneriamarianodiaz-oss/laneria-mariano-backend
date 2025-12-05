<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class BaseController extends Controller
{
    /**
     * Respuesta exitosa
     */
    public function successResponse($data, $message = null, $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Respuesta de creación exitosa
     */
    public function createdResponse($data, $message = 'Recurso creado exitosamente')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 201);
    }

    /**
     * Respuesta de error
     */
    public function errorResponse($message, $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $code);
    }

    /**
     * Respuesta de no encontrado
     */
    public function notFoundResponse($message = 'Recurso no encontrado')
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 404);
    }

    /**
     * Respuesta de error de validación
     */
    public function validationErrorResponse($errors)
    {
        return response()->json([
            'success' => false,
            'message' => 'Error de validación',
            'errors' => $errors
        ], 422);
    }
}