<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $routeName = $request->route()->getName();

        $permissions = [
            'dashboard.index' => 'dashboard',
            'customer.*' => 'master.customer',
            'draft-customer.*' => 'master.draft-customer',
            'truck.*' => 'master.truck',
            'truck-type.*' => 'master.truck-type',
            'subcon.*' => 'master.subcon',
            'driver.*' => 'master.driver',
            'location.*' => 'master.location',
            'unit-param.*' => 'master.unit-param',
            'role.*' => 'role-management',
            'user.*' => 'user-management',
            'calendar.*' => 'calendar',
            'consignment-order.*' => 'truck-planning',
            'truck-summary.*' => 'truck-summary',
            'driver-holidays.*' => 'driver-leave',
            'activity-log.*' => 'activity-log',
        ];

        foreach ($permissions as $pattern => $permission) {
            if ($request->routeIs($pattern)) {
                if (!$user || !$user->hasPermission($permission)) {
                    return response()->view('errors.no-permission');
                }
            }
        }

        return $next($request);
    }
}
