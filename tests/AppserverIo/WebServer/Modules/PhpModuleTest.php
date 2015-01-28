<?php

/**
 * \AppserverIo\WebServer\Modules\PhpModuleTest
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */

namespace AppserverIo\WebServer\Modules;

/**
 * Class PhpModuleTest
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class PhpModuleTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var PhpModule
     */
    public $module;

    /**
     * Initializes module object to test.
     *
     * @return void
     */
    public function setUp() {
        $this->module = new PhpModule();
    }

    /**
     * Test add header functionality on response object.
     */
    public function testModuleName() {
        $module = $this->module;
        $this->assertSame('php', $module::MODULE_NAME);
    }
}
