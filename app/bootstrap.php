<?php

use Nette\Diagnostics\Debugger,
	Nette\Application\Routers\Route,
        Nette\Application\Routers\RouteList,
        Nette\Application\Routers\SimpleRouter;


require LIBS_DIR . '/Nette/nette.min.php';


// Enable Nette Debugger for error visualisation & logging
Debugger::$logDirectory = __DIR__ . '/../log';
Debugger::$strictMode = TRUE;
Debugger::enable(Debugger::PRODUCTION);


// Load configuration from config.neon file
$configurator = new Nette\Config\Configurator;
$configurator->setCacheDirectory(__DIR__ . '/../temp');


// Enable RobotLoader - this will load all classes automatically
$configurator->createRobotLoader()
	->addDirectory(APP_DIR)
	->addDirectory(LIBS_DIR)
	->register();


// Create Dependency Injection container from config.neon file
$container = $configurator->loadConfig(__DIR__ . "/config/core.neon");


// Opens already started session
if ($container->session->exists()) {
	$container->session->start();
}


// Setup router using mod_rewrite detection
if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {

	$container->router = $router = new RouteList;
	$router[] = new Route('index.php', 'Front:Homepage:', Route::ONE_WAY);

	$router[] = $install = new RouteList('Install');
	$install[] = new Route('install/<presenter>/<action>', 'Homepage:');

	$router[] = $admin = new RouteList('Admin');
	$admin[] = new Route('admin/<presenter>/<action>', 'Overview:');

	$router[] = $front = new RouteList('Front');
	$front[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:');

} else {
	$container->router = new SimpleRouter('Front:Homepage:');
}


// Configure and run the application!
$application = $container->application;
$application->catchExceptions = TRUE;
$application->errorPresenter = 'Error';
$application->run();