<?php

declare(strict_types=1);

namespace Dakujem\Middleware;

/**
 * Interface for encryption keys.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
interface SecretContract
{
    /**
     * Return the key material - the secret.
     */
    public function keyMaterial(): mixed;

    /**
     * Return the algorithm valid for this key.
     */
    public function algorithm(): string;
}
