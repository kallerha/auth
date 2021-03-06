<?php

declare(strict_types=1);

namespace FluencePrototype\Auth;

use FluencePrototype\Bean\Bean;
use RedBeanPHP\OODBBean;

/**
 * Class Role
 * @package FluencePrototype\Auth
 */
class Role implements iRole
{

    public const BEAN = 'role';

    use Bean;

    private string $role;

    /**
     * @inheritDoc
     */
    public function __construct(string $role)
    {
        $this->role = $role;
    }

    /**
     * @inheritDoc
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @inheritDoc
     */
    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    /**
     * @inheritDoc
     */
    public function toBean(): OODBBean
    {
        $bean = $this->findOrDispense(className: Role::class);
        $bean->role = $this->role;

        return $bean;
    }

    /**
     * @inheritDoc
     */
    public static function fromBean(null|OODBBean $bean): null|static
    {
        if (!$bean) {
            return null;
        }

        $role = $bean->role;
        $role = new Role(role: $role);
        $role->setBeanDetails(bean: $bean);

        return $role;
    }

    public function cleanup(): void
    {
        // TODO: Implement cleanup() method.
    }

}