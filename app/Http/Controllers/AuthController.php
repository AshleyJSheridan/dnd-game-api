<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\Response;
use Tymon\JWTAuth\Facades\JWTFactory;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails())
            return response()->json($validator->errors()->toJson(), Response::HTTP_BAD_REQUEST);

        try {
            $user = User::create([
                'name' => $request->get('name'),
                'email' => $request->get('email'),
                'password' => Hash::make($request->get('password')),
            ]);

            return response()->json(['message' => 'User created successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not create user'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$accessToken = JWTAuth::attempt($credentials))
                return response()->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);

            $user = auth()->user();
            $accessToken = JWTAuth::claims(['role' => $user->role])->fromUser($user);

            $refreshTTL = config('jwt.refresh_ttl');
            $payload = JWTFactory::customClaims([
                'sub' => $user->getJWTIdentifier(),
                'iat' => now()->timestamp,
                'exp' => now()->addMinutes($refreshTTL)->timestamp,
                'type' => 'refresh'
            ])->make();
            $refreshToken = JWTAuth::encode($payload)->get();

            return response()->json([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getUser()
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate())
                return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Invalid token'], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(compact('user'));
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function deleteUser()
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate())
                return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);

            $localPart = substr($user->email, 0, strrpos($user->email, '@'));
            $domainPart = substr($user->email, strrpos($user->email, '@') - 1);

            // write over their email address destructively, allows character data to be preserved without any identifying info
            $user->email = md5($localPart) . $domainPart;
            $user->save();

            // invalidate the session
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json(['message' => 'Account successfully deleted. Sorry to see you go, good luck on your adventures.']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Invalid token'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function refreshToken(Request $request)
    {
        $refreshToken = $request->input('refresh_token');

        try {
            $payload = JWTAuth::setToken($refreshToken)->getPayload();

            if ($payload->get('type') !== 'refresh')
                return response()->json(['error' => 'Invalid token type'], Response::HTTP_UNAUTHORIZED);

            $userId = $payload->get('sub');
            $user = User::where('id', $userId);

            if (!$user)
                return response()->json(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);

            $accessToken = JWTAuth::claims(['role' => $user->role])->fromUser($user);
            $refreshTTL = config('jwt.refresh_ttl');
            $newRefreshPayload = JWTFactory::customClaims([
                'sub' => $user->getJWTIdentifier(),
                'iat' => now()->timestamp,
                'exp' => now()->addMinutes($refreshTTL)->timestamp,
                'type' => 'refresh'
            ])->make();

            $newRefreshToken = JWTAuth::encode($newRefreshPayload)->get();

            return response()->json([
                'access_token' => $accessToken,
                'refresh_token' => $newRefreshToken,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid refresh token'], Response::HTTP_UNAUTHORIZED);
        }
    }
}
