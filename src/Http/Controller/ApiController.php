<?php namespace Visiosoft\ConnectModule\Http\Controller;

use Anomaly\Streams\Platform\Http\Controller\ResourceController;
use Anomaly\UsersModule\User\Contract\UserInterface;
use Illuminate\Contracts\Auth\Guard;
use Anomaly\UsersModule\User\UserAuthenticator;
use Illuminate\Support\Facades\Auth;

class ApiController extends ResourceController
{
    private $authenticator;
    private $guard;

    public function __construct(
        UserAuthenticator $authenticator,
        Guard $guard
    )
    {
        $this->authenticator = $authenticator;
        $this->guard = $guard;
        parent::__construct();
    }

    public function login()
    {
        if ($response = $this->authenticator->authenticate($this->request->toArray())) {
            if ($response instanceof UserInterface) {
                $this->guard->login($response, false);
                $response['error'] = false;
                $response['token'] = app(\Visiosoft\ConnectModule\User\UserModel::class)->find(Auth::id())->createToken(Auth::id())->accessToken;
                return $this->response->json($response);
            }
        }

        return $this->response->json(['error' => true, 'message' => trans('visiosoft.module.connect::message.error_auth')]);

    }
}
