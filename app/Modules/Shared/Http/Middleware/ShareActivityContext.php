<?php

namespace App\Modules\Shared\Http\Middleware;

use App\Modules\Shared\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyimpan pelaku dan IP request ke Context (hidden, tidak ikut ke log file)
 * agar ActivityLogger tidak perlu bergantung pada Request.
 */
class ShareActivityContext
{
    public function handle(Request $request, Closure $next): Response
    {
        Context::addHidden(ActivityLogger::CONTEXT_IP, $request->ip());
        Context::addHidden(ActivityLogger::CONTEXT_USER_ID, $request->user()?->getAuthIdentifier());

        return $next($request);
    }
}
