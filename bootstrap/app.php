<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            'project/start',
            'tools/*',
            'project/*/generate',
            'project/*/revise',
            'project/*/save-chapter',
            'auth/google/quick',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e, \Illuminate\Http\Request $request) {
            if ($response->getStatusCode() === 419) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Sesi halaman telah diperbarui. Silakan muat ulang dan coba lagi.',
                    ], 419);
                }
                return redirect()->back()
                    ->withInput($request->except('password', 'password_confirmation', 'pin', '_token'))
                    ->with('info', 'Sesi halaman diperbarui otomatis. Silakan kirim ulang.');
            }
            return $response;
        });
    })->create();
