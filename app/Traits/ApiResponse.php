<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait ApiResponse
{
    protected function successResponse($data = null, $message = null, $code = 200)
    {
        return response()->json([
            'success' => (bool) true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function errorResponse($message = null, $code = 400)
    {
        return response()->json([
            'success' => (bool) false,
            'message' => $message,
            'data' => null,
        ], $code);
    }
}
