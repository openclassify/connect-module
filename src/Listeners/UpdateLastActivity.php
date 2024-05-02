<?php namespace Visiosoft\ConnectModule\Listeners;

use Anomaly\Streams\Platform\Application\Event\ApplicationHasLoaded;
use Anomaly\UsersModule\User\Contract\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class UpdateLastActivity
{
    protected $users;

    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    public function handle(ApplicationHasLoaded $event)
    {
        $user = Auth::user();
        if ($user)
        {
            $this->users->touchLastActivity($user);
        }
    }
}
