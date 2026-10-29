<?php

namespace App\Http\Middleware;

use App\Models\Package;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->isApproved() || $request->routeIs('account.pending')) {
            return $next($request);
        }

        // Someone who signed up through a package may only pay for that package and see their orders.
        if ($user->canPayForSignupPackage()) {
            $package = $request->route('package');

            // Depending on when route binding runs, this is either the Package or the slug from the URL.
            $packageId = $package instanceof Package
                ? $package->id
                : (is_string($package) ? Package::query()->where('slug', $package)->value('id') : null);

            $isOwnPackageCheckout = $request->routeIs('packages.checkout') && $packageId === $user->signup_package_id;

            if ($isOwnPackageCheckout || $request->routeIs('orders.show', 'orders.invoice')) {
                return $next($request);
            }

            return redirect()->route('packages.checkout', $user->signupPackage);
        }

        // They have paid for their package but are not approved yet, so they can still see that order and invoice.
        if ($user->hasPaidForSignupPackage() && $request->routeIs('orders.show', 'orders.invoice')) {
            return $next($request);
        }

        return redirect()->route('account.pending');
    }
}
