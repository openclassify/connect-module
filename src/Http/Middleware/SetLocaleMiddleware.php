<?php namespace Visiosoft\ConnectModule\Http\Middleware;

use Anomaly\Streams\Platform\Application\Application as App;
use Anomaly\Streams\Platform\Support\Locale;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

class SetLocaleMiddleware
{
    protected $app;

    protected $locale;

    protected $redirect;

    protected $application;

    public function __construct(
        App $app,
        Locale $locale,
        Redirector $redirect,
        Application $application
    ) {
        $this->app         = $app;
        $this->locale      = $locale;
        $this->redirect    = $redirect;
        $this->application = $application;
    }

    public function handle(Request $request, Closure $next)
    {
        if ($locale = $request->user()->locale) {

            $this->application->setLocale($locale);

            Carbon::setLocale($locale);

            setlocale(LC_TIME, $this->locale->full($locale));

            config()->set('_locale', $locale);
        }
        if (defined('LOCALE')) {
            return $next($request);
        }

        if ($locale = $request->get('_locale')) {
            if ($locale) {
                $request->session()->put('_locale', $locale);
            } else {
                $request->session()->remove('_locale');
            }

            return $this->redirect->back();
        }

        if ($locale = $request->session()->get('_locale')) {

            $this->application->setLocale($locale);

            Carbon::setLocale($locale);

            setlocale(LC_TIME, $this->locale->full($locale));

            config()->set('_locale', $locale);
        }

        if (!$locale) {

            $locale = $this->app->getLocale() ?: config('streams::locales.default');

            $this->application->setLocale($locale);

            Carbon::setLocale($locale);

            setlocale(LC_TIME, $this->locale->full($locale));
        }

        return $next($request);
    }
}