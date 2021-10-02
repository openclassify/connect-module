<?php namespace Visiosoft\ConnectModule\Http\Controller;

use Anomaly\Streams\Platform\Http\Controller\ResourceController;
use Anomaly\UsersModule\User\Contract\UserInterface;
use Anomaly\UsersModule\User\Contract\UserRepositoryInterface;
use Anomaly\UsersModule\User\UserPassword;
use Illuminate\Contracts\Auth\Guard;
use Anomaly\UsersModule\User\UserAuthenticator;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Visiosoft\ConnectModule\Notification\ResetYourPassword;


class ApiController extends ResourceController
{
    private $authenticator;
    private $guard;
    private $userRepository;

    public function __construct(
        UserAuthenticator $authenticator,
        UserRepositoryInterface $userRepository,
        Guard $guard
    )
    {
        $this->authenticator = $authenticator;
        $this->userRepository = $userRepository;
        $this->guard = $guard;
        parent::__construct();
    }

    public function login()
    {
        if ($response = $this->authenticator->authenticate($this->request->toArray())) {
            if ($response instanceof UserInterface) {
                $this->guard->login($response, false);
                $response = ['id' => $response->getId()];
                $response['token'] = app(\Visiosoft\ConnectModule\User\UserModel::class)->find(Auth::id())->createToken(Auth::id())->accessToken;
                return $this->response->json($response);
            }
        }

        return $this->response->json(['error' => true, 'message' => trans('visiosoft.module.connect::message.error_auth')]);
    }

    public function register()
    {
        $validator = Validator::make(request()->all(), [
            'email' => 'required|email|unique:users_users,email',
            'password' => 'required|max:55',
            'name' => 'required|max:55'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {

            $username = Str::slug(preg_replace('/@.*?$/', '', $this->request->email) . rand(0, 999999));

            $user = $this->userRepository->create([
                'email' => $this->request->email,
                'username' => $username,
                'password' => $this->request->password,
                'display_name' => $this->request->name,
                'first_name' => array_first(explode(' ', $this->request->name)),
            ]);

            $user->setAttribute('password', $this->request->password);

            $user->save();

            $this->guard->login($user, false);


            return [
                'success' => true,
                'id' => $user->getId(),
                'token' => app(\Visiosoft\ConnectModule\User\UserModel::class)->find(Auth::id())->createToken(Auth::id())->accessToken

            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'msg' => $e->getMessage()
            ];
        }
    }

    public function forgotPassword()
    {
        $users = app(UserRepositoryInterface::class);
        $encrypter = app(Encrypter::class);

        $parameters = array();


        // Forgot Request
        if (!$this->request->has('token')) {
            $validator = Validator::make(request()->all(), [
                'email' => 'required|email',
                'callback' => 'required',
                'success-params' => 'required',
                'error-params' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            try {
                $password = app(UserPassword::class);

                if (!$user = $users->findByEmail($this->request->email)) {
                    throw new \Exception(trans('anomaly.module.users::error.reset_password'));
                }

                $password->forgot($user);

                $parameters['token'] = $encrypter->encrypt($user->getResetCode());
                $parameters['success-verification'] = $encrypter->encrypt($this->request->get('success-params'));
                $parameters['error-verification'] = $encrypter->encrypt($this->request->get('error-params'));
                $parameters['redirect'] = $encrypter->encrypt($this->request->callback);

                $url = url('api/forgot-password') . '?' . http_build_query($parameters);

                $user->notify(new ResetYourPassword($url));

                return ['success' => true];

            } catch (\Exception $e) {

                return [
                    'success' => false,
                    'msg' => $e->getMessage()
                ];
            }
        }

        // Redirect Request
        try {
            $callback = $encrypter->decrypt($this->request->redirect);

            $success = $encrypter->decrypt($this->request->get('success-verification'));
            $error = $encrypter->decrypt($this->request->get('error-verification'));

            if ($user = $users->findBy('reset_code', $encrypter->decrypt($this->request->token))) {
                $callback = $this->generateCallback($callback, ['code' => $this->request->token], $success);
            } else {
                $callback = $this->generateCallback($callback, [], $error);
            }

            return Redirect::to($callback);

        } catch (\Exception $e) {

            return [
                'success' => false,
            ];
        }
    }

    public function renew()
    {
        $validator = Validator::make(request()->all(), [
            'code' => 'required',
            'new-password' => 'required|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $users = app(UserRepositoryInterface::class);
        $encrypter = app(Encrypter::class);
        $password = app(UserPassword::class);

        try {
            $code = $encrypter->decrypt($this->request->code);

            if (!$user = $users->findBy('reset_code', $encrypter->decrypt($this->request->code))) {
                throw new \Exception(trans('anomaly.module.users::error.reset_password'));
            }

            if (!$password->reset($user, $code, $this->request->get('new-password'))) {
                throw new \Exception(trans('anomaly.module.users::error.reset_password'));
            }

            return [
                'success' => true,
            ];

        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }
    }

    public function generateCallback($url, array $parameters, $string_parameters = '')
    {
        $url_parsed = parse_url($url);

        if (isset($url_parsed['query'])) {
            return $url . "&" . (count($parameters)) ? http_build_query($parameters) . "&" . $string_parameters : "&" . $string_parameters;
        }

        return $url . "?" . (count($parameters) ? http_build_query($parameters) . "&" . $string_parameters : "&" . $string_parameters);
    }
}
