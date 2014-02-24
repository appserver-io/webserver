<?php

namespace TechDivision\WebServer;

use TechDivision\Socket\SocketInterface;

interface ConnectionInterface
{

    /**
     * Negotiates the connection with the connected client in a proper way the given
     * protocol type and version expects.
     *
     * @return string The buffer
     */
    public function negotiate();

    /**
     * Return's the socket implementation
     *
     * @return \TechDivision\Socket\SocketInterface
     */
    public function getSocket();

}

