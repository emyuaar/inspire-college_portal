<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // [sheharyar test branch] Redirect ALL Microsoft Graph / SharePoint HTTP calls to the local mock. No real Microsoft.
        if (filter_var(env('MS_MOCK', false), FILTER_VALIDATE_BOOLEAN)) {
            $mockBase = parse_url(env('MS_MOCK_BASE', 'http://127.0.0.1:12112'));
            \Illuminate\Support\Facades\Http::globalRequestMiddleware(function ($request) use ($mockBase) {
                $uri = $request->getUri();
                if (in_array($uri->getHost(), ['login.microsoftonline.com', 'graph.microsoft.com'], true)) {
                    return $request->withUri(
                        $uri->withScheme($mockBase['scheme'] ?? 'http')
                            ->withHost($mockBase['host'] ?? '127.0.0.1')
                            ->withPort($mockBase['port'] ?? 12112)
                    );
                }
                return $request;
            });
        }
    }
}
