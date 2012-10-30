<?php

// Load Nette
require_once __DIR__ . "/../../nette/Nette/loader.php";

$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory(__DIR__ . "/../FileManager")
        ->addDirectory(__DIR__ . "/cases")
        ->setCacheStorage(new Nette\Caching\Storages\DevNullStorage)
        ->register();

$indexedClasses = $loader->getIndexedClasses();

// Load TestCase
require_once "TestCase.php";