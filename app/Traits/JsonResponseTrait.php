<?php

namespace App\Traits;

trait JsonResponseTrait
{
    /**
     * Fonction pour centraliser les réponses JSON.
     */
    protected function responseJson($success, $message, $data = null, $statusCode = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
}
