<?php

namespace AppserverIo\WebServer\Upstreams\Servers;

class DefaultServer
{
    /**
     * Constructor
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
     * Holds the host param value
     * 
     * @var string
     */
    protected $host;
    
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
     * Returns the host
     * 
     * @return string
     */
    public function getHost()
    {
        return $this->host;
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
