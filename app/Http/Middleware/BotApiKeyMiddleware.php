<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BotApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $botPassword = $request->header('X-Bot-Password');
        $expectedPassword = env('BOT_PASSWORD');

        if (!$botPassword) {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Password diperlukan'
            ], 401);
        }

        if ($botPassword !== $expectedPassword) {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Password tidak valid'
            ], 403);
        }

        return $next($request);
    }
}
