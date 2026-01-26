<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
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
        // Global middleware for permission checking
        Route::middlewareGroup('checkPermission', [
            function (Request $request, $next) {
                // User must be logged in
                if (!Auth::check()) {
                    return redirect()->route('login');
                }

                $user = Auth::user();

                // Example: define route-permission mapping
                $permissionsMap = [
                    'dashboard.*' => 'dashboard',
                    'customer.*'  => 'master.customer',
                    'truck.*'     => 'master.truck',
                    'user.*'      => 'user-management',
                    'role.*'      => 'role-management',
                    // add more mappings as needed
                ];

                $currentRoute = $request->route()->getName();

                foreach ($permissionsMap as $pattern => $permission) {
                    if ($currentRoute && fnmatch($pattern, $currentRoute)) {
                        if (!$user->hasPermission($permission)) {
                            return response()->view('errors.no-permission'); // create this blade
                        }
                    }
                }

                return $next($request);
            }
        ]);
    }
}
