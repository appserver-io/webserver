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
     * Prepares a set of rules to be used for a test
     *
     * @param array $testRules The rules to prepare
     *
     * @return void
     */
    public function prepareRuleset($testRules)
    {
        // We will get the rules into our module by ways of the volatile rewrites
        $this->mockRequestContext->setModuleVar(ModuleVars::VOLATILE_REWRITES, $testRules);
    }

    /**
     * @param $input
     * @param $desiredRewrite
     */
    protected function assertDesiredRewrite($input, $desiredRewrite)
    {
        // We will provide the crucial information by way of server vars
        $this->mockRequestContext->setServerVar(ServerVars::X_REQUEST_URI, $input);

        // Start the processing
        $this->rewriteModule->process(
            $this->request,
            $this->response,
            $this->mockRequestContext,
            ModuleHooks::REQUEST_POST
        );

        // Now check if we got the same thing here
        $this->assertSame($desiredRewrite, $this->mockRequestContext->getServerVar(ServerVars::X_REQUEST_URI));
    }

    /**
     * @param $input
     * @param $desiredRewrite
     */
    protected function assertDesiredRedirect($input, $desiredRedirect, $redirectAs = null)
    {
        // We will provide the crucial information by way of server vars
        $this->mockRequestContext->setServerVar(ServerVars::X_REQUEST_URI, $input);

        // Do not forget to reset the response object we are using!!
        $this->response = new HttpResponse();
        $this->response->init();

        // Start the processing
        $this->rewriteModule->process(
            $this->request,
            $this->response,
            $this->mockRequestContext,
            ModuleHooks::REQUEST_POST
        );

        // Has the header location been set at all?
        // If we did not match any redirect condition and will set it to the input so we get some output
        if (!$this->response->hasHeader(Protocol::HEADER_LOCATION)) {
            $this->response->addHeader(Protocol::HEADER_LOCATION, $input);
        }

        // Asserting that the header location was set correctly
        $this->assertSame($desiredRedirect, $this->response->getHeader(Protocol::HEADER_LOCATION));
        // If we got a custom status code we have to check for it
        if (!is_null($redirectAs)) {
            $this->assertSame($redirectAs, (int)$this->response->getStatusCode());
        }
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function appserverTestDataProvider()
    {
        return array(
            array('/dl/API', '/dl.do/API'),
            array('/index/test', '/index.do/test'),
            array('/imprint', '/index.do/imprint'),
            array('/index?q=dfgdsfgs&p=fsdgdfg', '/index.do?q=dfgdsfgs&p=fsdgdfg')
        );
    }

    /**
     * Test wrapper for the symlink dataset
     *
     * @return void
     *
     * @dataProvider appserverTestDataProvider
     */
    public function testAppserver($uri, $result)
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '^/index([/\?]*.*)',
                'target' => '/index.do$1',
                'flag' => 'L'
            ),
            array(
                'condition' => 'downloads([/\?]*.*)',
                'target' => '/downloads.do/downloads$1',
                'flag' => 'L'
            ),
            array(
                'condition' => '^/dl([/\?]*.*)',
                'target' => '/dl.do$1',
                'flag' => 'L'
            ),
            array(
                'condition' => '^(/\?*.*)',
                'target' => '/index.do$1',
                'flag' => 'L'
            )
        ));

        $this->assertDesiredRewrite($uri, $result);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function realDirTestDataProvider()
    {
        return array(
            array('/html', '/html'),
            array('/html?123456', '/html?123456'),
            array('/html/test.gif', '/ERROR'),
            array('/html/symlink.html', '/ERROR'),
            array('/failing_dir', '/ERROR')
        );
    }

    /**
     * Test wrapper for the symlink dataset
     *
     * @return void
     *
     * @dataProvider realDirTestDataProvider
     */
    public function testRealDir($uri, $result)
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '-d',
                'target' => '',
                'flag' => 'L'
            ),
            array(
                'condition' => '(.*)',
                'target' => '/ERROR',
                'flag' => 'L'
            )
        ));

        $this->assertDesiredRewrite($uri, $result);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function realFileTestDataProvider()
    {
        return array(
            array('/html/index.html', '/html/index.html'),
            array('/html/test.gif', '/html/test.gif'),
            array('/html/test.gif?123345', '/html/test.gif?123345'),
            array('/html/test.gif?q=testset', '/html/test.gif?q=testset'),
            array('/html/symlink.html', '/html/symlink.html'),
            array('/html/failing_test.gif', '/ERROR'),
            array('/html/failing_test.gif?12234', '/ERROR')
        );
    }

    /**
     * Test wrapper for the symlink dataset
     *
     * @return void
     *
     * @dataProvider realFileTestDataProvider
     */
    public function testFileDir($uri, $result)
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '-f',
                'target' => '',
                'flag' => 'L'
            ),
            array(
                'condition' => '(.*)',
                'target' => '/ERROR',
                'flag' => 'L'
            )
        ));

        $this->assertDesiredRewrite($uri, $result);
    }

    /**
     * Test wrapper for the urlencode dataset
     *
     * @return void
     */
    public function testRedirectUri()
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '',
                'target' => '/test/uri',
                'flag' => 'R'
            )
        ));

        $this->assertDesiredRedirect('/html/index.html', 'http://unittest.local:9080/test/uri');
    }

    /**
     * Test wrapper for the urlencode dataset
     *
     * @return void
     */
    public function testRedirectValidStatusCode()
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '',
                'target' => '/test/uri',
                'flag' => 'R=302'
            )
        ));

        $this->assertDesiredRedirect('/html/index.html', 'http://unittest.local:9080/test/uri', 302);
    }


    /**
     * Test wrapper for the urlencode dataset
     *
     * @return void
     */
    public function testRedirectInvalidStatusCode()
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '',
                'target' => '/test/uri',
                'flag' => 'R=500'
            )
        ));

        $this->assertDesiredRedirect('/html/index.html', 'http://unittest.local:9080/test/uri', 301);
    }


    /**
     * Test wrapper for the urlencode dataset
     *
     * @return void
     */
    public function testGeneralRedirect()
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '.*',
                'target' => 'https://www.google.com',
                'flag' => 'R'
            )
        ));

        $this->assertDesiredRedirect('/html/index.html', 'https://www.google.com');
    }

    /**
     * Test wrapper for the urlencode dataset
     *
     * @return void
     */
    public function testConditionedRedirect()
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '/you-will-not-find-me',
                'target' => 'https://www.google.com',
                'flag' => 'R'
            )
        ));

        $this->assertDesiredRedirect('/html/index.html', '/html/index.html');
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function symlinkTestDataProvider()
    {
        return array(
            array('/html/index.html', '/ERROR'),
            array('/html/test.gif', '/ERROR'),
            array('/html/symlink.html', '/html/symlink.html'),
            array('/html/failing_test.gif', '/ERROR')
        );
    }

    /**
     * Test wrapper for the symlink dataset
     *
     * @return void
     *
     * @dataProvider symlinkTestDataProvider
     */
    public function testSymlink($uri, $result)
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '-l',
                'target' => '',
                'flag' => 'L'
            ),
            array(
                'condition' => '(.*)',
                'target' => '/ERROR',
                'flag' => 'L'
            )
        ));

        $this->assertDesiredRewrite($uri, $result);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function urlencodeTestDataProvider()
    {
        return array(
            array('/html', '/ERROR'),
            array('/html/spa ce.txt', '/html/spa ce.txt'),
            array('/html/spa%20ce.txt', '/ERROR')
        );
    }

    /**
     * Test wrapper for the urlencode dataset
     *
     * @return void
     *
     * @dataProvider urlencodeTestDataProvider
     */
    public function testUrlencode($uri, $result)
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '-f',
                'target' => '',
                'flag' => 'L'
            ),
            array(
                'condition' => '(.*)',
                'target' => '/ERROR',
                'flag' => 'L'
            )
        ));

        $this->assertDesiredRewrite($uri, $result);
    }

    /**
     * Test wrapper for the urlencode dataset
     *
     * @return void
     */
    public function testLFlag()
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '.*',
                'target' => '',
                'flag' => 'L'
            ),
            array(
                'condition' => '(.*)',
                'target' => '/ERROR',
                'flag' => ''
            )
        ));

        $this->assertDesiredRewrite('/testUri', '/testUri');
    }

    /**
     * Test wrapper for the urlencode dataset
     *
     * @return void
     */
    public function testRFlag()
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '.*',
                'target' => 'https://www.google.com',
                'flag' => 'R'
            )
        ));

        $this->assertDesiredRedirect('/testUri', 'https://www.google.com');
    }

    /**
     * Test wrapper for the urlencode dataset
     *
     * @return void
     */
    public function testNcFlag()
    {
        $this->prepareRuleset(array(
            array(
                'condition' => 'testuri',
                'target' => '/targetUri',
                'flag' => 'NC,L'
            )
        ));

        $this->assertDesiredRewrite('/testUri', '/targetUri');
    }

    /**
     * Test wrapper for the urlencode dataset
     *
     * @return void
     */
    public function testMixedFlags()
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '.*',
                'target' => 'https://www.google.com',
                'flag' => 'R,L'
            ),
            array(
                'condition' => '.*',
                'target' => '/ERROR',
                'flag' => ''
            )
        ));

        $this->assertDesiredRedirect('/testUri', 'https://www.google.com');
    }

    /**
     * Test wrapper for the urlencode dataset
     *
     * @return void
     */
    public function testServerVars()
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '.*',
                'target' => '$REQUEST_URI@$SERVER_NAME',
                'flag' => 'L'
            )
        ));

        $this->assertDesiredRewrite('/html', '/html/index.html@unittest.local');
    }

    /**
     * Test wrapper for the urlencode dataset
     *
     * @return void
     */
    public function testVarCondition()
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '(unittest).+@$SERVER_NAME',
                'target' => '/$1',
                'flag' => 'L'
            )
        ));

        $this->assertDesiredRewrite('/html', '/unittest');
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function magentoTestDataProvider()
    {
        return array(
            array('/de_de/test-html.html', '/index.php/de_de/test-html.html'),
            array('/de_de/test-category.html?p=123', '/index.php/de_de/test-category.html?p=123'),
            array('/index.php/de_de/test-html.html', '/index.php/de_de/test-html.html')
        );
    }

    /**
     * Test wrapper for the urlencode dataset
     *
     * @return void
     *
     * @dataProvider magentoTestDataProvider
     */
    public function testMagento($uri, $result)
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '-d{OR}-f{OR}-l',
                'target' => '',
                'flag' => 'L'
            ),
            array(
                'condition' => '(.*){AND}!^/index\.php',
                'target' => '/index.php$1',
                'flag' => 'L'
            )
        ));

        $this->assertDesiredRewrite($uri, $result);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function singleBackreferenceTestDataProvider()
    {
        return array(
            array('/html/index.html', '/index'),
            array('/html/test.gif', '/test'),
            array('/html/failing_test', '/html/failing_test')
        );
    }

    /**
     * Test wrapper for the urlencode dataset
     *
     * @return void
     *
     * @dataProvider singleBackreferenceTestDataProvider
     */
    public function testSingleBackreference($uri, $result)
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '/html/(.*)\.',
                'target' => '/$1',
                'flag' => ''
            )
        ));

        $this->assertDesiredRewrite($uri, $result);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function doubleBackreferenceTestDataProvider()
    {
        return array(
            array('/html/index.html', '/html/index'),
            array('/html/test.gif', '/html/test'),
            array('/failing_test', '/failing_test')
        );
    }

    /**
     * Test wrapper for the urlencode dataset
     *
     * @return void
     *
     * @dataProvider doubleBackreferenceTestDataProvider
     */
    public function testDoubleBackreference($uri, $result)
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '/(html)/(.*)\.',
                'target' => '/$1/$2',
                'flag' => ''
            )
        ));

        $this->assertDesiredRewrite($uri, $result);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function mixedBackreferenceTestDataProvider()
    {
        return array(
            array('/html/index.html', '/index/html'),
            array('/html/test.gif', '/test/html'),
            array('/failing_test', '/failing_test')
        );
    }

    /**
     * Test wrapper for the urlencode dataset
     *
     * @return void
     *
     * @dataProvider mixedBackreferenceTestDataProvider
     */
    public function testMixedBackreference($uri, $result)
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '/(html)/(.*)\.',
                'target' => '/$2/$1',
                'flag' => ''
            )
        ));

        $this->assertDesiredRewrite($uri, $result);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function blockingBackreferencesTestDataProvider()
    {
        return array(
            array('/html/index.html', '/html'),
            array('/ppp/test.gif', '/ppp'),
            array('/html/test.gif', '/html'),
        );
    }

    /**
     * Test wrapper for the urlencode dataset
     *
     * @return void
     *
     * @dataProvider blockingBackreferencesTestDataProvider
     */
    public function testBlockingBackreferences($uri, $result)
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '/(ppp){OR}/(html)',
                'target' => '/$1',
                'flag' => 'L'
            )
        ));

        $this->assertDesiredRewrite($uri, $result);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function stackedRulesTestDataProvider()
    {
        return array(
            array('/firstIteration/test.txt', '/finalIteration/test.txt'),
            array('/wontfit/test.txt', '/wontfit/test.txt'),
            array('/toSecondIteration/test.txt', '/finalIteration/test.txt'),
        );
    }

    /**
     * Tests if several rules will handover their result to the next one
     *
     * @return void
     *
     * @dataProvider stackedRulesTestDataProvider
     */
    public function testStackedRules($uri, $result)
    {
        $this->prepareRuleset(array(
            array(
                'condition' => '/firstIteration/(.+)',
                'target' => '/toSecondIteration/$1',
                'flag' => ''
            ),
            array(
                'condition' => '/toSecondIteration/(.+)',
                'target' => '/finalIteration/$1',
                'flag' => ''
            )
        ));

        $this->assertDesiredRewrite($uri, $result);
    }
}
