<?php

declare(strict_types=1);

namespace Dakujem\Middleware;

use DomainException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionClass;
use ReflectionException;
use UnexpectedValueException;

/**
 * A callable decoder that uses Firebase JWT implementation.
 *
 * Notes:
 *   firebase/php-jwt is a peer dependency, you need to install it separately:
 *   `composer require firebase/php-jwt:"^5.5"`
 *   This decoder works with all v5.* branches of firebase/php-jwt,
 *   but we recommend using version "^5.5" to mitigate a possible security issue CVE-2021-46743.
 *
 *   Alternatively, update dakujem/auth-middleware to v2 and firebase/php-jwt to v6 to prevent the issue completely:
 *   `composer require dakujem/auth-middleware:"^2" firebase/php-jwt:"^6.0"`
 *
 * Usage with TokenMiddleware:
 *   $mw = new TokenMiddleware(new FirebaseJwtDecoder(new Secret('my-secret-is-not-committed-to-the-repo')));
 *
 * Warning:
 *   This decoder _only_ ensures that the token has been signed by the given secret key
 *   and that it is not expired (`exp` claim) or used before intended (`nbf` and `iat` claims).
 *   The rest of the authorization process is entirely up to your app's logic.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class FirebaseJwtDecoder
{
    /** @var Key|Key[]|string */
    private $secret;
    private ?array $algos;

    public function __construct($secret, ?array $algos = null)
    {
        if (empty($secret)) {
            throw new InvalidArgumentException('Invalid configuration: The secret key may not be empty.');
        }
        $algos ??= ['HS256', 'HS512', 'HS384'];

        if (count($algos) === 0) {
            throw new InvalidArgumentException('Invalid configuration: No encryption algorithms provided.');
        }

        if (!is_string($secret) && !class_exists(Key::class)) {
            throw new InvalidArgumentException(
                'Unsupported configuration. To use the `Secret` objects, upgrade peer library `firebase/php-jwt` to version 5.5 or 6 and above.'
            );
        }
        if (!is_string($secret)) {
            $key = fn(SecretContract $s): Key => new Key($s->keyMaterial(), $s->algorithm());
            if (is_array($secret) && count($secret) === 1) {
                $secret = array_pop($secret);
            }
            if ($secret instanceof SecretContract) {
                $this->secret = $key($secret);
            } elseif (is_array($secret)) {
                $this->secret = array_map($key, $secret);
            } else {
                throw new InvalidArgumentException(
                    'Invalid configuration: The secret must ether be a string, a `SecretContract` object or an array of such objects.'
                );
            }
        } else {
            $this->secret = $secret;
        }

        // In certain configurations, the decoding will fail. To prevent the failure, we throw an exception here.
        if (is_string($secret) && class_exists(Key::class)) {
            if (count($algos) > 1) {
                try {
                    // The following detects v6 of firebase/php-jwt lib.
                    if ((new ReflectionClass(JWT::class))->getMethod('decode')->getNumberOfParameters() < 3) {
                        //
                        // If this is happening to you, there are 3 options:
                        // 1. use a single secret+algorithm combination either using the `Secret` object or passing an array with a single algorithm to the `$algos` parameter
                        // 2. use multiple `Secret` objects and pass them to the `$secret` parameter AND use "kid" JWT header parameter when encoding the JWT
                        // 3. downgrade firebase/php-jwt to version v5.5 or below (not recommended)
                        //
                        // This is done to mitigate a possible security issue CVE-2021-46743.
                        // For more details, see https://github.com/firebase/php-jwt/issues/351.
                        //
                        throw new InvalidArgumentException(
                            'Peer library `firebase/php-jwt` has been updated to version v6 or above, which does not work with the current secret+algorithm configuration combination. Refer to the documentation od dakujem/auth-middleware for this version to solve the configuration issue.'
                        );
                    }
                } catch (ReflectionException $e) {
                    // ignore
                }
                //
                // If this is happening to you, there are 3 options:
                // 1. use a single secret+algorithm combination either using the `Secret` object or passing an array with a single algorithm to the `$algos` parameter
                // 2. use multiple `Secret` objects and pass them to the `$secret` parameter AND use "kid" JWT header parameter when encoding the JWT
                // 3. ignore this warning or downgrade dakujem/auth-middleware (not recommended)
                //
                // This is done to mitigate a possible security issue CVE-2021-46743.
                // For more details, see https://github.com/firebase/php-jwt/issues/351.
                //
                trigger_error(
                    'Peer library `firebase/php-jwt` has been updated to a version able to circumvent security vulnerability CVE-2021-46743. Please use the `Secret` objects instead of string constants: `new FirebaseJwtDecoder(new Secret($secretString, $algorithm))`.',
                    E_USER_WARNING,
                );
            }
            if (count($algos) === 1) {
                $algorithm = array_pop($algos);
                $this->secret = new Key($secret, $algorithm);
            }
        }
        $this->algos = $algos;
    }

    /**
     * Decodes a raw token.
     * Respects these 3 registered claims: `exp` (expiration), `nbf`/`iat` (not before).
     *
     * @param string $token raw token string
     * @param LoggerInterface|null $logger
     * @return object decoded token payload
     * @throws UnexpectedValueException
     */
    public function __invoke(string $token, ?LoggerInterface $logger = null): object
    {
        try {
            return JWT::decode(
                $token,
                $this->secret,
                $this->algos
            );
        } catch (UnexpectedValueException $throwable) {
            $logger && $logger->log(LogLevel::DEBUG, $throwable->getMessage(), [$token, $throwable]);
            throw $throwable;
        } catch (DomainException $throwable) {
            $re = new UnexpectedValueException(
                'The JWT is malformed, invalid JSON.',
                $throwable->getCode(),
                $throwable
            );
            $logger && $logger->log(LogLevel::DEBUG, $re->getMessage(), [$token, $throwable]);
            throw $re;
        }
    }
}
