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
 * @subpackage ConfigParser
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\WebServer\ConfigParser\Directives;

use TechDivision\WebServer\Interfaces\DirectiveInterface;

/**
 * TechDivision\WebServer\ConfigParser\Directives\RewriteEngine
 *
 * <TODO CLASS DESCRIPTION>
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage ConfigParser
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class RewriteEngine implements DirectiveInterface
{
    /**
     * Status to set the rewrite engine to. Can be either "on" or "off".
     *
     * @var string $status
     */
    protected $status;

    /**
     * @param string|null $status
     */
    public function __construct($status = null)
    {
        $this->status = $status;
    }

    /**
     * <TODO FUNCTION DESCRIPTION>
     *
     * @return null|string
     */
    public function __tostring()
    {
        if (is_null($this->getStatus())) {

            return '';
        }

        return $this->getStatus();
    }

    /**
     * <TODO FUNCTION DESCRIPTION>
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * <TODO FUNCTION DESCRIPTION>
     *
     * @param array $parts
     *
     * @return null
     * @throws \InvalidArgumentException
     */
    public function fillFromArray(array $parts)
    {
        // Array should be 2 pieces long
        if (count($parts) != 2) {

            throw new \InvalidArgumentException('Could not process line ' . implode($parts, ' '));
        }

        // Fill the status
        $this->status = $parts[1];
    }

    /**
     * Will return true if the rewrite engine is set to on and false if not.
     *
     * @return boolean
     */
    public function isOn()
    {
        if ($this->status === 'on') {

            return true;

        } else {

            return false;
        }
    }
}
