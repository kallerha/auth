<?php

declare(strict_types=1);

namespace FluencePrototype\Auth;

use Attribute;
use FluencePrototype\Bean\Bean;
use FluencePrototype\Http\Messages\iResponse;
use FluencePrototype\Http\Messages\Response\StatusCodes;
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
     * @param array $userRoles
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

        $authenticationService = new AuthenticationService();

        if ($authenticationService->isLoggedIn()) {
            if (count(value: $userRoles) === 1 && $userRoles[0] === 'guest') {
                return false;
            }

            $userId = $authenticationService->getUserId();
            $user = Bean::findOne(className: User::class, sql: '`id` = ? AND `deleted` IS NULL', bindings: [$userId]);
            $userRole = $user->getRole()->getRole();

            if ($authenticationService->getUserRole() !== $userRole) {
                $authenticationService->setUserRole(role: $userRole);
            }

            if (!in_array(needle: $userRole, haystack: $userRoles, strict: true)) {
                http_response_code(response_code: StatusCodes::UNAUTHORIZED);

                exit;
            }

            return true;
        }

        if (count(value: $userRoles) === 1 && $userRoles[0] === 'guest') {
            return true;
        }

        return false;
    }

}