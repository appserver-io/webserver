<?php

/**
 * \AppserverIo\WebServer\Upstreams\Servers\DefaultServer
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

namespace AppserverIo\WebServer\Upstreams\Servers;

use AppserverIo\Server\Interfaces\UpstreamServerInterface;

/**
 * A simple default upstream server implementation
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class DefaultServer implements UpstreamServerInterface
{
    /**
     * Constructor
     *
     * @param array $params The params for the server to be constructed for
     */
    public function __construct(array $params)
    {
        // pre init var by given params dynamically
        foreach (array_keys(get_object_vars($this)) as $varName) {
            if (isset($params[$varName])) {
                $this->{$varName} = $params[$varName];
            }
        }
    }
    
    /**
     * Holds the address param value
     *
     * @var string
     */
    protected $address;
    
    /**
     * Holds the port param value
     *
     * @var string
     */
    protected $port;
    
    /**
     * Holds the weight param value
     *
     * @var int
     */
    protected $weight;
    
    /**
     * Holds the max fails param value
     *
     * @var int
     */
    protected $maxFails;
    
    /**
     * Holds the fail timeout param value
     *
     * @var int
     */
    protected $failTimeout;
    
    /**
     * Holds the backup param value
     *
     * @var bool
     */
    protected $backup;
    
    /**
     * Holds the down param value
     *
     * @var bool
     */
    protected $down;
    
    /**
     * Holds the max conns param value
     *
     * @var int
     */
    protected $maxConns;
    
    /**
     * Holds the resolve param value
     *
     * @var bool
     */
    protected $resolve;

    /**
     * Returns the address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }
    
    /**
     * Returns the port
     *
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Returns the weight
     *
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Returns the max fails
     *
     * @return int
     */
    public function getMaxFails()
    {
        return $this->maxFails;
    }

    /**
     * Returns the fail timeout
     *
     * @return int
     */
    public function getFailTimeout()
    {
        return $this->failTimeout;
    }

    /**
     * Returns the backup flag
     *
     * @return bool
     */
    public function isBackup()
    {
        return $this->backup;
    }

    /**
     * Returns the down flag
     *
     * @return bool
     */
    public function isDown()
    {
        return $this->down;
    }

    /**
     * Returns the max conns
     *
     * @return int
     */
    public function getMaxConns()
    {
        return $this->maxConns;
    }

    /**
     * Returns the resolve flag
     *
     * @return bool
     */
    public function shouldResolve()
    {
        return $this->resolve;
    }
}
