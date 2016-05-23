<?php

/**
 * \AppserverIo\WebServer\Modules\CoreModuleTest
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

use AppserverIo\Psr\HttpMessage\RequestInterface;
use AppserverIo\Psr\HttpMessage\ResponseInterface;
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\WebServer\Mock\MockRequestContext;

/**
 * Test for the webserver core module
 *
 * @author    Bernhard Wick <bw@appserver.io>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class CoreModuleTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \AppserverIo\WebServer\Modules\CoreModule
     */
    public $module;

    /**
     * The document root this tests are using
     *
     * @var string $documentRoot
     */
    protected $documentRoot;

    /**
     * Test set up method
     *
     * @return void
     */
    public function setUp()
    {
        // set the document root
        $this->documentRoot = realpath(
            __DIR__ .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '_files' .
            DIRECTORY_SEPARATOR . 'modules' .
            DIRECTORY_SEPARATOR . CoreModule::MODULE_NAME . DIRECTORY_SEPARATOR
        );
        // set the module to test
        $this->module = new CoreModule();
    }

    /**
     * Tear down function
     *
     * @return void
     */
    protected function tearDown()
    {
        $this->module = null;
    }

    /**
     * Test for the correct module name
     *
     * @return void
     */
    public function testGetModuleName()
    {
        $this->assertSame('core', $this->module->getModuleName());
    }

    /**
     * Data Provider for the testProcessHookReaction method
     *
     * @return array
     */
    public function processHookReactionDataProvider()
    {
        // create re-usable mock objects here
        $request = $this->getMock('\AppserverIo\Psr\HttpMessage\RequestInterface');
        $response = $this->getMock('\AppserverIo\Psr\HttpMessage\ResponseInterface');

        return array(
            array($request, $response, ModuleHooks::REQUEST_PRE, false),
            array($request, $response, ModuleHooks::REQUEST_POST, true),
            array($request, $response, ModuleHooks::RESPONSE_PRE, false),
            array($request, $response, ModuleHooks::RESPONSE_POST, false),
        );
    }

    /**
     * Tests if the process method only listens to the correct hook
     *
     * @param \AppserverIo\Psr\HttpMessage\RequestInterface  $request       A request object
     * @param \AppserverIo\Psr\HttpMessage\ResponseInterface $response      A response object
     * @param integer                                        $hook          The current hook to process logic for
     * @param boolean                                        $shouldProcess Whether or not the process method should run
     *
     * @return void
     *
     * @dataProvider processHookReactionDataProvider
     */
    public function testProcessHookReaction(RequestInterface $request, ResponseInterface $response, $hook, $shouldProcess)
    {
        // create the mock of the request context with the correct number of calls to "getServerVar". This way we can test for further processing after hook check
        $requestContext = $this->getMock('\AppserverIo\Server\Interfaces\RequestContextInterface');
        if ($shouldProcess) {
            $requestContext->expects($this->atLeast(1))
                ->method('getServerVar')
                ->will($this->returnValue(''));
        } else {
            $requestContext->expects($this->never())
                ->method('getServerVar')
                ->will($this->returnValue(''));
        }

        $this->module->process($request, $response, $requestContext, $hook);
    }

    /**
     * Data Provider for the testProcessHookReaction method
     *
     * @return array
     */
    public function populateRequestContextScriptFilenameDataProvider()
    {
        return array(
            array('/abcd.txt', true, '/abcd.txt'),
            array('/iDoNotExist', false),
            array('/müllmänner.txt', true, '/müllmänner.txt')
        );
    }

    /**
     * Test for the correct setup of the SCRIPT_FILENAME server var after call to populateRequestContext
     *
     * @param string  $requestUri             The request URI teh context population is based on
     * @param boolean $isValidPath            Whether or not the script filename actually exists
     * @param string  $expectedScriptFilename The expected value of the SCRIPT_FILENAME server var
     *
     * @return void
     *
     * @dataProvider populateRequestContextScriptFilenameDataProvider
     */
    public function testPopulateRequestContextScriptFilename($requestUri, $isValidPath, $expectedScriptFilename = '')
    {
        // get a mock server context
        $serverContext = $this->getMock('\AppserverIo\Server\Interfaces\ServerContextInterface');
        $serverContext->expects($this->atLeast(1))
        ->method('getServerConfig')
        ->will($this->returnValue($this->getMock('\AppserverIo\Server\Interfaces\ServerConfigurationInterface')));
        $this->module->init($serverContext);

        // get a mock request context and prepare it with our request URI
        $requestContext = new MockRequestContext();
        $requestContext->setServerVar(ServerVars::X_REQUEST_URI, $requestUri);
        $requestContext->setServerVar(ServerVars::DOCUMENT_ROOT, $this->documentRoot);

        // populate the request context and test for the population result
        $this->module->populateRequestContext($requestContext);
        if ($isValidPath) {
            $this->assertTrue($requestContext->hasServerVar(ServerVars::SCRIPT_FILENAME),
                'Server var SCRIPT_FILENAME has not been set');
            $this->assertSame($this->documentRoot . $expectedScriptFilename, $requestContext->getServerVar(ServerVars::SCRIPT_FILENAME));
        } else {
            $this->assertFalse($requestContext->hasServerVar(ServerVars::SCRIPT_FILENAME),
                'Server var SCRIPT_FILENAME has falsely been set');
        }
    }
}
