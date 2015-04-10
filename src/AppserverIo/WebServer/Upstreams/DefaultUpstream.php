<?php

/**
 * \AppserverIo\WebServer\Upstreams\DefaultUpstream
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */

namespace AppserverIo\WebServer\Upstreams;

use AppserverIo\Server\Interfaces\UpstreamInterface;

/**
 * A simple default upstream implementation
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class DefaultUpstream implements UpstreamInterface
{
    /**
     * Holds a collection of servers defined for upstream
     *
     * @var array
     */
    public $servers;
    
    /**
     * Injects the server instance for the upstream to use
     *
     * @param array $servers The servers to inject
     *
     * @return void
     */
    public function injectServers(array $servers)
    {
        $this->servers = $servers;
    }
    
    /**
     * Returns the next server preserved by upstreams logic implementation.
     * For the default upstream we'll return a random server
     *
     * @param string $hash Any hash to make use of by identificate certain servers
     *
     * @return \AppserverIo\Server\Interfaces\UpstreamServerInterface
     */
    public function findServer($hash)
    {
        $serverNames = array_keys($this->servers);
        return $this->servers[$serverNames[rand(0, count($this->servers)-1)]];
    }
}
