<?php

declare(strict_types=1);

namespace FluencePrototype\Auth;

use FluencePrototype\Bean\Bean;
use RedBeanPHP\OODB;

/**
 * Class User
 * @package FluencePrototype\Auth
 */
final class User implements iUser
{

    use Bean;

    private string $email;
    private string $password;
    private iRole $role;

    /**
     * @inheritDoc
     */
    public function __construct(string $email, string $password, iRole $role)
    {
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
    }

    /**
     * @inheritDoc
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @inheritDoc
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @inheritDoc
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @inheritDoc
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @inheritDoc
     */
    public function getRole(): iRole
    {
        return $this->role;
    }

    /**
     * @inheritDoc
     */
    public function setRole(iRole $role): void
    {
        $this->role = $role;
    }

    /**
     * @inheritDoc
     */
    public function toBean(): OODBBean
    {
        $bean = $this->findOrDispense('user');
        $bean->email = $this->email;
        $bean->password = $this->password;
        $bean->role = $this->role->toBean();
    }

    /**
     * @inheritDoc
     */
    public static function fromBean(OODBBean $bean): static
    {
        $email = $bean->email;
        $password = $bean->password;
        $role = new Role($bean->role);

        return new User($email, $password, $role);
    }

}