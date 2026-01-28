<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth:sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            try {
                //dd($request->fullUrl());
                    DB::table('errorlog')->insert([
                        'message'       => $e->getMessage(),
                        'stack_trace'   => $e->getTraceAsString(),
                        'level'         => method_exists($e, 'getStatusCode') && $e->getStatusCode() < 500 ? 'warning' : 'error',
                        'url'           => $request->fullUrl() ?? '',
                        'route_name'    => $request->route()?->getName(),
                        'method'        => $request->method() ?? 'GET',
                        'request_data'  => json_encode($request->except(['password', 'password_confirmation', '_token'])),
                        'user_id'       => Auth::check() ? Auth::id() : null,
                        'code'          => (string)$e->getCode(), // حتماً رشته چون جدول varchar هست
                        'file'          => $e->getFile() ?? '',
                        'line'          => $e->getLine() ?? 0,
                        'fullurl'       => $_SERVER["REQUEST_URI"] ?? '',
                        'ip'            => $request->ip() ?? '',
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
            } catch (\Exception $inner) {
                // جلوگیری از خطاهای تودرتو در زمان لاگ کردن
            }

            try {
                $level = method_exists($e, 'getStatusCode') && $e->getStatusCode() < 500 ? 'warning' : 'error';
                $userid = Auth::check() ? Auth::id() : "";
                $line = $e->getLine() ?? 0;
                $ip = $request->ip() ?? '';
                $text =
                    "⚠️ Laravel Error Alert\n".
                    "Level:{$level}\n".
                    "Message: {$e->getMessage()}\n".
                    "File: {$e->getFile()}:{$e->getLine()}\n".
                    "URL: ".$request->fullUrl()."\n".
                    "User_id: {$userid}\n".
                    "Line: {$line}\n".
                    "IP: {$ip}\n".
                    "Time: ".now();

                $token   = env('BALE_BOT_TOKEN');
                $chat_id = env('BALE_CHAT_ID');

                $url = "https://tapi.bale.ai/bot{$token}/sendMessage";

                file_get_contents($url . "?" . http_build_query([
                    'chat_id' => $chat_id,
                    'text'    => $text
                ]));

            } catch (\Throwable $telegramError) {
                // ignore
            }
            return null; // اجازه به لاراول برای پردازش عادی خطا
        });
    })->create();
