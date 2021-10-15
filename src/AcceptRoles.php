<?php

declare(strict_types=1);

namespace FluencePrototype\Auth;

use Attribute;
use FluencePrototype\Http\Messages\iResponse;
use RedBeanPHP\R;
use ReflectionClass;
use ReflectionException;

/**
 * Class AcceptRoles
 * @package FluencePrototype\Auth
 */
#[Attribute(Attribute::TARGET_CLASS)]
class AcceptRoles
{

    /**
     * AcceptRoles constructor.
     * @param string $responseClass
     * @param array $parameters
     * @param string ...$userRoles
     * @throws ReflectionException
     */
    public function __construct(string $responseClass, array $parameters, array $userRoles)
    {
        if (!$this->isAuthorized(userRoles: $userRoles)) {
            $reflectionClass = new ReflectionClass(objectOrClass: $responseClass);

            if ($reflectionClass->implementsInterface(interface: iResponse::class)) {
                $response = $reflectionClass->newInstanceArgs(args: $parameters);
                $response->render();
            }
        }
    }

    /**
     * @param array $userRoles
     * @return bool
     */
    private function isAuthorized(array $userRoles): bool
    {
        if (empty($userRoles) || (count(value: $userRoles) === 1 && $userRoles[0] === 'anyone')) {
            return true;
        }

        $authenticationServer = new AuthenticationService();

        if ($authenticationServer->isLoggedIn()) {
            if (count(value: $userRoles) === 1 && $userRoles[0] === 'guest') {
                return false;
            }

            $userId = $authenticationServer->getUserId();
            $user = User::fromBean(R::findOne(type: User::BEAN, sql: '`id` = ?', bindings: [$userId]));
            $userRole = $user->getRole()->getRole();

            if (!in_array(needle: $userRole, haystack: $userRoles, strict: true)) {
                $authenticationServer->unauthorize();

                return false;
            }

            return true;
        }

        if (count(value: $userRoles) === 1 && $userRoles[0] === 'guest') {
            return true;
        }

        return false;
    }

}