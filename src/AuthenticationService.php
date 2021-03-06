<?php

declare(strict_types=1);

namespace FluencePrototype\Auth;

use Composer\Autoload\ClassLoader;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
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
    public function authorize(User $user, bool $rememberMe = false): void
    {
        $this->sessionService->set(name: AuthenticationService::SESSION_USER_ID, value: $user->getId());
        $this->sessionService->set(name: AuthenticationService::SESSION_USER_ROLE, value: $user->getRole()->getRole());
        $this->sessionService->set(name: AuthenticationService::SESSION_TIME, value: time());

        if ($rememberMe === true) {
            try {
                $reflectionClass = new ReflectionClass(objectOrClass: ClassLoader::class);
                $vendorDir = dirname(path: $reflectionClass->getFileName(), levels: 2);

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

                $jwtToken = JWT::encode(payload: $payload, key: $privateKey, alg: 'RS256');

                setcookie(
                    name: $_ENV['JWT_COOKIE_NAME'],
                    value: $jwtToken,
                    expires_or_options: $currentTime + $_ENV['JWT_COOKIE_EXPIRY'],
                    path: '/',
                    domain: $_ENV['JWT_COOKIE_DOMAIN'],
                    secure: true,
                    httponly: true
                );
            } catch (ReflectionException) {
            }
        }

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
        if (!($this->sessionService->isSet(name: AuthenticationService::SESSION_USER_ID)
            && $this->sessionService->isSet(name: AuthenticationService::SESSION_USER_ROLE)
            && $this->sessionService->isSet(name: AuthenticationService::SESSION_TIME))) {
            if ($jwtToken = filter_input(type: INPUT_COOKIE, var_name: $_ENV['JWT_COOKIE_NAME'], filter: FILTER_SANITIZE_STRING)) {
                try {
                    $reflectionClass = new ReflectionClass(objectOrClass: ClassLoader::class);
                    $vendorDir = dirname(path: $reflectionClass->getFileName(), levels: 2);

                    ob_start();

                    include $vendorDir . '/../jwt_public_key';

                    $publicKeyContent = ob_get_clean();

                    $publicKey = <<<EOF
$publicKeyContent
EOF;

                    if (($payload = JWT::decode(jwt: $jwtToken, keyOrKeyArray: $publicKey, allowed_algs: ['RS256'])) &&
                        is_object($payload) &&
                        isset($payload->claims) &&
                        isset($payload->claims->userId) &&
                        ($user = Bean::findOne(className: User::class, sql: '`id` = ? AND `deleted` IS NULL', bindings: [$payload->claims->userId]))) {
                        $this->sessionService->set(name: AuthenticationService::SESSION_USER_ID, value: $user->getId());
                        $this->sessionService->set(name: AuthenticationService::SESSION_USER_ROLE, value: $user->getRole()->getRole());
                        $this->sessionService->set(name: AuthenticationService::SESSION_TIME, value: time());
                    }
                } catch (ExpiredException | ReflectionException) {
                }
            }
        }

        if ($this->sessionService->isSet(name: AuthenticationService::SESSION_USER_ID)
            && $this->sessionService->isSet(name: AuthenticationService::SESSION_USER_ROLE)
            && $this->sessionService->isSet(name: AuthenticationService::SESSION_TIME)) {
            $pastTime = $this->sessionService->get(name: AuthenticationService::SESSION_TIME);
            $currentTime = time();

            if ($jwtToken = filter_input(type: INPUT_COOKIE, var_name: $_ENV['JWT_COOKIE_NAME'], filter: FILTER_SANITIZE_STRING)) {
                try {
                    $reflectionClass = new ReflectionClass(objectOrClass: ClassLoader::class);
                    $vendorDir = dirname(path: $reflectionClass->getFileName(), levels: 2);

                    ob_start();

                    include $vendorDir . '/../jwt_public_key';

                    $publicKeyContent = ob_get_clean();

                    $publicKey = <<<EOF
$publicKeyContent
EOF;

                    if (($payload = JWT::decode(jwt: $jwtToken, keyOrKeyArray: $publicKey, allowed_algs: ['RS256'])) &&
                        is_object($payload) &&
                        isset($payload->claims) &&
                        isset($payload->claims->userId) &&
                        $this->sessionService->get(name: AuthenticationService::SESSION_USER_ID) === $payload->claims->userId) {

                        if (session_status() !== PHP_SESSION_ACTIVE) {
                            session_start();
                        }

                        session_regenerate_id(delete_old_session: false);

                        if (session_status() === PHP_SESSION_ACTIVE) {
                            session_write_close();
                        }

                        return true;
                    }
                } catch (ExpiredException | ReflectionException) {
                }
            }

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