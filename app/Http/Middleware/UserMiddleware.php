<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class UserMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $authorizationHeader = $request->header('Authorization');

        if (strpos($authorizationHeader, 'Bearer ') !== 0) {
            return response()->json(['error' => 'Invalid Authorization header', 'code' => 401], 401);
        }

        $jwt = str_replace('Bearer ', '', $authorizationHeader);

        try {
            $decoded = JWT::decode($jwt, new Key(env('JWT_SECRET'), 'HS256'));
            $appId = '20';
            $urlAkses = env('BASE_URL_PORTAL')."akses/mine/{$appId}";

            $getakses = Http::withHeaders([
                'Authorization' => $authorizationHeader,
            ])->get($urlAkses);

            $akses = $getakses->json();

            // dd($akses);

            if (!isset($akses['data']) || $akses['data']['level_akses'] < 1) {
                return response()->json(['code' => 401, 'error' => 'Don`t have access for this feature'], 401);
            }

            if (Carbon::now()->timestamp >= $decoded->exp) {
                return response()->json(['code' => 401, 'error' => 'Token has expired'], 401);
            }

            $request->merge(['user_token' => $authorizationHeader, 'decoded' => $decoded]);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['code' => 401, 'message' => 'Invalid or expired token', 'err' => $e->getTrace()[0],'errMsg' => $e->getMessage(),], 401);
        }
    }
}
