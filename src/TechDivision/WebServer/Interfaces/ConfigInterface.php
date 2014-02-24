<?php

namespace TechDivision\WebServer\Interfaces;

interface ConfigInterface
{

    public function getServerListen();

    public function getServerPort();


    public function getSocketClassName();

    public function getParserClassName();

    public function getConnectionClassName();

    public function getRequestClassName();

    public function getResponseClassName();

}
