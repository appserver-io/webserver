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
 * @subpackage Modules
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\WebServer\ConfigParser;

/**
 * TechDivision\WebServer\ConfigParser\Config
 *
 * Base class which will hold all configuration directives
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class Config
{
    /**
     * The path to the file which this configuration is based on
     *
     * @var string $configPath
     */
    protected $configPath;

    /**
     * The timestamp of the config file at the time we parsed it
     *
     * @var integer $mTime
     */
    protected $mTime;

    /**
     * The directives contained in the config file
     *
     * @var array $directives
     */
    protected $directives;

    /**
     * Default constructor
     *
     * @param string $configPath The path to the file which this configuration is based on
     * @param array  $directives The directives contained in the config file
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($configPath, $directives)
    {
        if (!is_readable($configPath)) {

            throw new \InvalidArgumentException('Could not read from config file ' . $configPath);
        }

        $this->configPath = $configPath;
        $this->mTime = filemtime($configPath);
        $this->directives = $directives;
    }

    /**
     * Getter for the config file's path
     *
     * @return string
     */
    public function getConfigPath()
    {
        return $this->configPath;
    }

    /**
     * Getter for the config file's change time
     *
     * @return string
     */
    public function getMTime()
    {
        return $this->mTime;
    }

    /**
     * Getter for the directives array
     *
     * @return array
     */
    public function getDirectives()
    {
        return $this->directives;
    }

    /**
     * Will return all directives of a certain type. This "type" is the qualified class name of a directive
     *
     * @param string $type Directive type as a directive class's qualified name
     *
     * @return array
     */
    public function getDirectivesByType($type)
    {
        $directives = array();
        foreach ($this->directives as $directive) {

            if (is_a($directive, $type)) {

                $directives[] = $directive;
            }
        }

        return $directives;
    }

    /**
     * Will return all backreferences for all directives within this config. As some backreferences need resolved
     * directives to be built, we can get them only for certain types/directives.
     *
     * @param string|null $type Directive type as a directive class's qualified name
     * @param array       $args Arguments which will be passed to the directive's getBackreferences method
     *
     * @return array
     */
    public function getBackreferences($type = null, $args = array())
    {
        $backreferences = array();

        // Do we have to get backreferences for a certain type only?
        if (is_null($type)) {
            $directives = $this->directives;

        } else {

            $directives = $this->getDirectivesByType($type);
        }

        foreach ($directives as $directive) {

            if (method_exists($directive, 'getBackreferences')) {

                $backreferences = array_merge(
                    $backreferences,
                    call_user_func_array(
                        array($directive, 'getBackreferences'),
                        array_merge(array(count($backreferences)), $args)
                    )
                );
            }
        }

        return $backreferences;
    }
}
