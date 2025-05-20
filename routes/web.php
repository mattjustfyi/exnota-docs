<?php

declare(strict_types=1);

// Route::view('/', 'welcome');
//
// Route::view('dashboard', 'dashboard')
//    ->middleware(['auth', 'verified'])
//    ->name('dashboard');
//
// Route::view('profile', 'profile')
//    ->middleware(['auth'])
//    ->name('profile');
//
// require __DIR__.'/auth.php';
//

// This is in web.php for now because it's simpler.
// `artisan install:api` will install Sanctum, which we don't need yet. And simply adding api.php doesn't
// seem to work.
use App\Exceptions\GitHubAuthenticationException;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

// Repository webhook route
\Illuminate\Support\Facades\Route::webhooks('api/webhook/repository');

// GitHub App webhook route
\Illuminate\Support\Facades\Route::webhooks('api/webhook/github-app', 'github-app');

\Illuminate\Support\Facades\Route::get('/auth/github/redirect', function () {
    return Socialite::driver('github')->redirect();
});

\Illuminate\Support\Facades\Route::get('/auth/github/callback', function () {
    // Log request details
    Log::info('GitHub OAuth callback received', [
        'headers' => request()->headers->all(),
        'query' => request()->query->all(),
        'ip' => request()->ip(),
        'userAgent' => request()->userAgent(),
    ]);

    /** @var \Laravel\Socialite\Contracts\User $githubUser * */
    $githubUser = Socialite::driver('github')->user();

    if ($githubUser->email === null) {
        Log::warning('GitHub authentication failed - no email', [
            'github_id' => $githubUser->id,
            'nickname' => $githubUser->nickname,
        ]);
        throw new GitHubAuthenticationException('No email found in GitHub account. An email is required for registration.');
    }

    /** @noinspection PhpPossiblePolymorphicInvocationInspection */
    $user = User::updateOrCreate([
        'github_id' => $githubUser->id,
    ], [
        'name' => $githubUser->name ?? $githubUser->nickname,
        'email' => $githubUser->email,
        'github_token' => $githubUser->token,
        // Tokens won't expire unless the feature is enabled: "User access tokens that expire are currently an optional feature and are subject to change."
        //
        // So this will be null for now.
        //
        // See: https://docs.github.com/en/apps/creating-github-apps/authenticating-with-a-github-app/generating-a-user-access-token-for-a-github-app#about-user-access-tokens
        'github_refresh_token' => $githubUser->refreshToken,    ]);

    Log::info('GitHub authentication successful', [
        'user_id' => $user->id,
        'github_id' => $githubUser->id,
        'email' => $user->email,
    ]);

    \Illuminate\Support\Facades\Auth::login($user);

    return redirect('/dashboard');
});

// GitHub App installation route
\Illuminate\Support\Facades\Route::get('/install/github/redirect', function () {
    $installUrl = config('services.github.app_install_url');

    return redirect($installUrl.'?state=test-123');
});
