<?php

/**
 * \AppserverIo\WebServer\Modules\FastCgiModuleTest
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Modules
 * @author     Tim Wagner <tw@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */

namespace AppserverIo\WebServer\Modules;

use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Psr\HttpMessage\Protocol;

/**
 * Class FastCgiModuleTest
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Modules
 * @author     Tim Wagner <tw@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */
class FastCgiModuleTest extends \PHPUnit_Framework_TestCase
{

    /**
     * The module to test.
     *
     * @var \AppserverIo\WebServer\Modules\FastCgiModule
     */
    public $fastCgiModule;

    /**
     * Initializes module object to test.
     *
     * @return void
     */
    public function setUp()
    {
        $this->fastCgiModule = new FastCgiModule();
    }

    /**
     * Test add header functionality on response object.
     *
     * @return void
     */
    public function testModuleName()
    {
        $this->assertSame(FastCgiModule::MODULE_NAME, $this->fastCgiModule->getModuleName());
    }

    /**
     * Test the process method to make sure that the FastCGI connection and the
     * environment has been initialized.
     *
     * @return void
     */
    public function testProcess()
    {

        // create a mock response
        $mockFastCgiRequest = $this->getMock('Crunch\FastCGI\Request', array(), array(), '', false);
        $mockFastCgiResponse = $this->getMock('Crunch\FastCGI\Response');

        // create a pair of sockets
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        // create the mock connection, pass the client socket to the constructor
        $mockFastCgiConnection = $this->getMock('Crunch\FastCGI\Connection', array('newRequest', 'request'), array(array_shift($sockets)));
        $mockFastCgiConnection->expects($this->once())
            ->method('newRequest')
            ->will($this->returnValue($mockFastCgiRequest));
        $mockFastCgiConnection->expects($this->once())
            ->method('request')
            ->will($this->returnValue($mockFastCgiResponse));

        // create the mock client
        $mockFastCgiClient = $this->getMock('Crunch\FastCGI\Client', array('connect'), array(), '', false);
        $mockFastCgiClient->expects($this->once())
            ->method('connect')
            ->will($this->returnValue($mockFastCgiConnection));

        // create a mock version of the module
        $mockFastCgiModule = $this->getMock('AppserverIo\WebServer\Modules\FastCgiModule', array('getFastCgiClient'));
        $mockFastCgiModule->expects($this->once())
            ->method('getFastCgiClient')
            ->will($this->returnValue($mockFastCgiClient));

        // prepare the array with the server vars
        $serverVars = array(
            array(ServerVars::SERVER_HANDLER,  FastCgiModule::MODULE_NAME),
            array(ServerVars::REQUEST_METHOD,  Protocol::METHOD_POST),
            array(ServerVars::SCRIPT_FILENAME, '/opt/appserver-0.8.2-alpha.48/webapps/test.php'),
            array(ServerVars::QUERY_STRING,    'test=test'),
            array(ServerVars::SCRIPT_NAME,     '/index.php'),
            array(ServerVars::REQUEST_URI,     '/test.php/test?test=test'),
            array(ServerVars::DOCUMENT_ROOT,   '/opt/appserver-0.8.2-alpha.48/webapps'),
            array(ServerVars::SERVER_PROTOCOL, 'HTTP/1.1'),
            array(ServerVars::HTTPS,           'off'),
            array(ServerVars::SERVER_SOFTWARE, 'appserver/0.8.2 (mac) (PHP 5.5.10)'),
            array(ServerVars::REMOTE_ADDR,     '127.0.0.1'),
            array(ServerVars::REMOTE_PORT,      63752),
            array(ServerVars::SERVER_ADDR,     '127.0.0.1'),
            array(ServerVars::SERVER_PORT,      9080),
            array(ServerVars::SERVER_NAME,     'localhost')
        );

        // create a mock HTTP request instance
        $mockHttpRequest = $this->getMockForAbstractClass('AppserverIo\Http\HttpRequest');
        $mockHttpRequest->expects($this->once())
            ->method('getHeaders')
            ->will($this->returnValue(array()));
        $mockHttpRequest->expects($this->once())
            ->method('getBodyStream')
            ->will($this->returnValue(fopen('php://memory', 'rw')));

        // create a mock HTTP request context instance
        $mockRequestContext = $this->getMockForAbstractClass('AppserverIo\Server\Interfaces\RequestContextInterface');
        $mockRequestContext->expects($this->any())
            ->method('hasServerVar')
            ->will($this->returnValue(true));
        $mockRequestContext->expects($this->any())
            ->method('hasHeader')
            ->will($this->returnValue(false));
        $mockRequestContext->expects($this->any())
            ->method('getServerVar')
            ->will($this->returnValueMap($serverVars));
        $mockRequestContext->expects($this->once())
            ->method('getEnvVars')
            ->will($this->returnValue(array()));

        // create a mock HTTP response instance
        $mockHttpResponse = $this->getMockForAbstractClass('AppserverIo\Http\HttpResponse');

        // process the FastCGI request
        $mockFastCgiModule->process($mockHttpRequest, $mockHttpResponse, $mockRequestContext, ModuleHooks::REQUEST_POST);
    }
}
