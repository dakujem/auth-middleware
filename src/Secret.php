<?php

declare(strict_types=1);

namespace Dakujem\Middleware;

use InvalidArgumentException;

/**
 * A representation of a secret and an algorithm valid for the key.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class Secret implements SecretContract
{
    private string $algorithm;
    private $keyMaterial;

    public function __construct(mixed $keyMaterial, string $algorithm)
    {
        if (empty($keyMaterial)) {
            throw new InvalidArgumentException('Type error: $keyMaterial must not be empty');
        }
        $this->keyMaterial = $keyMaterial;
        $this->algorithm = $algorithm;
    }

    /**
     * Return the key material - the secret.
     */
    public function keyMaterial(): mixed
    {
        return $this->keyMaterial;
    }

    /**
     * Return the algorithm valid for this key.
     */
    public function algorithm(): string
    {
        return $this->algorithm;
    }
}
