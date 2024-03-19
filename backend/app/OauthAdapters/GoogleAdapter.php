<?php
declare(strict_types=1);

namespace App\OauthAdapters;

use Laravel\Socialite\Facades\Socialite;

class GoogleAdapter extends BaseAdapter
{
    public static function isEnabled(): bool
    {
        $client_id = config('services.google.client_id');
        $client_secret = config('services.google.client_secret');
        $redirect = config('services.google.redirect');

        return $client_id && $client_secret && $redirect;
    }

    public static function resolveDriver(): \Laravel\Socialite\Contracts\Provider
    {
        return Socialite::driver('google');
    }

    public static function getIcon(): string
    {
        return 'google';
    }

    public static function getLabel(): string
    {
        return 'google';
    }

    public static function getLoginUrl(): string
    {
        return static::getDriver()->redirect()->getTargetUrl();
    }

    public static function getUserFromToken(string $token): \Laravel\Socialite\Contracts\User
    {
        /**
         * @var \Laravel\Socialite\Two\GoogleProvider $driver
         */
        $driver = static::getDriver();

        $response = $driver->getAccessTokenResponse($token);

        return $driver->userFromToken($response['access_token']);
    }
}
