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
 * <TODO CLASS DESCRIPTION>
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
    protected $configPath;

    protected $mTime;

    protected $directives;

    public function __construct($configPath, $directives)
    {
        if (!is_readable($configPath)) {

            throw new \InvalidArgumentException('Could not read from config file ' . $configPath);
        }

        $this->configPath = $configPath;
        $this->mTime = filemtime($configPath);
        $this->directives = $directives;
    }

    public function getConfigPath()
    {
        return $this->configPath;
    }

    public function getMTime()
    {
        return $this->mTime;
    }

    public function getDirectives()
    {
        return $this->directives;
    }

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
     * <TODO FUNCTION DESCRIPTION>
     *
     * @param null $type
     * @param array $args
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
