<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function successResponse(array $responseData)
    {
        return response([
            'success' => true,
            'data' => $responseData
        ], 200);
    }

    public function errorResponse(array $responseData, int $statusCode)
    {
        return response([
            'success' => false,
            'data' => $responseData
        ], $statusCode);
    }
}
