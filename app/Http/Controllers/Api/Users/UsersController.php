<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Resources\UserResource;
use Illuminate\Support\Str;

class UsersController extends Controller
{
  public function profile(Request $request)
  {
    try {
      $user = User::where('id', Auth::id())->first();
      if (!$user) {
        return response()->json([
          'success' => false,
          'message' => 'User not found',
        ], 404);
      }

      return response()->json([
        'success' => true,
        'data' => new UserResource($user)
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'An error occurred while fetching the profile' . $e->getMessage(),
      ], 500);
    }
  }
}
