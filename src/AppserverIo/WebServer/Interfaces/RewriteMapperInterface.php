<?php

/**
 * \AppserverIo\WebServer\Interfaces\RewriteMapperInterface
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link https://github.com/appserver-io/webserver
 * @link http://www.appserver.io
 */
namespace AppserverIo\WebServer\Interfaces;

/**
 * Interface RewriteMapperInterface
 *
 * @author Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link https://github.com/appserver-io/webserver
 * @link http://www.appserver.io
 */
interface RewriteMapperInterface
{

    /**
     * Looks up a target url for given request url
     *
     * @param string $requestUri The requested url without query params
     *
     * @return null|string
     */
    public function lookup($requestUri);
}
