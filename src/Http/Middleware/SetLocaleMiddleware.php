<?php

namespace Visiosoft\ConnectModule\Http\Middleware;

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
        App         $app,
        Locale      $locale,
        Redirector  $redirect,
        Application $application
    )
    {
        $this->app = $app;
        $this->locale = $locale;
        $this->redirect = $redirect;
        $this->application = $application;
    }

    public function handle(Request $request, Closure $next)
    {
        $locale = null;
        if (!empty($request->get('_locale'))) {
            $locale = $request->get('_locale');
        }

        if (!empty($request->header('locale'))) {
            $locale = $request->header('locale');
        }
        if ($locale) {

            $this->application->setLocale($locale);

            Carbon::setLocale($locale);

            setlocale(LC_TIME, $this->locale->full($locale));

            config()->set('_locale', $locale);

        } else {

            $locale = $this->app->getLocale() ?: config('streams::locales.default');

            $this->application->setLocale($locale);

            Carbon::setLocale($locale);

            setlocale(LC_TIME, $this->locale->full($locale));
        }

        return $next($request);
    }
}