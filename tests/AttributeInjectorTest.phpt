<?php

declare(strict_types=1);

namespace Dakujem\Middleware\Test;

require_once __DIR__ . '/bootstrap.php';

use Dakujem\Middleware\TokenManipulators;
use Dakujem\Middleware\TokenManipulators as Man;
use LogicException;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use Slim\Psr7\Factory\RequestFactory;
use Tester\Assert;
use Tester\TestCase;
use Throwable;
use TypeError;

/**
 * Test of TokenManipulators::attributeInjector static factory.
 *
 * @see TokenManipulators::attributeInjector()
 *
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */
class _AttributeInjectorTest extends TestCase
{
    public function testWriter()
    {
        // create an empty test request
        $request = $this->createRequest();

        // sanity test (no token there)
        Assert::same(null, $request->getAttribute('token'));

        // a test token ...
        $token = (object)[
            'sub' => 42,
        ];

        Assert::same(
            $token,
            (Man::attributeInjector()(fn() => $token, $request))->getAttribute('token'),
            'The token should be written to the \'token\' attribute by default.'
        );
        Assert::same($token, (Man::attributeInjector('foo')(fn() => $token, $request))->getAttribute('foo'));
        Assert::same($token, (Man::attributeInjector('')(fn() => $token, $request))->getAttribute(''));

        $errorMessage = 'This will appear in the error message.';
        $decoderThatAlwaysThrows = function () use ($errorMessage) {
            throw new RuntimeException($errorMessage);
        };
        Assert::same(
            $errorMessage,
            (Man::attributeInjector()($decoderThatAlwaysThrows, $request))->getAttribute('token.error'),
            'The error message should be written to the \'token.error\' attribute by default.'
        );
        Assert::same(
            null,
            (Man::attributeInjector()($decoderThatAlwaysThrows, $request))->getAttribute('token'),
            'Nothing should be written to the \'token\' attribute.'
        );
        Assert::same(
            $errorMessage,
            (Man::attributeInjector('whatever', 'foo.bar')($decoderThatAlwaysThrows, $request))->getAttribute('foo.bar')
        );
        Assert::same(
            $errorMessage,
            (Man::attributeInjector('whatever', '')($decoderThatAlwaysThrows, $request))->getAttribute('')
        );
    }

    public function testLogicExceptionIsNotCaught()
    {
        // The injector only traps `RuntimeException` errors, other errors will be unhandled:
        $errorMessage = 'An error message.';
        // This test uses a decoder that always throws LogicException exception.
        Assert::throws(
            fn() => Man::attributeInjector()(
                function () use ($errorMessage) {
                    throw new LogicException($errorMessage);
                },
                $this->createRequest()
            ),
            LogicException::class,
            $errorMessage
        );
    }

    public function testNoTokenFound()
    {
        // On null token (token is not found in the request by the extractors), nothing is written
        $response = (Man::attributeInjector()(fn() => null, $this->createRequest()));
        Assert::null($response->getAttribute('token'));
        Assert::null($response->getAttribute('token.error'));
    }

    public function testCustomMessageRenderer()
    {
        $errorMessage = 'An error message.';
        $throws = function () use ($errorMessage) {
            throw new RuntimeException($errorMessage);
        };
        $request = $this->createRequest();

        // Default injector message handling - no custom message producer installed.
        Assert::same(
            $errorMessage,
            (Man::attributeInjector(Man::TOKEN_ATTRIBUTE_NAME, Man::ERROR_ATTRIBUTE_NAME, null)($throws, $request))->getAttribute(Man::ERROR_ATTRIBUTE_NAME),
            'The error message must be unchanged: Exception::getMessage is used.'
        );

        // Custom injector message handling.
        $customMessage = 'foobar';
        $messageProducer = function (Throwable $e) use ($customMessage) {
            return $customMessage;
        };
        Assert::same(
            $customMessage,
            (Man::attributeInjector(Man::TOKEN_ATTRIBUTE_NAME, Man::ERROR_ATTRIBUTE_NAME, $messageProducer)($throws, $request))->getAttribute(Man::ERROR_ATTRIBUTE_NAME),
            'The custom handler will produce the message.'
        );

        // It is also possible to assign the exception itself to the attribute.
        $injector = Man::attributeInjector(
            Man::TOKEN_ATTRIBUTE_NAME,
            Man::ERROR_ATTRIBUTE_NAME,
            fn(Throwable $e) => $e,
        );
        Assert::type(
            Throwable::class,
            ($injector($throws, $request))->getAttribute(Man::ERROR_ATTRIBUTE_NAME),
            'The exception will be returned.'
        );
        Assert::same(
            $errorMessage,
            ($injector($throws, $request))->getAttribute(Man::ERROR_ATTRIBUTE_NAME)->getMessage(),
            'The original decoder-provided exception message should be accessible.'
        );
    }

    /**
     * Type error, the first argument must be callable
     */
    public function testTypeErrorsAreThrown()
    {
        Assert::throws(function () {
            Man::attributeInjector()([42], $this->createRequest());
        }, TypeError::class);
        Assert::throws(function () {
            Man::attributeInjector()(42, $this->createRequest());
        }, TypeError::class);
    }

    private function createRequest(): RequestInterface
    {
        return (new RequestFactory())->createRequest('GET', '/');
    }
}

// run the test
(new _AttributeInjectorTest)->run();
