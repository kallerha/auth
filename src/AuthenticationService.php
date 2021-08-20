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
    private const SESSION_USER_ID = 'session_user_id';
    private const SESSION_USER_ROLE = 'session_user_role';
    private const SESSION_TIME = 'session_time';

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
        $this->sessionService->set(self::SESSION_USER_ROLE, $user->getRole()->getRole());
        $this->sessionService->set(self::SESSION_TIME, time());

        session_regenerate_id(delete_old_session: true);
    }

    /**
     *
     */
    public function unauthorize(): void
    {
        $this->sessionService->unset(self::SESSION_USER_ID);
        $this->sessionService->unset(self::SESSION_USER_ROLE);
        $this->sessionService->unset(self::SESSION_TIME);

        session_destroy();
    }

    /**
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        if ($this->sessionService->isSet(self::SESSION_USER_ID)
            && $this->sessionService->isSet(self::SESSION_USER_ROLE)
            && $this->sessionService->isSet(self::SESSION_TIME)) {
            $pastTime = $this->sessionService->get(self::SESSION_TIME);
            $currentTime = time();

            if ($currentTime - $pastTime > self::TIME_SESSION) {
                $this->unauthorize();

                return false;
            }

            $this->sessionService->set(self::SESSION_TIME, time());

            session_regenerate_id(delete_old_session: true);

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

    /**
     * @return string|null
     */
    public function getUserRoleIfLoggedIn(): ?string
    {
        if ($this->isLoggedIn()) {
            return $this->sessionService->get(self::SESSION_USER_ROLE);
        }

        return null;
    }

}