<?php

namespace App\Traits;

trait ApiResponses
{
    /**
     * Respuesta exitosa
     */
    protected function successResponse($data, $message = null, $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Respuesta de error
     */
    protected function errorResponse($message, $code = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Respuesta de recurso creado
     */
    protected function createdResponse($data, $message = 'Recurso creado exitosamente')
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Respuesta de recurso no encontrado
     */
    protected function notFoundResponse($message = 'Recurso no encontrado')
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Respuesta de validación fallida
     */
    protected function validationErrorResponse($errors)
    {
        return $this->errorResponse('Error de validación', 422, $errors);
    }
}