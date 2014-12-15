<?php

/**
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
 * @copyright  2014 TechDivision GmbH - <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io/
 */

namespace AppserverIo\WebServer\Modules\Analytics\Connectors\UniversalAnalytics;

use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\WebServer\Modules\Analytics\Connectors\ConnectorInterface;

/**
 * AppserverIo\WebServer\Modules\Analytics\Connectors\UniversalAnalytics\MeasurementProtocol
 *
 * Connector for google's "Universal Analytics" measurement protocol API
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Modules
 * @author     Bernhard Wick <bw@appserver.io>
 * @copyright  2014 TechDivision GmbH - <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io/
 */
class MeasurementProtocol implements ConnectorInterface
{

    /**
     * Base URL at which the measurement protocol service can be reached
     *
     * @var string SERVICE_BASE_URL
     */
    const SERVICE_BASE_URL = 'http://www.google-analytics.com/collect';

    /**
     * Name of the service the connector connects to
     *
     * @var string SERVICE_NAME
     */
    const SERVICE_NAME = 'UniversalAnalytics/MeasurementProtocol';

    /**
     * Very basic user agent we will use for our requests
     *
     * @var string USER_AGENT
     */
    const USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) Gecko Firefox/5.0';

    /**
     * Default parameters to use with every service call
     *
     * @var array $defaultParameter
     */
    protected $defaultParameters = array(
        'v' => 1
    );

    /**
     * Holds parameters passed to the connector
     *
     * @var array $parameters
     */
    protected $parameters = array();

    /**
     * Array for mapping incoming parameters to the measurement protocol API
     *
     * @var array $parameterMapping
     */
    protected $parameterMapping = array(
        'os' => 'aid',
        'version' => 'av',
        'dist' => 'aiid'
    );

    /**
     * Will call for the measurement protocol endpoint
     *
     * @param \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext A requests context instance
     *
     * @return null
     */
    public function call(RequestContextInterface $requestContext)
    {
        // we will work with the query string which we have to parse first
        $queryString = $requestContext->getServerVar(ServerVars::QUERY_STRING);

        $parameters = array();
        foreach (explode('&', $queryString) as $paramPair) {

            list($param, $value) = explode('=', $paramPair);

            // we might have to map our parameters
            if (isset($this->parameterMapping[$param])) {

                $param = $this->parameterMapping[$param];
            }
            $parameters[$param] = urldecode($value);
        }

        // merge default and configured parameters into our list
        $parameters = array_merge($parameters, $this->defaultParameters, $this->parameters);

        // do a CURL call to the service
        $ch = curl_init(self::SERVICE_BASE_URL);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);

        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Will return the name of the service the connector is used for
     *
     * @return string
     */
    public function getServiceName()
    {
        return self::SERVICE_NAME;
    }

    /**
     * Used to initialize the connector based on additional parameters passed to it
     *
     * @param array $params The additional parameters for this connector
     *
     * @return null
     */
    public function init(array $params)
    {
        $this->parameters = $params;
        $this->parameters['cid'] = uniqid();
    }
}
