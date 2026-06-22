<?php

namespace App\Services;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Throwable;

class Service
{
    protected function logException(Throwable $exception, string $context = ''): void
    {
        Log::error('Service exception: ' . $context, [
            'message' => $exception->getMessage(),
            // 'file' => $exception->getFile(),
            // 'line' => $exception->getLine(),
            // 'trace' => $exception->getTraceAsString(),
        ]);
    }

    public function throwExceptionJson($message = 'حدث خطأ ما', $code = 500, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        throw new HttpResponseException(response()->json($response, $code));
    }

    public function ttimeNow()
    {
        return now()->addHours(3);
    }
}
