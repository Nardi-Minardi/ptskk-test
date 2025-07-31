<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Resources\UserResource;
use Illuminate\Support\Str;

class AuthController extends Controller
{
  public function register(Request $request)
  {
    try {
      $validated = Validator::make(
        $request->all(),
        [
          "first_name" => 'required',
          "last_name" => 'required',
          "email" => 'required|email|unique:users,email',
          "password" => 'required'
        ],
        [
          'first_name.required' => 'First name is required',
          'last_name.required' => 'Last name is required',
          'email.required' => 'Email is required',
          'email.email' => 'Email must be a valid email address',
          'email.unique' => 'Email has already been taken',
          'password.required' => 'Password is required',
        ]
      );

      if ($validated->fails()) {
        return response()->json([
          'success' => false,
          'message' => 'Validation failed',
          'errors' => $validated->errors()
        ], 422);
      }

      $user_id = Str::uuid();

      $user = new User();
      $user->id = $user_id;
      $user->name = $request->input('first_name') . ' ' . $request->input('last_name');
      $user->email = $request->input('email');
      $user->password = bcrypt($request->input('password'));
      $user->save();

      return response()->json([
        'success' => true,
        'message' => 'Registration successful, please verify your email',
        'data' => new UserResource($user)
      ], 201);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'An error occurred during registration: ' . $e->getMessage(),
      ], 500);
    }
  }

  public function login(Request $request)
  {
    try {
      $validated = Validator::make(
        $request->all(),
        [
          "email" => 'required|email|exists:users,email',
          "password" => 'required',
          "client_time_at_login" => 'required|date_format:Y-m-d H:i:s',
        ],
        [
          'email.required' => 'email is required',
          'email.email' => 'Email must be a valid email address',
          'email.exists' => 'Email does not exist in our records',
          'password.required' => 'Password is required',
          'client_time_at_login.required' => 'Client time at login is required',
          'client_time_at_login.date_format' => 'Client time at login must be in the format Y-m-d H:i:s',
          'client_time_at_login.date' => 'Client time at login must be a valid date',
        ]
      );

      if ($validated->fails()) {
        return response()->json([
          'success' => false,
          'message' => 'Validation failed',
          'errors' => $validated->errors()
        ], 422);
      }


      $credentials = $request->only('email', 'password');
      if (!Auth::attempt($credentials)) {
        return response()->json([
          'success' => false,
          'message' => 'Invalid credentials',
        ], 400);
      }

      $user = Auth::getUser();

      // Check if the user is role user and email_verified_at is null
      if ($user->email_verified_at === null) {
        return response()->json([
          'success' => false,
          'message' => "Please verify your email before login",
        ], 400);
      }

      $token = $user->createToken('sanctum')->plainTextToken;
      
      $generated_expired_token = $this->generateExpiredToken($request->input('client_time_at_login'));
      $expired_at = $generated_expired_token['expired_at'];
      $is_expired = $generated_expired_token['is_expired'];
      $current_server_time = $generated_expired_token['current_server_time'];

      return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'user' => new UserResource($user),
        'token' => $token,
        'expired_at' => $expired_at->toDateTimeString(),
        'is_expired' => $is_expired,
        'server_time' => $current_server_time->toDateTimeString(),
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'An error occurred during login' . $e->getMessage(),
      ], 500);
    }
  }

  public function verifyEmail(Request $request)
  {
    try {
      $validated = Validator::make(
        $request->all(),
        [
          "email" => 'required|email|exists:users,email',
        ],
        [
          'email.required' => 'email is required',
          'email.email' => 'Email must be a valid email address',
          'email.exists' => 'Email does not exist in our records',
        ]
      );

      if ($validated->fails()) {
        return response()->json([
          'success' => false,
          'message' => 'Validation failed',
          'errors' => $validated->errors()
        ], 422);
      }

      $user = User::where('email', $request->email)->first();
      if (!$user) {
        return response()->json([
          'success' => false,
          'message' => 'User not found',
        ], 404);
      }

      if ($user->email_verified_at !== null) {
        return response()->json([
          'success' => false,
          'message' => 'Email already verified',
        ], 400);
      }

      $user->email_verified_at = now();
      $user->save();

      return response()->json([
        'success' => true,
        'message' => 'Your email has been successfully verified',
      ], 200);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'An error occurred during login' . $e->getMessage(),
      ], 500);
    }
  }

  public function logout(Request $request)
  {
    try {
      $user = Auth::user();
      if (!$user) {
        return response()->json([
          'success' => false,
          'message' => 'User not authenticated',
        ], 401);
      }

      $user->tokens()->delete();

      return response()->json([
        'success' => true,
        'message' => 'Logout successful',
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'An error occurred during logout: ' . $e->getMessage(),
      ], 500);
    }
  }

  public function refreshToken(Request $request)
  {
    try {
      $validated = Validator::make(
        $request->all(),
        [
          "client_time_at_login" => 'required|date_format:Y-m-d H:i:s',
        ],
        [
          'client_time_at_login.required' => 'Client time at login is required',
          'client_time_at_login.date_format' => 'Client time at login must be in the format Y-m-d H:i:s',
          'client_time_at_login.date' => 'Client time at login must be a valid date',
        ]
      );

      if ($validated->fails()) {
        return response()->json([
          'success' => false,
          'message' => 'Validation failed',
          'errors' => $validated->errors()
        ], 422);
      }

      $user = Auth::user();
      if (!$user) {
        return response()->json([
          'success' => false,
          'message' => 'User not authenticated',
        ], 401);
      }

      // Revoke all previous tokens
      $user->tokens()->delete();

      // Create a new token
      $token = $user->createToken('sanctum')->plainTextToken;

      $client_time_at_login = $request->input('client_time_at_login');
      $generated_expired_token = $this->generateExpiredToken($client_time_at_login);
      $expired_at = $generated_expired_token['expired_at'];
      $is_expired = $generated_expired_token['is_expired'];
      $current_server_time = $generated_expired_token['current_server_time'];

      return response()->json([
        'success' => true,
        'message' => 'Token refreshed successfully',
        'user' => new UserResource($user),
        'token' => $token,
        'expired_at' => $expired_at->toDateTimeString(),
        'is_expired' => $is_expired,
        'server_time' => $current_server_time->toDateTimeString(),
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'An error occurred during token refresh: ' . $e->getMessage(),
      ], 500);
    }
  }

  private function generateExpiredToken($client_time_at_login)
  {
    try {
      //1 hour expiration time
      $current_server_time = now();
      $client_time_at_login = Carbon::parse($client_time_at_login);

      // hitung selisih waktu antara server dan client
      $time_difference = $current_server_time->diffInSeconds($client_time_at_login);

      // untuk perhitungan expired_at
      $expired_at = $current_server_time->copy()->addSeconds(3600 + $time_difference);

      $is_expired = $expired_at->isPast();

      return [
        'expired_at' => $expired_at,
        'is_expired' => $is_expired,
        'current_server_time' => $current_server_time
      ];
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'An error occurred while generating expired token: ' . $e->getMessage(),
      ], 500);
    }
  }
}
