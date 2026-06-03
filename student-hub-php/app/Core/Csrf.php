<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    /**
     * Generate secure CSRF token and store it in session
     */
    public static function generateToken(string $formKey = 'global'): string
    {
        Session::start();
        $tokens = Session::get('csrf_tokens');
        if (!is_array($tokens)) {
            $tokens = [];
        }

        if (empty($tokens[$formKey])) {
            $token = bin2hex(random_bytes(32));
            $tokens[$formKey] = $token;
            Session::set('csrf_tokens', $tokens);
            Session::set('csrf_token', $token);
        }

        return $tokens[$formKey];
    }

    /**
     * Verify whether a provided token matches the session token to protect POST actions
     */
    public static function verifyToken(?string $token, string $formKey = 'global'): bool
    {
        Session::start();
        $tokens = Session::get('csrf_tokens');
        $sessionToken = is_array($tokens) ? ($tokens[$formKey] ?? null) : Session::get('csrf_token');

        if ($sessionToken === null || $token === null) {
            return false;
        }

        $valid = hash_equals($sessionToken, $token);
        if ($valid) {
            if (is_array($tokens)) {
                unset($tokens[$formKey]);
                Session::set('csrf_tokens', $tokens);
            }
            Session::remove('csrf_token');
            self::generateToken($formKey);
        }

        return $valid;
    }
}
