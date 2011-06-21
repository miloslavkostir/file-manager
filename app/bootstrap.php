<?php

/**
 * My Application bootstrap file.
 */


use Nette\Diagnostics\Debugger,
	Nette\Application\Routers\Route,
        Nette\Application\Routers\RouteList;


// Load Nette Framework
// this allows load Nette Framework classes automatically so that
// you don't have to litter your code with 'require' statements
require LIBS_DIR . '/Nette/Nette/loader.php';


// Enable Nette\Debugger for error visualisation & logging
Debugger::$strictMode = TRUE;
Debugger::enable();


$configurator = new Nette\Configurator;
$configurator->loadConfig(__DIR__ . '/config.neon');


$application = $configurator->container->application;
$application->errorPresenter = 'Error';
//$application->catchExceptions = TRUE;

dibi::connect($configurator->container->params->database);

// Setup router
$application->onStartup[] = function() use ($application) {
	$router = $application->getRouter();

        $admin = $router[] = new RouteList('Admin');
        $admin[] = new Route('index.php', 'Overview:', Route::ONE_WAY);
        $admin[] = new Route('admin/<presenter>/<action>[/<id>]', 'Overview:');

        $front = $router[] = new RouteList('Front');
        $front[] = new Route('index.php', 'Homepage:', Route::ONE_WAY);
        $front[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:');
};


// Run the application!
$application->run();

/**
 * create PO language files
 * https://github.com/karelklima/gettext-extractor
 */
//$ge = new NetteGettextExtractor('extractor.log');
//$ge->setupForms()->setupDataGrid();
//$ge->scan(LIBS_DIR . "/FileManager");
//$ge->save(LIBS_DIR . '/FileManager/locale/FileManager.en.po');