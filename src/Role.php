<?php

declare(strict_types=1);

namespace FluencePrototype\Auth;

use FluencePrototype\Bean\Bean;
use RedBeanPHP\OODB;

/**
 * Class Role
 * @package FluencePrototype\Auth
 */
class Role implements iRole
{

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
        $bean = $this->findOrDispense('role');
        $bean->role = $this->role;

        return $bean;
    }

    /**
     * @inheritDoc
     */
    public static function fromBean(OODBBean $bean): static
    {
        $role = $bean->role;

        return new Role($role);
    }

}