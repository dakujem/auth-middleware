<?php

declare(strict_types=1);

/**
 * This file is a part of dakujem/auth-middleware package.
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */

namespace Dakujem\Middleware\Test;

require_once __DIR__ . '/../vendor/autoload.php';

use Tester\Environment;

// Nette Tester initialization.
Environment::setup();

// This is done in this version only. See FirebaseJwtDecoder for info.
error_reporting(E_ALL ^ E_USER_WARNING);
