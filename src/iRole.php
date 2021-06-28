<?php

declare(strict_types=1);

namespace FluencePrototype\Auth;

use FluencePrototype\Bean\iBean;

/**
 * Interface iRole
 * @package FluencePrototype\Auth
 */
interface iRole extends iBean
{

    /**
     * iRole constructor.
     * @param string $role
     */
    public function __construct(string $role);

    /**
     * @return string
     */
    public function getRole(): string;

    /**
     * @param string $role
     */
    public function setRole(string $role): void;

}