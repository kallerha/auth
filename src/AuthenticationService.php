<?php

declare(strict_types=1);

namespace FluencePrototype\Auth;

use FluencePrototype\Session\SessionService;
use RedBeanPHP\R;

/**
 * Class AuthenticationService
 * @package FluencePrototype\Auth
 */
class AuthenticationService
{

    private const TIME_SESSION = 3600;
    private const TIME_COOKIE = 365 * 86400;
    private const SESSION_USER_ID = 'g8k4dlf0ne4jfoj49r';
    private const SESSION_TIME = 'y546y6d8lo897o785j';

    private SessionService $sessionService;

    /**
     * AuthenticationService constructor.
     */
    public function __construct()
    {
        $this->sessionService = new SessionService();
    }

    /**
     * @param User $user
     */
    public function authorize(User $user): void
    {
        $this->sessionService->set(self::SESSION_USER_ID, $user->getId());
        $this->sessionService->set(self::SESSION_TIME, time());
    }

    /**
     *
     */
    public function unauthorize(): void
    {
        $this->sessionService->unset(self::SESSION_USER_ID);
        $this->sessionService->unset(self::SESSION_TIME);
    }

    /**
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        if ($this->sessionService->isSet(self::SESSION_USER_ID) && $this->sessionService->isSet(self::SESSION_TIME)) {
            $pastTime = $this->sessionService->get(self::SESSION_TIME);
            $currentTime = time();

            if ($currentTime - $pastTime > self::TIME_SESSION) {
                $this->unauthorize();

                return false;
            }

            $this->sessionService->set(self::SESSION_TIME, time());

            return true;
        }

        return false;
    }

    /**
     * @return User|null
     */
    public function getUserIfLoggedIn(): ?User
    {
        if ($this->isLoggedIn()) {
            $userId = $this->sessionService->get(self::SESSION_USER_ID);

            if ($userBean = R::findOne('user', '`id` = ? AND `deleted` IS NULL', [$userId])) {
                return User::fromBean($userBean);
            }
        }

        return null;
    }

}