<?php

declare(strict_types=1);

namespace FluencePrototype\Auth;

use Composer\Autoload\ClassLoader;
use FluencePrototype\Bean\Bean;
use FluencePrototype\Session\SessionService;
use JetBrains\PhpStorm\Pure;
use ReflectionClass;
use ReflectionException;

/**
 * Class AuthenticationService
 * @package FluencePrototype\Auth
 */
class AuthenticationService
{

    private const SESSION_USER_ID = 'session_user_id';
    private const SESSION_USER_ROLE = 'session_user_role';
    private const SESSION_TIME = 'session_time';

    private SessionService $sessionService;

    /**
     * AuthenticationService constructor.
     */
    #[Pure] public function __construct()
    {
        $this->sessionService = new SessionService();
    }

    /**
     * @param User $user
     * @param bool $rememberMe
     */
    public function authorize(User $user): void
    {
        $this->sessionService->set(name: AuthenticationService::SESSION_USER_ID, value: $user->getId());
        $this->sessionService->set(name: AuthenticationService::SESSION_USER_ROLE, value: $user->getRole()->getRole());
        $this->sessionService->set(name: AuthenticationService::SESSION_TIME, value: time());

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        session_regenerate_id(delete_old_session: false);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    /**
     *
     */
    public function unauthorize(): void
    {
        $this->sessionService->unset(name: AuthenticationService::SESSION_USER_ID);
        $this->sessionService->unset(name: AuthenticationService::SESSION_USER_ROLE);
        $this->sessionService->unset(name: AuthenticationService::SESSION_TIME);

        setcookie(
            name: $_ENV['JWT_COOKIE_NAME'],
            value: '',
            expires_or_options: -1,
            path: '/',
            domain: $_ENV['JWT_COOKIE_DOMAIN'],
            secure: true,
            httponly: true
        );

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        session_unset();
        session_destroy();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    /**
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        if ($this->sessionService->isSet(name: AuthenticationService::SESSION_USER_ID)
            && $this->sessionService->isSet(name: AuthenticationService::SESSION_USER_ROLE)
            && $this->sessionService->isSet(name: AuthenticationService::SESSION_TIME)) {
            $pastTime = $this->sessionService->get(name: AuthenticationService::SESSION_TIME);
            $currentTime = time();

            if ($currentTime - $pastTime > $_ENV['SESSION_AUTH_TIME']) {
                $this->unauthorize();

                return false;
            }

            $this->sessionService->set(name: AuthenticationService::SESSION_TIME, value: time());

            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            session_regenerate_id(delete_old_session: false);

            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            return true;
        }

        return false;
    }

    /**
     * @return int|null
     */
    public function getUserId(): null|int
    {
        if ($userId = $this->sessionService->get(name: AuthenticationService::SESSION_USER_ID)) {
            return $userId;
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getUserRole(): null|string
    {
        if ($userRole = $this->sessionService->get(name: AuthenticationService::SESSION_USER_ROLE)) {
            return $userRole;
        }

        return null;
    }

    /**
     * @param string $role
     */
    public function setUserRole(string $role): void
    {
        $this->sessionService->set(name: AuthenticationService::SESSION_USER_ROLE, value: $role);
    }

}