<?php

declare(strict_types=1);

namespace FluencePrototype\Auth;

use Composer\Autoload\ClassLoader;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
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

    private const TIME_SESSION = 3600;
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
    public function authorize(User $user, bool $rememberMe = false): void
    {
        $this->sessionService->set(AuthenticationService::SESSION_USER_ID, $user->getId());
        $this->sessionService->set(AuthenticationService::SESSION_USER_ROLE, $user->getRole()->getRole());
        $this->sessionService->set(AuthenticationService::SESSION_TIME, time());

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if ($rememberMe === true) {
            try {
                $reflectionClass = new ReflectionClass(ClassLoader::class);
                $vendorDir = dirname($reflectionClass->getFileName(), 2);

                ob_start();

                include $vendorDir . '/../jwt_private_key';

                $privateKeyContent = ob_get_clean();

                $privateKey = <<<EOF
$privateKeyContent
EOF;

                $currentTime = time();

                $payload = [
                    'exp' => $currentTime + $_ENV['JWT_COOKIE_EXPIRY'],
                    'iat' => $currentTime,
                    'iss' => $_ENV['JWT_ISSUER'],
                    'claims' => [
                        'userId' => $user->getId()
                    ]
                ];

                $jwtToken = JWT::encode($payload, $privateKey, 'RS256');

                setcookie(
                    $_ENV['JWT_COOKIE_NAME'],
                    $jwtToken,
                    $currentTime + $_ENV['JWT_COOKIE_EXPIRY'],
                    '/',
                    $_ENV['JWT_COOKIE_DOMAIN'],
                    true,
                    true
                );
            } catch (ReflectionException) {
            }
        }

        session_regenerate_id(delete_old_session: false);
        session_write_close();
    }

    /**
     *
     */
    public function unauthorize(): void
    {
        $this->sessionService->unset(AuthenticationService::SESSION_USER_ID);
        $this->sessionService->unset(AuthenticationService::SESSION_USER_ROLE);
        $this->sessionService->unset(AuthenticationService::SESSION_TIME);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        setcookie(
            $_ENV['JWT_COOKIE_NAME'],
            '',
            -1,
            '/',
            $_ENV['JWT_COOKIE_DOMAIN']
        );

        session_unset();
        session_destroy();
        session_write_close();
    }

    /**
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        if ($this->sessionService->isSet(AuthenticationService::SESSION_USER_ID)
            && $this->sessionService->isSet(AuthenticationService::SESSION_USER_ROLE)
            && $this->sessionService->isSet(AuthenticationService::SESSION_TIME)) {
            $pastTime = $this->sessionService->get(AuthenticationService::SESSION_TIME);
            $currentTime = time();

            if ($jwtToken = filter_input(INPUT_COOKIE, $_ENV['JWT_COOKIE_NAME'], FILTER_SANITIZE_STRING)) {
                try {
                    $reflectionClass = new ReflectionClass(ClassLoader::class);
                    $vendorDir = dirname($reflectionClass->getFileName(), 2);

                    ob_start();

                    include $vendorDir . '/../jwt_public_key';

                    $publicKeyContent = ob_get_clean();

                    $publicKey = <<<EOF
$publicKeyContent
EOF;

                    if ($payload = JWT::decode($jwtToken, $publicKey, ['RS256'])) {
                        if ($this->sessionService->get(AuthenticationService::SESSION_USER_ID) === $payload->claims->userId) {
                            if (session_status() !== PHP_SESSION_ACTIVE) {
                                session_start();
                            }

                            session_regenerate_id(delete_old_session: false);
                            session_write_close();

                            return true;
                        }

                        setcookie(
                            $_ENV['JWT_COOKIE_NAME'],
                            '',
                            -1,
                            '/',
                            $_ENV['JWT_COOKIE_DOMAIN']
                        );
                    }
                } catch (ExpiredException | ReflectionException) {
                }
            }

            if ($currentTime - $pastTime > AuthenticationService::TIME_SESSION) {
                $this->unauthorize();

                return false;
            }

            $this->sessionService->set(AuthenticationService::SESSION_TIME, time());

            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            session_regenerate_id(delete_old_session: false);
            session_write_close();

            return true;
        }

        return false;
    }


    /**
     * @return int|null
     */
    public function getUserId(): null|int
    {
        if ($userId = $this->sessionService->get(AuthenticationService::SESSION_USER_ID)) {
            return $userId;
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getUserRole(): null|string
    {
        if ($userRole = $this->sessionService->get(AuthenticationService::SESSION_USER_ROLE)) {
            return $userRole;
        }

        return null;
    }

}