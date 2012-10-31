<?php

/**
 * PHPUnit Test in web browser
 */

// Load Bootstrap file
require_once __DIR__ . "/bootstrap.php";

// Create test suite
$suite = new PHPUnit_Framework_TestSuite();
foreach ($loader->getIndexedClasses() as $className => $classFile) {
    // Get classes like '<className>Test'
    if (substr($className, -4) === 'Test') {
        $suite->addTestSuite($className);
    }
}

// Run tests
ob_start();
PHPUnit_TextUI_TestRunner::run($suite);

// Print results
dump(ob_get_clean());