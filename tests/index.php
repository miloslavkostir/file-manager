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
 * PHPUnit Test in web browser.
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
PHPUnit_TextUI_TestRunner::run($suite);