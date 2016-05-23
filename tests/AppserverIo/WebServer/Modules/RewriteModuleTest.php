<?php

/**
 * \AppserverIo\WebServer\Modules\RewriteModuleTest
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Bernhard Wick <bw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */

namespace AppserverIo\WebServer\Modules;

use AppserverIo\Http\HttpRequest;
use AppserverIo\Http\HttpResponse;
use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\WebServer\Mock\MockFaultyRequestContext;
use AppserverIo\WebServer\Mock\MockRewriteModule;
use AppserverIo\WebServer\Mock\MockServerConfig;
use AppserverIo\WebServer\Mock\MockRequestContext;
use AppserverIo\WebServer\Mock\MockServerContext;
use AppserverIo\Server\Contexts\ServerContext;
use AppserverIo\Server\Dictionaries\EnvVars;
use AppserverIo\Server\Dictionaries\ModuleHooks;

/**
 * Class RewriteModuleTest
 *
 * Basic test class for the RewriteModule class.
 *
 * @author    Bernhard Wick <bw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class RewriteModuleTest extends \PHPUnit_Framework_TestCase
{

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
        $this->documentRoot = realpath(
            __DIR__ .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '_files' .
            DIRECTORY_SEPARATOR . 'modules' .
            DIRECTORY_SEPARATOR . RewriteModule::MODULE_NAME . DIRECTORY_SEPARATOR
        );
    }

    /**
     * Tests a certain path through the process() method
     *
     * @return void
     */
    public function testInitWithException()
    {
        // We should get a \AppserverIo\Server\Exceptions\ModuleException
        $this->setExpectedException('\AppserverIo\Server\Exceptions\ModuleException');

        // Get the objects we need
        $rewriteModule = new RewriteModule();
        $mockServerContext = new MockServerContext();

        // Do the thing
        $rewriteModule->init($mockServerContext);
    }

    /**
     * Tests the getDependencies() method
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $rewriteModule = new RewriteModule();
        $this->assertEmpty($rewriteModule->getDependencies());
    }

    /**
     * Tests the getModuleName() method
     *
     * @return void
     */
    public function testGetModuleName()
    {
        $rewriteModule = new RewriteModule();
        $this->assertEquals('rewrite', $rewriteModule->getModuleName());
    }

    /**
     * Tests the getRequestContext() method
     *
     * @return void
     */
    public function testGetRequestContext()
    {
        // Get objects we need
        $rewriteModule = new MockRewriteModule();
        $mockRequestContext = new MockRequestContext();
        $mockRequestContext->setServerVar(ServerVars::DOCUMENT_ROOT, $this->documentRoot);

        // Do the thing
        $rewriteModule->setRequestContext($mockRequestContext);
        $this->assertSame($mockRequestContext, $rewriteModule->getRequestContext());
    }

    /**
     * Tests the prepare() method
     *
     * @return void
     */
    public function testPrepare()
    {
        $rewriteModule = new RewriteModule();
        $rewriteModule->prepare();
    }

    /**
     * Tests a certain path through the process() method
     *
     * @return void
     */
    public function testProcessWithWrongHook()
    {
        // Get the objects we need
        $rewriteModule = new RewriteModule();
        $request = new HttpRequest();
        $response = new HttpResponse();
        $mockRequestContext = new MockRequestContext();
        $mockRequestContext->setServerVar(ServerVars::DOCUMENT_ROOT, $this->documentRoot);

        // Do the thing
        $this->assertSame(
            null,
            $rewriteModule->process($request, $response, $mockRequestContext, ModuleHooks::REQUEST_PRE)
        );
    }

    /**
     * Tests a certain path through the process() method
     *
     * @return void
     */
    public function testProcessWithException()
    {
        // We should get a \AppserverIo\Server\Exceptions\ModuleException
        $this->setExpectedException('\AppserverIo\Server\Exceptions\ModuleException');

        // Get the objects we need
        $rewriteModule = new RewriteModule();
        $request = new HttpRequest();
        $response = new HttpResponse();
        $mockFaultyRequestContext = new MockFaultyRequestContext();

        // Do the thing
        $rewriteModule->process($request, $response, $mockFaultyRequestContext, ModuleHooks::REQUEST_POST);
    }

    /**
     * Tests the fillContextBackreferences() method
     *
     * @return void
     */
    public function testFillContextBackreferences()
    {
        // Get the objects we need
        $rewriteModule = new MockRewriteModule();
        $request = new HttpRequest();
        $response = new HttpResponse();
        $mockRequestContext = new MockRequestContext();
        $mockRequestContext->setServerVar(ServerVars::DOCUMENT_ROOT, $this->documentRoot);

        // Do the thing
        $mockRequestContext->setEnvVar(EnvVars::HTTPS, 'test');
        $rewriteModule->setRequestContext($mockRequestContext);
        $rewriteModule->fillContextBackreferences();
        $this->assertEquals('test', $rewriteModule->getServerBackreferences()['$' . EnvVars::HTTPS]);
    }

    /**
     * Tests the fillHeaderBackreferences() method
     *
     * @return void
     */
    public function testFillHeaderBackreferences()
    {
        // Get the objects we need
        $rewriteModule = new MockRewriteModule();
        $request = new HttpRequest();
        $serverContext = new ServerContext();

        // Do the thing
        $serverContext->init(new MockServerConfig(null));
        $rewriteModule->init($serverContext);
        $request->addHeader('Host', 'test-host.com');
        $rewriteModule->fillHeaderBackreferences($request);

        // Test what we got
        $this->assertTrue(isset($rewriteModule->getServerBackreferences()['$Host']));
        $this->assertTrue(isset($rewriteModule->getServerBackreferences()['$HTTP_HOST']));
        $this->assertEquals('test-host.com', $rewriteModule->getServerBackreferences()['$HTTP_HOST']);
    }
}
