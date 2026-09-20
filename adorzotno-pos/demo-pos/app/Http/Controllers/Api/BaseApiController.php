<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;

class BaseApiController extends Controller
{
    /**
     * Success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public function success($data = null, $message = 'Success', $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $statusCode);
    }

    /**
     * Error response
     *
     * @param mixed $errors
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public function error($errors = null, $message = 'Error', $statusCode = 400): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $statusCode);
    }

    /**
     * Validation error response
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    public function validationError($errors = [], $message = 'Validation failed'): JsonResponse
    {
        return $this->error($errors, $message, 422);
    }

    /**
     * Not found response
     *
     * @param string $message
     * @return JsonResponse
     */
    public function notFound($message = 'Resource not found'): JsonResponse
    {
        return $this->error(null, $message, 404);
    }

    /**
     * Unauthorized response
     *
     * @param string $message
     * @return JsonResponse
     */
    public function unauthorized($message = 'Unauthorized'): JsonResponse
    {
        return $this->error(null, $message, 401);
    }

    /**
     * Server error response
     *
     * @param string $message
     * @return JsonResponse
     */
    public function serverError($message = 'Server error'): JsonResponse
    {
        return $this->error(null, $message, 500);
    }

    protected function authenticatedCustomer(bool $createIfMissing = false): ?Customer
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        $customer = $user->customer;

        if ($customer !== null || !$createIfMissing) {
            return $customer;
        }

        return Customer::query()->create([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'user_id' => $user->id,
            'customer_code' => 'CUS-' . str_pad((string) ((int) Customer::query()->max('id') + 1), 6, '0', STR_PAD_LEFT),
            'opening_balance' => 0,
            'current_due' => 0,
            'loyalty_points' => 0,
            'status' => 'active',
        ]);
    }
}
