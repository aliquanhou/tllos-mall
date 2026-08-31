<?php

namespace App\Core\Traits;

trait ApiResponse
{
    protected function success($data = null, string $message = 'success', int $code = 200)
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => time(),
        ], $code);
    }

    protected function error(string $message = 'error', int $code = 400, $data = null)
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => time(),
        ], $code);
    }

    protected function paginated($data, string $message = 'success')
    {
        return response()->json([
            'code' => 200,
            'message' => $message,
            'data' => $data->items(),
            'pagination' => [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
            ],
            'timestamp' => time(),
        ]);
    }
}
