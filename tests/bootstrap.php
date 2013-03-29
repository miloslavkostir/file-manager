<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

/**
 * Test bootstrap.
 */

// Load libraries
require_once __DIR__ . "/../vendor/autoload.php";

$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory(__DIR__ . "/../src")
        ->addDirectory(__DIR__ . "/cases")
        ->setCacheStorage(new Nette\Caching\Storages\DevNullStorage)
        ->register();

// Load TestCase
require_once "TestCase.php";