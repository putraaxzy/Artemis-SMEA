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
        $apiKey = $request->header('x-api-key');
        $expectedKey = env('BOT_PASSWORD');

        if (!$apiKey) {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'API Key diperlukan'
            ], 401);
        }

        if ($apiKey !== $expectedKey) {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'API Key tidak valid'
            ], 403);
        }

        return $next($request);
    }
}
