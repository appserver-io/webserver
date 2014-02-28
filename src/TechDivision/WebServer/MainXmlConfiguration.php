<?php
/**
 * \TechDivision\WebServer\MainXmlConfiguration
 *
 * PHP version 5
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace TechDivision\WebServer;

use TechDivision\WebServer\ServerXmlConfiguration;

/**
 * Class MainXmlConfiguration
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class MainXmlConfiguration
{
    protected $xml;

    public function __construct($filename)
    {
        $this->xml = simplexml_load_file($filename);
    }

    public function getServerConfigs()
    {
        $serverConfigurations = array();
        foreach ($this->xml->server as $serverConfig) {
            $serverConfigurations[] = new ServerXmlConfiguration($serverConfig);
        }
        return $serverConfigurations;
    }

}