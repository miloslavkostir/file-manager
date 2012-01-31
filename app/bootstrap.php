<?php

use Nette\Application\Routers\Route,
        Nette\Application\Routers\RouteList,
        Nette\Application\Routers\SimpleRouter;


require LIBS_DIR . '/Nette/nette.min.php';


// Configure application
$configurator = new Nette\Config\Configurator;
$configurator->setProductionMode();
$configurator->setTempDirectory(__DIR__ . '/../temp');


// Enable Nette Debugger for error visualisation & logging
$configurator->enableDebugger(__DIR__ . '/../log');


// Enable RobotLoader - this will load all classes automatically
$configurator->createRobotLoader()
	->addDirectory(APP_DIR)
	->addDirectory(LIBS_DIR)
	->register();


// Create Dependency Injection container from config.neon file
$env = $configurator->isProductionMode() ? $configurator::PRODUCTION : $configurator::DEVELOPMENT;
$configurator->addConfig(__DIR__ . '/config/core.neon', $env);
$container = $configurator->createContainer();


// Setup router using mod_rewrite detection
if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {

	$container->router = $router = new RouteList;
	$router[] = new Route('index.php', 'Front:Homepage:', Route::ONE_WAY);

	$router[] = $install = new RouteList('Install');
	$install[] = new Route('install/<presenter>/<action>', 'Homepage:');

	$router[] = $admin = new RouteList('Admin');
	$admin[] = new Route('admin/<presenter>/<action>', 'Dashboard:');

	$router[] = $front = new RouteList('Front');
	$front[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:');

} else {
	$container->router = new SimpleRouter('Front:Homepage:');
}


// Configure and run the application!
$container->application->run();