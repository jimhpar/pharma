<?php

namespace App\Http\Middleware;

use App\Support\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeBranchContext
{
    public function __construct(private BranchContext $branchContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $this->branchContext->ensureInitialized(auth()->user());
        }

        return $next($request);
    }
}
