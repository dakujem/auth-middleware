<?php

declare(strict_types=1);

namespace Dakujem\Middleware;

use DomainException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use UnexpectedValueException;

/**
 * A callable decoder that uses Firebase JWT implementation.
 *
 * Note:
 *   firebase/php-jwt is a peer dependency, it needs to be installed separately:
 *   `composer require firebase/php-jwt:"^6.0 | ^5.5"`
 *
 * Usage with TokenMiddleware:
 *   $mw = new TokenMiddleware(new FirebaseJwtDecoder(new Secret('my-secret-is-not-committed-to-the-repo', 'HS256')));
 *
 * Warning:
 *   This decoder ensures _only_ that the token has been signed by the given secret key
 *   and that it is not expired (`exp` claim) or used before intended (`nbf` and `iat` claims).
 *   The rest of the authorization process is entirely up to your app's logic.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class FirebaseJwtDecoder
{
    /** @var Key|Key[] */
    private Key|array $keys;

    public function __construct(SecretContract ...$secret)
    {
        if (!class_exists(Key::class)) {
            throw new LogicException('Peer dependency version mismatch. Please upgrade the `firebase/php-jwt` package.');
        }
        if (count($secret) === 0) {
            throw new InvalidArgumentException('No keys passed to the decoder.');
        }
        $key = fn(SecretContract $s): Key => new Key($s->keyMaterial(), $s->algorithm());
        if (count($secret) === 1) {
            $this->keys = $key(array_pop($secret));
        } else {
            $this->keys = array_map($key, $secret);
        }
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
                $this->keys,
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
