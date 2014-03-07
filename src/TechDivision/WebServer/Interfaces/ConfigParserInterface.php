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
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Interfaces
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\WebServer\Interfaces;

/**
 * TechDivision\WebServer\Interfaces\ConfigParserInterface
 *
 * <TODO CLASS DESCRIPTION>
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Interfaces
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
interface ConfigParserInterface
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
     * @return \TechDivision\WebServer\ConfigParser\Config
     */
    public function getConfigForFile($documentRoot, $requestedUri);
}
