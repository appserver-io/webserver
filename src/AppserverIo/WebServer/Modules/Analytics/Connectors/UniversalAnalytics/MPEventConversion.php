<?php

/**
 * \AppserverIo\WebServer\Modules\Analytics\Connectors\UniversalAnalytics\MPEventConversion
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
class MPEventConversion extends MeasurementProtocol
{

    /**
     * String which identifies the parameter to set if a cookie is present
     *
     * @var string COOKIE_PRESENT
     */
    const COOKIE_PRESENT = 'known';

    /**
     * String which identifies the parameter to set if a cookie is absent
     *
     * @var string COOKIE_NOT_PRESENT
     */
    const COOKIE_NOT_PRESENT = 'foreign';

    /**
     * Default hit type we will use if there is none given
     *
     * @var string DEFAULT_HIT_TYPE
     */
    const DEFAULT_HIT_TYPE = 'event';

    /**
     * Delimiter separating the parameter and the situational identifier
     *
     * @var string IDENTIFICATION_DELIMITER
     */
    const IDENTIFICATION_DELIMITER = ':';

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
            // the user is known to us
            $cookie = $request->getHeader(HttpProtocol::HEADER_COOKIE);
            $matches = array();
            preg_match('/_ga=GA[0-9]\.[0-9]\.(.+)/', $cookie, $matches);
            if (isset($matches[1])) {
                $parameters['cid'] = $matches[1];

                // remove the cookie to avoid additional calls
                $response->removeCookie('_ga');

                // filter the parameters for a known cookie
                $parameters = $this->filterParameters($parameters, self::COOKIE_PRESENT);
            }
        }

        // if there is no known client id we will set one randomly
        if (! isset($parameters['cid'])) {
            $uuid4 = Uuid::uuid4();
            $parameters['cid'] = $uuid4->toString();

            // filter the parameters for usage without cookie
            $parameters = $this->filterParameters($parameters, self::COOKIE_NOT_PRESENT);
        }

        // make the actual call
        $this->sendToService($parameters);
    }

    /**
     * Will init the parameter mappings for our hit types
     *
     * @param array $parameters The parameters to check for requirements
     *
     * @return null
     */
    protected function checkInputParameters(array $parameters)
    {
        // we only server "event" hit types here, so fail if there is something different, assuming that the params
        // will not match too
        if ($parameters['t'] !== self::DEFAULT_HIT_TYPE) {
            throw new \InvalidArgumentException(sprintf('This connector does only support the "event" hit type. Hit type "%s" given', $parameters['t']));
        }

        // check the parent too
        parent::checkInputParameters($parameters);
    }

    /**
     * Will filter the parameters by deleting parameters which are not identified and normalize the ones which are
     *
     * @param array  $parameters          The parameters to check for requirements
     * @param string $parameterIdentifier String which is used to identify the params to keep
     *
     * @return array The filtered parameters
     */
    protected function filterParameters(array $parameters, $parameterIdentifier)
    {
        foreach ($parameters as $key => $parameter) {
            // unset the ones with identification delimiter
            if (strpos($key, self::IDENTIFICATION_DELIMITER) !== false) {
                unset($parameters[$key]);
            }

            // re-set the ones which have been identified as useful
            if (strpos($key, $parameterIdentifier) !== false) {
                $parameters[strstr($key, self::IDENTIFICATION_DELIMITER, true)] = $parameter;
            }
        }

        return $parameters;
    }
}
