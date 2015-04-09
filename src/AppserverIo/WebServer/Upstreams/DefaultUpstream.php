<?php

namespace AppserverIo\WebServer\Upstreams;

class DefaultUpstream
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
     * @param array $servers
     */
    public function injectServers(array $servers)
    {
        $this->servers = $servers;
    }
    
    public function findServer($hash)
    {
        $serverNames = array_keys($this->servers);
        return $this->servers[$serverNames[rand(0,1)]];
    }
}