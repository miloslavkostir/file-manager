<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

use Nette\Http\Session;

/**
 * Mock of \Nette\Http\Session
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class MockSession extends Session
{

    /**
     * Constructor
     */
    public function __construct()
    {}

}