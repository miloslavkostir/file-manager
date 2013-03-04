<?php

// Load libraries
require_once __DIR__ . "/../vendor/autoload.php";

$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory(__DIR__ . "/../src")
        ->addDirectory(__DIR__ . "/cases")
        ->setCacheStorage(new Nette\Caching\Storages\DevNullStorage)
        ->register();

// Load TestCase
require_once "TestCase.php";