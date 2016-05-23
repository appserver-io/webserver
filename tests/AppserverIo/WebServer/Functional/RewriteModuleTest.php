<?php

/**
 * \AppserverIo\WebServer\Tests\Functional\RewriteModuleTest
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

namespace AppserverIo\WebServer\Functional;

use AppserverIo\Psr\HttpMessage\Protocol;
use AppserverIo\Http\HttpRequest;
use AppserverIo\Http\HttpResponse;
use AppserverIo\WebServer\Mock\MockServerConfig;
use AppserverIo\WebServer\Mock\MockRequestContext;
use AppserverIo\WebServer\Modules\RewriteModule;
use AppserverIo\Server\Contexts\ServerContext;
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Server\Dictionaries\ModuleVars;
use AppserverIo\Server\Dictionaries\ServerVars;

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
     * The rewrite module instance to test.
     *
     * @var \AppserverIo\WebServer\Modules\RewriteModule
     */
    protected $rewriteModule;

    /**
     * Nested array of datasets we will tests one after another
     *
     * @var array $rewriteDataSets
     */
    protected $rewriteDataSets = array();

    /**
     * Nested array of datasets we will tests one after another.
     * Theses datasets contain redirects which have to be tested differently
     *
     * @var array $redirectDataSets
     */
    protected $redirectDataSets = array();

    /**
     * The mock server context we use in this test
     *
     * @var \AppserverIo\WebServer\Mock\MockServerContext $mockServerContext
     */
    protected $mockServerContext;

    /**
     * The request context we use in this test
     *
     * @var \AppserverIo\WebServer\Mock\MockRequestContext $mockRequestContext
     */
    protected $mockRequestContext;

    /**
     * List of files which will not be tested during the test run
     *
     * @var array $excludedDataFiles
     */
    protected $excludedDataFiles = array('.', '..', 'html');

    /**
     * @var \AppserverIo\Http\HttpRequest $request The request we need for processing
     */
    protected $request;

    /**
     * @var \AppserverIo\Http\HttpResponse $response The response we need for processing
     */
    protected $response;

    /**
     * Initializes the rewrite module to test.
     * Will also build up needed mock objects and provide data for the actual rewrite tests.
     *
     * @return void
     */
    public function setUp()
    {
        // Get an instance of the module we can test with
        $this->rewriteModule = new RewriteModule();

        // We need a mock server context to init our module, otherwise we cannot use it
        $this->mockServerContext = new ServerContext();
        $this->mockServerContext->init(new MockServerConfig(null));

        // We need a MockRequestContext to work on
        $this->mockRequestContext = new MockRequestContext();
        $this->mockRequestContext->setServerVar(ServerVars::DOCUMENT_ROOT, realpath(
            __DIR__ .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '_files' .
            DIRECTORY_SEPARATOR . 'modules' .
            DIRECTORY_SEPARATOR . RewriteModule::MODULE_NAME . DIRECTORY_SEPARATOR
        ));

        // The module has to be inited
        $this->rewriteModule->init($this->mockServerContext);

        // We will collect all data files
        $dataPath = __DIR__ .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '_files' .
            DIRECTORY_SEPARATOR . 'modules' .
            DIRECTORY_SEPARATOR . RewriteModule::MODULE_NAME . DIRECTORY_SEPARATOR;

        $dataFiles = scandir($dataPath);

        // Iterate over all data files and collect the sets of test data
        foreach ($dataFiles as $dataFile) {

            // Skip the files we do not want
            foreach ($this->excludedDataFiles as $excludedDataFile) {

                if (strpos($dataFile, $excludedDataFile) === 0) {

                    continue 2;
                }
            }

            // Require the different files and collect the data
            $ruleSets = array();
            require $dataPath . $dataFile;

            // Iterate over all rulesets and collect the rules and maps
            foreach ($ruleSets as $setName => $ruleSet) {

                // Per convention we got the variables $rules, and $map within a file
                $this->rewriteDataSets[$setName] = array(
                    'redirect' => @$ruleSet['redirect'],
                    'redirectAs' => @$ruleSet['redirectAs'],
                    'rules' => $ruleSet['rules'],
                    'map' => $ruleSet['map']
                );
            }
        }

        // Create a request and response object we can use for our processing
        $this->request = new HttpRequest();
        $this->response = new HttpResponse();
        $this->response->init();
    }

    /**
     * Test if the constructor created an instance of the rewrite module.
     *
     * @return void
     */
    public function testInstanceOf()
    {
        $this->assertInstanceOf('\AppserverIo\WebServer\Modules\RewriteModule', $this->rewriteModule);
    }

    /**
     * Basic test of the module name
     *
     * @return void
     */
    public function testModuleName()
    {
        $module = $this->rewriteModule;
        $this->assertSame('rewrite', $module::MODULE_NAME);
    }

    /**
     * Iterate over all sets of data and test the rewriting
     *
     * @param string $testDataSet The dataset to test against
     *
     * @return boolean
     * @throws \Exception
     */
    public function assertionEngine($testDataSet)
    {
        // Do we know this dataset?
        $this->assertArrayHasKey($testDataSet, $this->rewriteDataSets);

        // Get our dataset
        $dataSet = $this->rewriteDataSets[$testDataSet];

        // We will get the rules into our module by ways of the volatile rewrites
        $this->mockRequestContext->setModuleVar(ModuleVars::VOLATILE_REWRITES, $dataSet['rules']);

        // No iterate over the map which is combined with the rules in the dataset
        foreach ($dataSet['map'] as $input => $desiredOutput) {

            // We will provide the crucial information by way of server vars
            $this->mockRequestContext->setServerVar(ServerVars::X_REQUEST_URI, $input);

            // Start the processing
            $this->rewriteModule->process(
                $this->request,
                $this->response,
                $this->mockRequestContext,
                ModuleHooks::REQUEST_POST
            );

            // If we got a redirect we have to test differently
            if (isset($dataSet['redirect'])) {

                try {
                    // Has the header location been set at all?
                    // If we did not match any redirect condition and will set it to the input so we get some output
                    if (!$this->response->hasHeader(Protocol::HEADER_LOCATION)) {

                        $this->response->addHeader(Protocol::HEADER_LOCATION, $input);
                    }

                    // Asserting that the header location was set correctly
                    $this->assertSame($desiredOutput, $this->response->getHeader(Protocol::HEADER_LOCATION));
                    // If we got a custom status code we have to check for it
                    if (isset($dataSet['redirectAs'])) {

                        $this->assertSame($dataSet['redirectAs'], (int) $this->response->getStatusCode());
                    }

                } catch (\Exception $e) {

                    // Do not forget to reset the response object we are using!!
                    $this->response = new HttpResponse();
                    $this->response->init();

                    // Re-throw the exception
                    throw $e;
                }

            } else {

                // Now check if we got the same thing here
                $this->assertSame($desiredOutput, $this->mockRequestContext->getServerVar(ServerVars::X_REQUEST_URI));
            }
        }

        // Still here? Then we are successful
        return true;
    }

    /**
     * Test wrapper for the appserver dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testAppserver()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('appserver');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the realDir dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testRealDir()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('realDir');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the realFile dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testRealFile()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('realFile');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the redirectUri dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testRedirectUri()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('uriRedirect');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Will test if it is possible to set a valid status code when redirecting.
     * Also wraps around our assertion engine
     *
     * @return null
     * @throws \Exception
     */
    public function testRedirectValidStatusCode()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('redirectValidStatusCode');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Will test if an invalid status code will be ignored and the default redirect status code will be set.
     * Also wraps around our assertion engine
     *
     * @return null
     * @throws \Exception
     */
    public function testRedirectInvalidStatusCode()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('redirectInvalidStatusCode');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the symlink dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testSymlink()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('symlink');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the urlencode dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testUrlencode()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('urlencode');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the LFlag dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testLFlag()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('LFlag');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the RFlag dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testRFlag()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('RFlag');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the NCFlag dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testNCFlag()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('NCFlag');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the mixedFlags dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testMixedFlags()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('mixedFlags');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the magento dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testMagento()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('magento');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the singleBackreference dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testSingleBackreference()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('singleBackreference');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the doubleBackreference dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testDoubleBackreference()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('doubleBackreference');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the mixedBackreference dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testMixedBackreference()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('mixedBackreference');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the blockingBackreferences dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testBlockingBackreferences()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('blockingBackreferences');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the serverVars dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testServerVars()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('serverVars');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the varCondition dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testVarCondition()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('varCondition');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the generalRedirect dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testGeneralRedirect()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('generalRedirect');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Test wrapper for the conditionedRedirect dataset
     *
     * @return null
     * @throws \Exception
     */
    public function testConditionedRedirect()
    {
        try {

            // Now check if we got the same thing here
            $this->assertionEngine('conditionedRedirect');

        } catch (\Exception $e) {

            // Re-throw the exception
            throw $e;
        }
    }
}
