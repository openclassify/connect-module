<?php

namespace Visiosoft\ConnectModule\Exceptions;

use Anomaly\Streams\Platform\Exception\ExceptionIdentifier;
use Exception;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;


class ExceptionHandler extends Handler
{
    protected $original;

    protected $schema = [
        'success' => true,
        'data' => [],
        'message' => '',
        'error_code' => 0
    ];

    protected $internalDontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];


    protected function prepareException(Throwable $e)
    {
        $this->original = $e;

        return parent::prepareException($e); // TODO: Change the autogenerated stub
    }

    public function render($request, Throwable $e)
    {

        if ($e instanceof AuthenticationException) {
            return $this->unauthenticated($request, $e);
        }

        if ($e instanceof NotFoundHttpException && $redirect = config('streams::404.redirect')) {
            return redirect($redirect);
        }

        return parent::render($request, $e);
    }

    protected function renderHttpException(HttpExceptionInterface $e)
    {

        if (env('APP_DEBUG') === true) {
            return $this->convertExceptionToResponse($e);
        }

        $summary = $e->getMessage();
        $headers = $e->getHeaders();
        $code = $e->getStatusCode();
        $name = trans("streams::error.{$code}.name");
        $message = trans("streams::error.{$code}.message");
        $id = $this->container->make(ExceptionIdentifier::class)->identify($this->original);

        if (view()->exists($view = "streams::errors/{$code}")) {
            return response()->view($view, compact('id', 'code', 'name', 'message', 'summary'), $code, $headers);
        }

        return response()->view(
            'streams::errors/error',
            compact('id', 'code', 'name', 'message', 'summary'),
            $code,
            $headers
        );
    }


    public function report(Throwable $e)
    {
        if (app()->bound('sentry') &&
            $this->shouldReport($e) &&
            env('SENTRY_LARAVEL_DSN') &&
            !empty(env('SENTRY_LARAVEL_DSN'))) {
            app('sentry')->captureException($e);
        }

        if ($e instanceof Swift_TransportException) {
            echo json_encode([
                'success' => false,
                'msg' => trans('visiosoft.theme.base::message.error_mail'),
            ]);

            die();
        }

        if (\request()->is('api/*')) {
            $error_code = $e->getCode();

            $error_list = trans("visiosoft.module.connect::errors");

            $message = (!in_array($error_code, array_keys($error_list))) ? $e->getMessage() : trans("visiosoft.module.connect::errors." . $error_code);

            if ($e instanceof AuthenticationException) {
                return $this->unauthenticated(\request(), $e);
            }

            $schema = $this->schema;
            $schema['success'] = false;
            $schema['message'] = $message;
            $schema['error_code'] = $error_code;

            header('Content-Type: application/json; charset=UTF-8', true);
            header('Access-Control-Allow-Origin: *');

            return response($schema, $error_code);
        } else {
            $this->original = $e;

            return parent::report($e);
        }
    }


    protected function context()
    {
        try {
            return array_filter(
                [
                    'user' => Auth::id(),
                    'email' => Auth::user() ? Auth::user()->email : null,
                    'url' => request() ? request()->fullUrl() : null,
                    'identifier' => $this->container->make(ExceptionIdentifier::class)->identify($this->original),
                ]
            );
        } catch (Throwable $e) {
            return [];
        }
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        $schema = $this->schema;
        $schema['success'] = false;
        $schema['message'] = trans('streams::error.401.name');
        $schema['error_code'] = 401;

        if ($request->expectsJson()) {
            return response($schema, 401);
        }

        if ($request->segment(1) === 'admin') {
            return redirect()->guest('admin/login');
        } else {
            if ($request->is('api/*')) {
                return response($schema, 401);
            }
            return redirect()->guest('login');
        }
    }
}
