<?php

/**
 * \AppserverIo\WebServer\Modules\Rewrite\Entities\ConditionTest
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
 * @author     Bernhard Wick <bw@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */

namespace AppserverIo\WebServer\Modules\Rewrite\Entities;

use AppserverIo\WebServer\Modules\Rewrite\Entities\Rule;
use AppserverIo\WebServer\Mock\MockRequestContext;
use AppserverIo\WebServer\Mock\MockRule;
use AppserverIo\Http\HttpResponse;
use AppserverIo\Server\Dictionaries\ServerVars;

/**
 * Class RuleTest
 *
 * Basic test class for the Rule class.
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Modules
 * @author     Bernhard Wick <bw@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */
class RuleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test for the getConditionString() method
     *
     * @return void
     */
    public function testGetConditionString()
    {
        $rule = new Rule('^_Resources/.*', null, '');
        $this->assertEquals('^_Resources/.*', $rule->getConditionString());
    }

    /**
     * Test for the getFlagString() method
     *
     * @return void
     */
    public function testGetFlagString()
    {
        $rule = new Rule('^_Resources/.*', null, 'L,R');
        $this->assertEquals('L,R', $rule->getFlagString());
    }

    /**
     * Test for the getTarget() method
     *
     * @return void
     */
    public function testGetTarget()
    {
        $rule = new Rule('^_Resources/.*', '/index.php', '');
        $this->assertEquals('/index.php', $rule->getTarget());
    }

    /**
     * Test for the SortFlags() method
     *
     * @return void
     */
    public function testSortFlags()
    {
        $rule = new MockRule('^_Resources/.*', '/index.php', '');
        $this->assertEquals(array('M' => '$X_REQUEST_URI'), $rule->sortFlags('M=$X_REQUEST_URI'));
        $this->assertEquals(array('L' => null), $rule->sortFlags('L'));
        $this->assertEquals(array('L' => null, 'R' => null), $rule->sortFlags('L,R'));
    }

    /**
     * Test for a path through the apply() method
     *
     * @return void
     */
    public function testApplyWithAbsoluteTarget()
    {
        // Get the objects we need
        $rule = new MockRule('.*', __FILE__, '');
        $mockRequestContext = new MockRequestContext();
        $response = new HttpResponse();

        // Do the thing
        $result = $rule->apply($mockRequestContext, $response, array());
        $this->assertEquals('absolute', $rule->getType());
        $this->assertTrue($mockRequestContext->hasServerVar(ServerVars::REQUEST_FILENAME));
        $this->assertEquals(__FILE__, $mockRequestContext->getServerVar(ServerVars::REQUEST_FILENAME));
        $this->assertTrue($result);
    }

    /**
     * Test for a path through the apply() method
     *
     * @return void
     *
     * @depends testSortFlags
     */
    public function testApplyWithMismatchedMap()
    {
        // Get the objects we need
        $rule = new MockRule('.*', array(), 'L,M=$X_REQUEST_URI');
        $mockRequestContext = new MockRequestContext();
        $response = new HttpResponse();

        // Do the thing
        $rule->apply($mockRequestContext, $response, array());
        $this->assertEquals('', $rule->getTarget());
        $this->assertFalse(array_key_exists('L', $rule->getSortedFlags()));
    }

    /**
     * Test for a path through the apply() method
     *
     * @return void
     *
     * @depends testSortFlags
     */
    public function testApplyWithMatchingMap()
    {
        // Get the objects we need
        $rule = new MockRule('.*', array('test' => 'testTarget'), 'M=test');
        $mockRequestContext = new MockRequestContext();
        $response = new HttpResponse();

        // Do the thing
        $rule->apply($mockRequestContext, $response, array());
        $this->assertEquals('/testTarget', $rule->getTarget());
    }

    /**
     * Test for a path through the apply() method
     *
     * @return void
     *
     * @depends testSortFlags
     */
    public function testApplyWithBackrefMap()
    {
        // Get the objects we need
        $rule = new MockRule('.*', array('testBert' => 'testTarget'), 'M=test$BACKREF');
        $mockRequestContext = new MockRequestContext();
        $response = new HttpResponse();

        // Do the thing
        $rule->apply($mockRequestContext, $response, array('$BACKREF' => 'Bert'));
        $this->assertEquals('/testTarget', $rule->getTarget());
    }
}
