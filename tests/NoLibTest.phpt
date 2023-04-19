<?php

declare(strict_types=1);

namespace Dakujem\Middleware\Test;

require_once __DIR__ . '/../vendor/nette/tester/src/bootstrap.php';
require_once __DIR__ . '/../src/Factory/AuthFactory.php';
require_once __DIR__ . '/../src/Factory/AuthWizard.php';
require_once __DIR__ . '/../src/SecretContract.php';
require_once __DIR__ . '/../src/Secret.php';
require_once __DIR__ . '/../src/FirebaseJwtDecoder.php';
require_once __DIR__ . '/../vendor/psr/http-factory/src/ResponseFactoryInterface.php';
require_once __DIR__ . '/../vendor/slim/psr7/src/Factory/ResponseFactory.php';

use Dakujem\Middleware\Factory\AuthWizard;
use Dakujem\Middleware\FirebaseJwtDecoder;
use Dakujem\Middleware\Secret;
use LogicException;
use Slim\Psr7\Factory\ResponseFactory;
use Tester\Assert;

/**
 * Test the behaviour when the peer lib is not installed.
 *
 * @see FirebaseJwtDecoder
 *
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */

Assert::throws(
    fn() => AuthWizard::defaultDecoder('doesntmatter'),
    LogicException::class,
    'Firebase JWT is not installed. Requires firebase/php-jwt package (`composer require firebase/php-jwt:"^6.0|^5.5"`).'
);

//
// Implementation note:
//
//   There is an intentional call to `AuthWizard::defaultDecoder` inside `AuthWizard::factory` method
//   that creates the decoder callable instance _before_ creating the factory wrapper (fn()=>$decoder).
//   Do NOT optimize it into `$secret !== null ? fn() => self::defaultDecoder($secret) : null`.
//
//   It is done so that the factory throws instantly and the error is easier to tackle.
//
Assert::throws(
    fn() => AuthWizard::factory('doesntmatter', new ResponseFactory()),
    LogicException::class,
    'Firebase JWT is not installed. Requires firebase/php-jwt package (`composer require firebase/php-jwt:"^6.0|^5.5"`).'
);


Assert::throws(
    fn() => new FirebaseJwtDecoder(new Secret('doesntmatter', 'whatever')),
    LogicException::class,
    'Peer dependency version mismatch. Please upgrade the `firebase/php-jwt` package.'
);
