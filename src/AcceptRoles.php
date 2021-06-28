<?php

declare(strict_types=1);

namespace FluencePrototype\Auth;

use Attribute;

/**
 * Class AcceptRoles
 * @package FluencePrototype\Auth
 */
#[Attribute(Attribute::TARGET_CLASS)]
class AcceptRoles
{

    /**
     * AcceptRoles constructor.
     * @param string ...$roles
     * @throws ForbiddenException
     */
    public function __construct(string ...$roles)
    {
        $authenticationServer = new AuthenticationService();
        $user = $authenticationServer->getUserIfLoggedIn();

        if (!($user && in_array(needle: $user->getRole()->getRole(), haystack: $roles, strict: true))) {
            throw new ForbiddenException();
        }
    }

}