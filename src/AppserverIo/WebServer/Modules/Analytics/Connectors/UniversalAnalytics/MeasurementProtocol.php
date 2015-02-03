<?php

/**
 * AppserverIo\WebServer\Modules\Analytics\Connectors\UniversalAnalytics\MeasurementProtocol
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

namespace AppserverIo\WebServer\Modules\Analytics\Connectors\UniversalAnalytics;

use AppserverIo\Http\HttpProtocol;
use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\WebServer\Modules\Analytics\Connectors\ConnectorInterface;
use AppserverIo\Psr\HttpMessage\RequestInterface;
use Rhumsaa\Uuid\Uuid;
use AppserverIo\Psr\HttpMessage\ResponseInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\Logger\LoggerUtils;

/**
 * Connector for google's "Universal Analytics" measurement protocol API
 *
 * @author    Bernhard Wick <bw@appserver.io>
 * @copyright 2014 TechDivision GmbH - <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class MeasurementProtocol implements ConnectorInterface
{

    /**
     * Default hit type we will use if there is none given
     *
     * @var string DEFAULT_HIT_TYPE
     */
    const DEFAULT_HIT_TYPE = 'event';

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
    protected $defaultParameters = array();

    /**
     * Holds parameters passed to the connector
     *
     * @var array $parameters
     */
    protected $parameters = array();

    /**
     * Holds required parameters for the different hit types
     *
     * @var array $requiredParameters
     *
     * @see https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide
     * @see https://developers.google.com/analytics/devguides/collection/protocol/v1/parameters
     */
    protected $requiredParameters = array();

    /**
     * The server's context instance which we preserve for later use
     *
     * @var \AppserverIo\Server\Interfaces\ServerContextInterface $serverContext $serverContext
     */
    protected $serverContext;

    /**
     * The hit types we do support
     *
     * @var array $supportedHitTypes
     *
     * @see https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide
     */
    protected $supportedHitTypes = array(
        'event',
        'screenview',
        'item',
        'social'
    );

    /**
     * Default constructor
     *
     * @param \AppserverIo\Server\Interfaces\ServerContextInterface $serverContext The server's context instance
     */
    public function __construct(ServerContextInterface $serverContext)
    {
        $this->serverContext = $serverContext;

        // init the required parameters
        $this->requiredParameters = array(
            'event' => array(
                'ec',
                'ea'
            ),
            'screenview' => array(),
            'item' => array(
                'ti',
                'in'
            ),
            'social' => array(
                'sn',
                'sa',
                'st'
            )
        );

        // set our default parameters
        $this->defaultParameters = array(
            'v' => 1
        );
    }

    /**
     * Will call for the measurement protocol endpoint
     *
     * @param \AppserverIo\Psr\HttpMessage\RequestInterface          $request        A request object
     * @param \AppserverIo\Psr\HttpMessage\ResponseInterface         $response       A response object
     * @param \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext A requests context instance
     *
     * @return null
     */
    public function call(RequestInterface $request, ResponseInterface $response, RequestContextInterface $requestContext)
    {
        // merge default and configured parameters into our list
        $parameters = array_merge($this->defaultParameters, $this->parameters);

        // we want the request to be like it came from the same host, so we will reuse part of it
        $parameters['ua'] = $request->getHeader(HttpProtocol::HEADER_USER_AGENT);
        $parameters['uip'] = $requestContext->getServerVar(ServerVars::REMOTE_ADDR);

        // the client will be a random UUID, at least if we do not get a matching cookie
        if ($request->hasHeader(HttpProtocol::HEADER_COOKIE)) {
            $cookie = $request->getHeader(HttpProtocol::HEADER_COOKIE);
            $matches = array();
            preg_match('/_ga=GA[0-9]\.[0-9]\.(.+)/', $cookie, $matches);
            if (isset($matches[1])) {
                $parameters['cid'] = $matches[1];
            }
        }
        if (! isset($parameters['cid'])) {
            $uuid4 = Uuid::uuid4();
            $parameters['cid'] = $uuid4->toString();
        }

        // make the actual call
        $this->sendToService($parameters);
    }

    /**
     * Will init the parameter mappings for our hit types
     *
     * @param array $params The parameters to check for requirements
     *
     * @return null
     */
    protected function checkInputParameters(array $params)
    {
        // we only check if we know the requirements
        if (isset($this->requiredParameters[$params['t']])) {
            foreach ($this->requiredParameters[$params['t']] as $requirement) {
                if (! isset($params[$requirement])) {
                    // do the logging, preferably by one of our loggers
                    $message = 'We miss the required parameter "%s", you might not get proper analytics!';
                    if ($this->serverContext->hasLogger(LoggerUtils::SYSTEM)) {
                        $logger = $this->serverContext->getLogger(LoggerUtils::SYSTEM);
                        $logger->warning(sprintf($message, $requirement));
                    } else {
                        error_log(sprintf($message, $requirement));
                    }
                }
            }
        }
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

        // first of all we have to check for the hit type
        $supportedHitTypes = array_flip($this->supportedHitTypes);
        if (! isset($params['t'])) {
            $params['t'] = self::DEFAULT_HIT_TYPE;
        } elseif (! isset($supportedHitTypes[$params['t']])) {
            throw new \InvalidArgumentException(sprintf('Unsupported hit type "%s", please check configuration and module implementation.', $params['t']));
        }

        // we want to check our requirements
        $this->checkInputParameters($params);

        // set some params we already know
        $this->parameters = $params;
    }

    /**
     * Will send gathered parameters to the service URL using a POST request
     *
     * @param array $parameters The parameters to send
     *
     * @return null
     */
    protected function sendToService(array $parameters)
    {
        // make a CURL call to the service
        $ch = curl_init(self::SERVICE_BASE_URL);

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, count($parameters));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);

        curl_exec($ch);
        curl_close($ch);
    }
}
