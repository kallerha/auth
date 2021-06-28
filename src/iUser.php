<?php

declare(strict_types=1);

namespace FluencePrototype\Auth;

use FluencePrototype\Bean\iBean;

/**
 * Interface iUser
 * @package FluencePrototype\Auth
 */
interface iUser extends iBean
{

    /**
     * iUser constructor.
     * @param string $email
     * @param string $password
     * @param iRole $role
     */
    public function __construct(string $email, string $password, iRole $role);

    /**
     * @return string
     */
    public function getEmail(): string;

    /**
     * @param string $email
     */
    public function setEmail(string $email): void;

    /**
     * @return string
     */
    public function getPassword(): string;

    /**
     * @param string $password
     */
    public function setPassword(string $password): void;

    /**
     * @return iRole
     */
    public function getRole(): iRole;

    /**
     * @param iRole $role
     */
    public function setRole(iRole $role): void;

}