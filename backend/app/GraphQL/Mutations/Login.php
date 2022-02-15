<?php
 declare(strict_types=1);

 namespace App\GraphQL\Mutations;

 use App\Exceptions\InvalidCredentials;
 use App\Models\User;
 use Illuminate\Support\Facades\Auth;

class Login
{
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return \App\Models\User
     */
    public function __invoke($_, array $args): User
    {
        // Plain Laravel: Auth::guard()
        // Laravel Sanctum: Auth::guard(config('sanctum.guard', 'web'))
        $guard = Auth::guard('web');

        if (! $guard->attempt($args)) {
            throw new InvalidCredentials('Invalid credentials supplied');
        }

        /**
         * Since we successfully logged in, this can no longer be `null`.
         *
         * @var \App\Models\User $user
         */
        $user = $guard->user();

        return $user;
    }
}
