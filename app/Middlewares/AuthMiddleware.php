<?php
/**
 * Authentication Middleware
 * Ensures user is authenticated before accessing protected routes
 */

class AuthMiddleware implements Middleware
{
    public function handle($next)
    {
        if (!isAuthenticated()) {
            flash('error', 'Please login to access this page.');
            Response::redirect('/login');
        }

        return $next();
    }
}
