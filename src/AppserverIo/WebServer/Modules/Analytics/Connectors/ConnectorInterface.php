<?php

/**
 * \AppserverIo\WebServer\Modules\Analytics\Connectors\ConnectorInterface
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
 * @copyright 2014 TechDivision GmbH - <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */

namespace AppserverIo\WebServer\Modules\Analytics\Connectors;

use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Psr\HttpMessage\RequestInterface;
use AppserverIo\Psr\HttpMessage\ResponseInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;

/**
 * Interface describing a connector to a certain analysis service
 *
 * @author    Bernhard Wick <bw@appserver.io>
 * @copyright 2014 TechDivision GmbH - <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
interface ConnectorInterface
{

    /**
     * Default constructor
     *
     * @param \AppserverIo\Server\Interfaces\ServerContextInterface $serverContext The server's context instance
     */
    public function __construct(ServerContextInterface $serverContext);

    /**
     * Will call the service the connector is used to communicate with
     *
     * @param \AppserverIo\Psr\HttpMessage\RequestInterface          $request        A request object
     * @param \AppserverIo\Psr\HttpMessage\ResponseInterface         $response       A response object
     * @param \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext A requests context instance
     *
     * @return null
     */
    public function call(RequestInterface $request, ResponseInterface $response, RequestContextInterface $requestContext);

    /**
     * Will return the name of the service the connector is used for
     *
     * @return string
     */
    public function getServiceName();

    /**
     * Used to initialize the connector based on additional parameters passed to it
     *
     * @param array $params The additional parameters for this connector
     *
     * @return null
     */
    public function init(array $params);
}
