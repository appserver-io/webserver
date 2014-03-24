<?php
/**
 * \TechDivision\WebServer\ServerContextTest
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage tests
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_Http
 */

namespace TechDivision\WebServer;

use TechDivision\WebServer\Dictionaries\ServerVars;

/**
 * Class ServerContextTest
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage tests
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_Http
 */
class ServerContextTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \TechDivision\WebServer\ServerContext
     */
    public $serverContext;

    /**
     * Initializes server context object to test.
     *
     * @return void
     */
    public function setUp() {
        $this->serverContext = new ServerContext();
    }

    /**
     * Test set server var functionality on response object.
     */
    public function testSetServerVarToServerContextObject() {
        $serverPort = rand(8080,9090);

        $this->serverContext->setServerVar(ServerVars::SERVER_PORT, $serverPort);
        $this->serverContext->setServerVar(ServerVars::SERVER_ADDR, '10.20.30.40');
        $this->serverContext->setServerVar(ServerVars::SERVER_ADMIN, 'admin@phpunit.de');

        $this->assertSame($serverPort, $this->serverContext->getServerVar(ServerVars::SERVER_PORT));
        $this->assertSame('10.20.30.40', $this->serverContext->getServerVar(ServerVars::SERVER_ADDR));
        $this->assertSame('admin@phpunit.de', $this->serverContext->getServerVar(ServerVars::SERVER_ADMIN));
    }
}
