<?php

declare(strict_types=1);

namespace Dakujem\Middleware\Test;

require_once __DIR__ . '/bootstrap.php';

use Dakujem\Middleware\Factory\AuthFactory;
use Dakujem\Middleware\Factory\AuthWizard;
use Dakujem\Middleware\GenericMiddleware;
use Dakujem\Middleware\TokenMiddleware;
use LogicException;
use Slim\Psr7\Factory\ResponseFactory;
use Tester\Assert;
use Tester\TestCase;

/**
 * Test of `AuthFactory` middleware factory and its helper `AuthWizard`.
 *
 * @see AuthFactory
 * @see AuthWizard
 *
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */
class _FactoryTest extends TestCase
{
    public function testWizardDefaultAlgo()
    {
        Assert::same('HS256', AuthWizard::$defaultAlgo);
    }

    public function testAuthFactoryReturnsCorrectMiddleware()
    {
        $f = new AuthFactory(fn() => fn() => null, new ResponseFactory());
        Assert::type(TokenMiddleware::class, $f->decodeTokens());
        Assert::type(GenericMiddleware::class, $f->assertTokens());
        Assert::type(GenericMiddleware::class, $f->inspectTokens(fn() => null));
    }

    public function testAuthWizardReturnsCorrectMiddleware()
    {
        Assert::type(AuthFactory::class, AuthWizard::factory(null, null));
        $rf = new ResponseFactory();
        Assert::type(TokenMiddleware::class, AuthWizard::decodeTokens('whatever'));
        Assert::type(GenericMiddleware::class, AuthWizard::assertTokens($rf));
        Assert::type(GenericMiddleware::class, AuthWizard::inspectTokens($rf, fn() => null));
    }

    public function testThrowsOnNoSecret()
    {
        // no secret key for decoding
        Assert::throws(
            fn() => AuthWizard::factory(null, new ResponseFactory())->decodeTokens(),
            LogicException::class,
            'Decoder factory not provided.'
        );
    }

    public function testThrowsOnNoResponseFactory()
    {
        // no response factory for 401 responses
        Assert::throws(
            fn() => AuthWizard::factory('a secret key what', null)->assertTokens(),
            LogicException::class,
            'Response factory not provided.'
        );
        Assert::throws(
            fn() => AuthWizard::factory('a secret key what', null)->inspectTokens(fn() => null),
            LogicException::class,
            'Response factory not provided.'
        );
    }

    public function testThrowsOnNoExtractors()
    {
        // no secret key for decoding
        Assert::throws(
            fn() => AuthWizard::factory('a secret key what', new ResponseFactory())->decodeTokens(null, null, null),
            LogicException::class,
            'No extractors. Using the token middleware without extractors is pointless.'
        );
    }
}

// run the test
(new _FactoryTest)->run();
