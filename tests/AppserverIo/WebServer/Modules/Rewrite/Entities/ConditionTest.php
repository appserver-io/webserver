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

use AppserverIo\WebServer\Modules\Rewrite\Entities\Condition;
use AppserverIo\WebServer\Mock\MockCondition;
use AppserverIo\WebServer\Modules\RewriteModule;

/**
 * Class ConditionTest
 *
 * Basic test class for the Condition class.
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Modules
 * @author     Bernhard Wick <bw@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */
class ConditionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the constructor
     *
     * @return void
     *
     * @depends testGetOperand
     * @depends testGetType
     */
    public function testConstruct()
    {
        // First something we know will produce a regex
        $condition = new MockCondition('test', '.*');
        $this->assertEquals('regex', $condition->getType());
        $this->assertEquals('test', $condition->getOperand());
    }

    /**
     * Tests the constructor's behaviour on an invalid modifier
     *
     * @return void
     */
    public function testConstructInvalidModifier()
    {
        // We should get an \InvalidArgumentException
        $this->setExpectedException('InvalidArgumentException');

        // This modifier does not exist at all
        new Condition('test', '.*', '[IDoNotExist]');
    }

    /**
     * Tests the constructor's ability to cut the action when needed
     *
     * @return void
     */
    public function testConstructCorrectActionCut()
    {
        // Give it an action which requires cutting it (e.g. string comparison)
        $condition = new MockCondition('test', '<aTest');
        $this->assertEquals('<', $condition->getAction());
        $this->assertEquals('aTest', $condition->getAdditionalOperand());
    }

    /**
     * Tests the getOperand() method
     *
     * @return void
     */
    public function testGetOperand()
    {
        $condition = new Condition('test', '.*');
        $this->assertEquals('test', $condition->getOperand());
    }

    /**
     * Tests the getModifier() method
     *
     * @return void
     */
    public function testGetModifier()
    {
        $condition = new Condition('test', '.*');
        $this->assertEquals('', $condition->getModifier());
        $condition = new Condition('test', '.*', '[NC]');
        $this->assertEquals('[NC]', $condition->getModifier());
    }

    /**
     * Tests the getType() method
     *
     * @return void
     */
    public function testGetType()
    {
        $condition = new Condition('test', '.*');
        $this->assertEquals('regex', $condition->getType());
        $condition = new Condition(__DIR__, '-d');
        $this->assertEquals('check', $condition->getType());
    }

    /**
     * Tests the resolve() method
     *
     * @return void
     *
     * @depends testGetOperand
     */
    public function testResolve()
    {
        // Get some reasonable backreferences
        $backreferences = array('$BACKREF' => 'This', '$REFBACK' => 'That');
        // Get a condition that uses them
        $condition = new MockCondition('IAmUsing$BACKREFAnd$REFBACKAsWell', '.+$BACKREFAnd(.+)');

        // Do the thing
        $condition->resolve($backreferences);

        // Assert there was something resolved
        $this->assertEquals('IAmUsingThisAndThatAsWell', $condition->getOperand());
        $this->assertEquals('.+ThisAnd(.+)', $condition->getAdditionalOperand());
    }

    /**
     * Test for a path through the matches() method
     *
     * @return void
     */
    public function testMatchesIsUsedFile()
    {
        // This should succeed
        $condition = new Condition(__FILE__, '-s');
        $this->assertTrue($condition->matches());

        // This should not
        $condition = new Condition(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '_files' .
            DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'emptyFile',
            '-s'
        );
        $this->assertFalse($condition->matches());
    }

    /**
     * Test for a path through the matches() method
     *
     * @return void
     */
    public function testMatchesStringEquals()
    {
        // This should succeed
        $condition = new Condition('test', '=test');
        $this->assertTrue($condition->matches());

        // This should not
        $condition = new Condition('test', '=testTest');
        $this->assertFalse($condition->matches());
    }

    /**
     * Test for a path through the matches() method
     *
     * @return void
     */
    public function testMatchesStringLessThan()
    {
        // This should succeed
        $condition = new Condition('test', '<zzzz');
        $this->assertTrue($condition->matches());

        // This should not
        $condition = new Condition('test', '<a');
        $this->assertFalse($condition->matches());
    }

    /**
     * Test for a path through the matches() method
     *
     * @return void
     */
    public function testMatchesStringGreaterThan()
    {
        // This should succeed
        $condition = new Condition('test', '>a');
        $this->assertTrue($condition->matches());

        // This should not
        $condition = new Condition('test', '>zzzz');
        $this->assertFalse($condition->matches());
    }

    /**
     * Test for a path through the matches() method
     *
     * @return void
     */
    public function testMatchesIsExecutable()
    {
        $htmlBaseDir = __DIR__ .
            DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '_files' .
            DIRECTORY_SEPARATOR . 'modules' .
            DIRECTORY_SEPARATOR . RewriteModule::MODULE_NAME . DIRECTORY_SEPARATOR . 'html';

        // This should succeed
        $condition = new Condition($htmlBaseDir, '-x');

        $this->assertTrue($condition->matches());

        // This should not
        $condition = new Condition($htmlBaseDir . DIRECTORY_SEPARATOR . 'emptyFile', '-x');
        $this->assertFalse($condition->matches());
    }

    /**
     * Test the getBackreferences() method
     *
     * @return void
     */
    public function testGetBackreferences()
    {
        // First case, the type is NOT "regex"
        $condition = new Condition(__FILE__, '-f');
        $this->assertEmpty($condition->getBackreferences());

        // Now with type regex and ONE backreference in it
        $condition = new Condition('testBACKREFtest', '(BACKREF)');
        $backreferences = $condition->getBackreferences();
        $this->assertEquals(1, count($backreferences));
        $element = array_pop($backreferences);
        $this->assertEquals('BACKREF', $element);
        $arrayKeys = array_keys($condition->getBackreferences());
        $this->assertEquals('$1', array_pop($arrayKeys));

        // Now try with two of them
        $condition = new Condition('testBACKREFtest', '(BACKREF).+(t)');
        $this->assertEquals(2, count($condition->getBackreferences()));
        $this->assertEquals('BACKREF', $condition->getBackreferences()['$1']);
        $this->assertEquals('t', $condition->getBackreferences()['$2']);
    }
}
