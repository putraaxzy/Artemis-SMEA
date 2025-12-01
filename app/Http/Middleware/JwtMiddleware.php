<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'berhasil' => false,
                    'pesan' => 'User tidak ditemukan'
                ], 404);
            }
        } catch (JWTException $e) {
            $message = 'Token tidak ditemukan';

            if ($e instanceof TokenExpiredException) {
                $message = 'Token telah kadaluarsa';
            } elseif ($e instanceof TokenInvalidException) {
                $message = 'Token tidak valid';
            }

            return response()->json([
                'berhasil' => false,
                'pesan' => $message
            ], 401);
        }

        return $next($request);
    }
}
