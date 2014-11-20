<?php

/**
 * \AppserverIo\WebServer\Interfaces\ModuleParserInterface
 *
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
 * @subpackage Interfaces
 * @author     Bernhard Wick <bw@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */

namespace AppserverIo\WebServer\Interfaces;

/**
 * ModuleParserInterface
 *
 * Interface for module parsers
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Interfaces
 * @author     Bernhard Wick <bw@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */
interface ModuleParserInterface
{
    /**
     * Will return the type of the configuration as the parser might encounter different configuration types
     *
     * @return string
     */
    public function getConfigType();

    /**
     * Will return a complete configuration parsed from the provided file
     *
     * @param string $documentRoot The servers document root as a fallback
     * @param string $requestedUri The requested uri
     *
     * @return \AppserverIo\WebServer\Modules\Parser\Config
     */
    public function getConfigForFile($documentRoot, $requestedUri);
}
