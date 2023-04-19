<?php

declare(strict_types=1);

namespace Dakujem\Middleware\Factory;

use Dakujem\Middleware\FirebaseJwtDecoder;
use Dakujem\Middleware\GenericMiddleware;
use Dakujem\Middleware\Secret;
use Dakujem\Middleware\SecretContract;
use Dakujem\Middleware\TokenManipulators as Man;
use Dakujem\Middleware\TokenMiddleware;
use Firebase\JWT\JWT;
use LogicException;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface as Logger;

/**
 * AuthWizard - friction reducer / convenience helper.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class AuthWizard
{
    public static string $defaultAlgo = 'HS256';

    /**
     * @see AuthFactory::decodeTokens()
     *
     * @param string|SecretContract[]|SecretContract $secret API secret key
     * @param string|null $tokenAttribute
     * @param string|null $headerName
     * @param string|null $cookieName
     * @param string|null $errorAttribute
     * @param Logger|null $logger
     * @return TokenMiddleware
     */
    public static function decodeTokens(
        string|array|SecretContract $secret,
        ?string $tokenAttribute = null,
        ?string $headerName = Man::HEADER_NAME,
        ?string $cookieName = Man::COOKIE_NAME,
        ?string $errorAttribute = null,
        ?Logger $logger = null,
    ): MiddlewareInterface {
        return self::factory($secret, null)->decodeTokens(
            $tokenAttribute,
            $headerName,
            $cookieName,
            $errorAttribute,
            $logger
        );
    }

    /**
     * @see AuthFactory::assertTokens()
     *
     * @param ResponseFactory $responseFactory
     * @param string|null $tokenAttribute
     * @param string|null $errorAttribute
     * @return GenericMiddleware
     */
    public static function assertTokens(
        ResponseFactory $responseFactory,
        ?string $tokenAttribute = null,
        ?string $errorAttribute = null,
    ): MiddlewareInterface {
        return self::factory(null, $responseFactory)->assertTokens($tokenAttribute, $errorAttribute);
    }

    /**
     * @see AuthFactory::inspectTokens()
     *
     * @param ResponseFactory $responseFactory
     * @param callable $inspector fn(Token,callable,callable):Response
     * @param string|null $tokenAttribute
     * @param string|null $errorAttribute
     * @return GenericMiddleware
     */
    public static function inspectTokens(
        ResponseFactory $responseFactory,
        callable $inspector,
        ?string $tokenAttribute = null,
        ?string $errorAttribute = null,
    ): MiddlewareInterface {
        return self::factory(null, $responseFactory)->inspectTokens($inspector, $tokenAttribute, $errorAttribute);
    }

    /**
     * Create an instance of AuthFactory.
     *
     * @param string|SecretContract[]|SecretContract|null $secret
     * @param ResponseFactory|null $responseFactory
     * @return AuthFactory
     */
    public static function factory(
        string|array|SecretContract|null $secret,
        ?ResponseFactory $responseFactory,
    ): AuthFactory {
        $decoder = $secret !== null ? self::defaultDecoder($secret) : null;
        return new AuthFactory(
            $decoder !== null ? fn() => $decoder : null,
            $responseFactory
        );
    }

    /**
     * Creates a default decoder factory.
     * The factory can be used for the constructor.
     *
     * @param string|SecretContract[]|SecretContract $secret secret key for JWT decoder
     * @param string|null $algo optional algorithm; only used when $secret is a string
     * @return callable fn():FirebaseJwtDecoder
     * @throws
     */
    public static function defaultDecoder(
        string|array|SecretContract $secret,
        ?string $algo = null,
    ): callable {
        if (!class_exists(JWT::class)) {
            throw new LogicException(
                'Firebase JWT is not installed. ' .
                'Requires firebase/php-jwt package (`composer require firebase/php-jwt:"^6.0|^5.5"`).'
            );
        }
        if (is_string($secret)) {
            $secret = new Secret($secret, $algo ?? self::$defaultAlgo);
        }
        return new FirebaseJwtDecoder(
            ...(is_iterable($secret) ? $secret : [$secret])
        );
    }
}
