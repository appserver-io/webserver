<?php

/**
 * \AppserverIo\Server\Exceptions\ModuleException
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
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */

namespace AppserverIo\WebServer\Interfaces;

use AppserverIo\Server\Exceptions\ModuleException;

/**
 * Interface RewriteMapperInterface
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */
interface RewriteMapperInterface
{

    /**
     * Look's up a target url for given request url
     *
     * @param string $requestUri The requested url without query params
     *
     * @return null|string
     */
    public function lookup($requestUri);
}
