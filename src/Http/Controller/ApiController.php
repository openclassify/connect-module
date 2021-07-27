<?php namespace Visiosoft\ConnectModule\Http\Controller;

use Anomaly\Streams\Platform\Http\Controller\ResourceController;
use Anomaly\UsersModule\User\Contract\UserInterface;
use Anomaly\UsersModule\User\Contract\UserRepositoryInterface;
use Illuminate\Contracts\Auth\Guard;
use Anomaly\UsersModule\User\UserAuthenticator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


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

        return $this->response->json(['error' => true, 'message' => trans('visiosoft.module.connect::message.error_auth')], 401);
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

            $username = Str::slug(preg_replace('/@.*?$/', '', $this->request->email) . rand(0,999999));

            $user = $this->userRepository->create([
                'email' => $this->request->email,
                'username' => $username,
                'password' => $this->request->password,
                'display_name' => $this->request->name,
                'first_name' => array_first(explode(' ',$this->request->name)),
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
}
