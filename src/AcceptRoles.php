<?php

declare(strict_types=1);

namespace FluencePrototype\Auth;

use Attribute;
use FluencePrototype\Http\Messages\iResponse;
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
        $authenticationServer = new AuthenticationService();
        $userRole = $authenticationServer->getUserRoleIfLoggedIn();

        if (count($userRoles) === 1 && $userRoles[0] === 'guest') {
            if ($userRole) {
                $reflectionClass = new ReflectionClass($responseClass);

                if ($reflectionClass->implementsInterface(iResponse::class)) {
                    $response = $reflectionClass->newInstanceArgs($parameters);
                    $response->render();
                }
            }
        } else {
            if (!($authenticationServer->isLoggedIn()
                && $userRole
                && in_array(needle: $userRole, haystack: $userRoles, strict: true))) {
                $reflectionClass = new ReflectionClass($responseClass);

                if ($reflectionClass->implementsInterface(iResponse::class)) {
                    $response = $reflectionClass->newInstanceArgs($parameters);
                    $response->render();
                }
            }
        }
    }

}