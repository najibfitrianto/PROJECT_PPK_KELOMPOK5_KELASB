<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * SRS-007: Panel admin hanya bisa diakses saat role = admin.
 *
 * Middleware ini dipasang pada grup route 'admin.*' di routes/web.php.
 * Jika user yang login bukan admin (atau belum login), request akan
 * dihentikan dengan 403 sebelum sampai ke UserManagementController.
 */
class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return $next($request);
    }
}
