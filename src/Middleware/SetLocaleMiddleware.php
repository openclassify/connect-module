<?php namespace Visiosoft\ConnectModule\Middleware;

class SetLocaleMiddleware
{
    public function handle($request, $next)
    {
        if ($request->user()->locale){
           $request->setLocale($request->user()->locale);
        }
        return $next($request);
    }
}