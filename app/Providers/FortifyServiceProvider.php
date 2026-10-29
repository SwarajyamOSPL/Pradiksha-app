<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()->where(Fortify::username(), $request->input(Fortify::username()))->first();

            if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }

            // Sales managers have their own login page (/sales/login), which marks its form with portal=sales.
            $usedSalesPortal = $request->input('portal') === 'sales';
            $isSalesManager = $user->role === UserRole::SalesManager;

            if ($usedSalesPortal && ! $isSalesManager) {
                return null;
            }

            if (! $usedSalesPortal && $isSalesManager) {
                throw ValidationException::withMessages([
                    Fortify::username() => __('Sales managers log in from the sales team login page: :url', ['url' => route('sales.login')]),
                ]);
            }

            // Only reveal the account state once the password is right, so this can't be used to probe for accounts.
            if ($user->status === UserStatus::Pending) {
                throw ValidationException::withMessages([
                    Fortify::username() => __('Your registration is awaiting admin approval. You will be able to log in once it is approved.'),
                ]);
            }

            if ($user->status === UserStatus::Suspended) {
                throw ValidationException::withMessages([
                    Fortify::username() => __('Your account has been suspended. Please contact support.'),
                ]);
            }

            return $user;
        });

        $this->app->singleton(LoginResponse::class, fn () => new class implements LoginResponse
        {
            /**
             * Send the user back to the page they were trying to reach, unless that page belongs to a
             * different role's area (for example a sales manager who earlier tried /admin).
             */
            public function toResponse($request)
            {
                if ($request->wantsJson()) {
                    return new JsonResponse('', 204);
                }

                $intended = (string) $request->session()->get('url.intended', '');
                $path = (string) parse_url($intended, PHP_URL_PATH);
                $role = $request->user()?->role;

                $forbiddenArea = ($role !== UserRole::Admin && str_starts_with($path, '/admin'))
                    || ($role !== UserRole::SalesManager && str_starts_with($path, '/sales'));

                if ($forbiddenArea) {
                    $request->session()->forget('url.intended');
                }

                return redirect()->intended(Fortify::redirects('login'));
            }
        });

        $this->app->singleton(RegisterResponse::class, fn () => new class implements RegisterResponse
        {
            /**
             * New franchise holders are not logged in: their request goes to the admin and they
             * can sign in once it is approved.
             */
            public function toResponse($request)
            {
                Auth::guard(config('fortify.guard'))->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('status', __('Thank you for registering! Your request has been sent to our admin for approval. You will be able to log in once it is approved.'));
            }
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        Fortify::registerView(fn () => view('pages::auth.register'));
        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email'));
        Fortify::resetPasswordView(fn () => view('pages::auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('pages::auth.forgot-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

    }
}
