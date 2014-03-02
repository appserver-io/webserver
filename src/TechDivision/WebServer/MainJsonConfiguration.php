<?php
/**
 * \TechDivision\WebServer\MainJsonConfiguration
 *
 * PHP version 5
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/TechDivision_WebServer
 */

namespace TechDivision\WebServer;

/**
 * Class MainJsonConfiguration
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/TechDivision_WebServer
 */
class MainJsonConfiguration
{
    /**
     * Hold's the data instance read by json file
     *
     * @var \stdClass
     */
    protected $data;

    /**
     * Constructs the config by given filename
     *
     * @param string $filename The filename to init the config with
     */
    public function __construct($filename)
    {
        $this->data = json_decode(file_get_contents($filename));
    }

    /**
     * Return's an array of server configs
     *
     * @return array
     */
    public function getServerConfigs()
    {
        $serverConfigurations = array();
        foreach ($this->data->servers as $serverConfig) {
            $serverConfigurations[] = new ServerConfiguration($serverConfig);
        }
        return $serverConfigurations;
    }
}
