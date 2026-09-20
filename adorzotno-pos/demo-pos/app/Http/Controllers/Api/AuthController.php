<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseApiController
{
    /**
     * Register a new customer
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone ?? null,
            ]);

            $customer = Customer::query()->create([
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

            // Log the user in
            auth()->login($user);

            return $this->success([
                'user' => $user,
                'customer' => $customer,
            ], 'Registration successful', 201);
        } catch (\Exception $e) {
            return $this->serverError('Registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Login user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $credentials = $request->only('email', 'password');

        if (auth()->attempt($credentials)) {
            $user = auth()->user();
            return $this->success([
                'user' => $user->load('customer'),
            ], 'Login successful', 200);
        }

        return $this->unauthorized('Invalid credentials');
    }

    /**
     * Logout user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->success(null, 'Logout successful', 200);
    }

    /**
     * Get current user profile
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        if (!auth()->check()) {
            return $this->unauthorized('Not authenticated');
        }

        return $this->success([
            'user' => auth()->user()->load('customer'),
        ], 'Profile retrieved successfully', 200);
    }

    /**
     * Update user profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return $this->unauthorized('Not authenticated');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . auth()->id(),
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        try {
            $user = auth()->user();
            
            if ($request->name) {
                $user->name = $request->name;
            }
            if ($request->email) {
                $user->email = $request->email;
            }
            if ($request->phone) {
                $user->phone = $request->phone;
            }
            if ($request->password) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            $customer = $this->authenticatedCustomer(true);

            if ($customer !== null) {
                if ($request->name) {
                    $customer->name = $request->name;
                }
                if ($request->email) {
                    $customer->email = $request->email;
                }
                if ($request->phone) {
                    $customer->phone = $request->phone;
                }

                $customer->save();
            }

            return $this->success([
                'user' => $user->load('customer'),
            ], 'Profile updated successfully', 200);
        } catch (\Exception $e) {
            return $this->serverError('Profile update failed: ' . $e->getMessage());
        }
    }
}
