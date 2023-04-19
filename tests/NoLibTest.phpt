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

use Dakujem\Middleware\Factory\AuthFactory;
use Dakujem\Middleware\Factory\AuthWizard;
use Dakujem\Middleware\FirebaseJwtDecoder;
use Dakujem\Middleware\Secret;
use InvalidArgumentException;
use LogicException;
use Tester\Assert;

/**
 * Test the behaviour when the peer lib is not installed.
 *
 * @see FirebaseJwtDecoder
 *
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */

Assert::throws(
    fn() => AuthFactory::defaultDecoderFactory('doesntmatter'),
    LogicException::class,
    'Firebase JWT is not installed. Requires firebase/php-jwt package (`composer require firebase/php-jwt:"^5.5"`).'
);

Assert::throws(
    fn() => new FirebaseJwtDecoder(new Secret('whatever', AuthWizard::$defaultAlgo)),
    InvalidArgumentException::class,
    'Unsupported configuration. To use the `Secret` objects, upgrade peer library `firebase/php-jwt` to version 5.5 or 6 and above.'
);
