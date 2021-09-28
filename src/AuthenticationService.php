<?php

declare(strict_types=1);

namespace FluencePrototype\Auth;

use FluencePrototype\Session\SessionService;

/**
 * Class AuthenticationService
 * @package FluencePrototype\Auth
 */
class AuthenticationService
{

    private const TIME_SESSION = 3600;
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

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        session_regenerate_id(delete_old_session: true);
        session_write_close();
    }

    /**
     *
     */
    public function unauthorize(): void
    {
        $this->sessionService->unset(self::SESSION_USER_ID);
        $this->sessionService->unset(self::SESSION_USER_ROLE);
        $this->sessionService->unset(self::SESSION_TIME);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        session_unset();
        session_destroy();
        session_write_close();
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

            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            session_regenerate_id(delete_old_session: true);
            session_write_close();

            return true;
        }

        return false;
    }


    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        if ($userId = $this->sessionService->get(self::SESSION_USER_ID)) {
            return $userId;
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getUserRole(): ?string
    {
        if ($userRole = $this->sessionService->get(self::SESSION_USER_ROLE)) {
            return $userRole;
        }

        return null;
    }

}