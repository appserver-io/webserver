<?php

/**
 * \AppserverIo\WebServer\Mock\MockCondition
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
 * @subpackage Mock
 * @author     Bernhard Wick <bw@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */

namespace AppserverIo\WebServer\Mock;

use AppserverIo\Server\Contexts\RequestContext;

/**
 * Class MockFaultyRequestContext
 *
 * Mock class to be used for exception testing
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Mock
 * @author     Bernhard Wick <bw@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */
class MockFaultyRequestContext extends RequestContext
{
    /**
     * Overridden method to test exception handling
     *
     * @param string $serverVar The server var to get value for
     *
     * @return void
     * @throws \Exception
     */
    public function getServerVar($serverVar)
    {
        throw new \Exception();
    }
}
