<?php

use Nette\Diagnostics\Debugger,
	Nette\Application\Routers\Route,
        Nette\Application\Routers\RouteList,
        Nette\Application\Routers\SimpleRouter;


// Load Nette Framework
$params['libsDir'] = __DIR__ . '/../libs';
require $params['libsDir'] . '/Nette/nette.min.php';


// Enable Nette Debugger for error visualisation & logging
Debugger::$logDirectory = __DIR__ . '/../log';
Debugger::$strictMode = TRUE;
Debugger::enable(Debugger::PRODUCTION);

// Load configuration from config.neon file
$configurator = new Nette\Configurator;
$configurator->container->params += $params;
$configurator->container->params['tempDir'] = __DIR__ . '/../temp';
$configurator->container->getService('robotLoader'); // fix http://forum.nette.org/en/932-trouble-with-installation#p4000
$container = $configurator->loadConfig(__DIR__ . '/config.neon');


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