<?php

declare(strict_types=1);

namespace FluencePrototype\Auth;

use FluencePrototype\Bean\Bean;
use FluencePrototype\Validation\Validators\EmailValidation;
use FluencePrototype\Validation\Validators\NotEmptyValidation;
use RedBeanPHP\OODBBean;

/**
 * Class User
 * @package FluencePrototype\Auth
 */
final class User implements iUser
{

    public const BEAN = 'user';

    use Bean;

    #[NotEmptyValidation('Du skal angive en email')]
    #[EmailValidation('Du skal angive en gyldig email')]
    private string $email;

    #[NotEmptyValidation('Du skal angive en adgangskode')]
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
        $bean = $this->findOrDispense(className: User::class);
        $bean->email = $this->email;
        $bean->password = $this->password;
        $bean->role = $this->role->toBean();

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

        $email = $bean->email;
        $password = $bean->password;
        $role = Role::fromBean(bean: $bean->role);
        $user = new User(email: $email, password: $password, role: $role);
        $user->setBeanDetails(bean: $bean);

        return $user;
    }

    public function cleanup(): void
    {
        // TODO: Implement cleanup() method.
    }

}