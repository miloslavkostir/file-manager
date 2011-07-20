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
//Debugger::enable(Debugger::DEVELOPMENT);

// Load configuration from config.neon file
$configurator = new Nette\Configurator;
$configurator->container->params += $params;
$configurator->container->params['tempDir'] = __DIR__ . '/../temp';
$container = $configurator->loadConfig(__DIR__ . '/config.neon');


// Setup Dibi connection
dibi::connect($container->params['database']);



// Setup router using mod_rewrite detection
if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {

        $router = $container->router;

        $admin = $router[] = new RouteList('Admin');
        $admin[] = new Route('index.php', 'Overview:', Route::ONE_WAY);
        $admin[] = new Route('admin/<presenter>/<action>[/<id>]', 'Overview:');

        $front = $router[] = new RouteList('Front');
        $front[] = new Route('index.php', 'Homepage:', Route::ONE_WAY);
        $front[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:');

} else {
	$container->router = new SimpleRouter('Front:Homepage:');
}


// Configure and run the application!
$application = $container->application;
$application->catchExceptions = TRUE;
$application->errorPresenter = 'Error';
$application->run();