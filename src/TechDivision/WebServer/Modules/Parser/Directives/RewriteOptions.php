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

namespace TechDivision\WebServer\Modules\Parser\Directives;

use TechDivision\Http\HttpRequestInterface;
use TechDivision\WebServer\Interfaces\DirectiveInterface;
use TechDivision\WebServer\Modules\Parser\Config;

/**
 * TechDivision\WebServer\Modules\Parser\Directives\RewriteOptions
 *
 * The RewriteOptions directive
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
class RewriteOptions implements DirectiveInterface
{
    /**
     * The allowed values the $type member my assume
     *
     * @var array $allowedValues
     */
    protected $allowedValues = array();

    /**
     * The option value. This might be "inherit", "AllowAnyURI" or "MergeBase"
     *
     * @var string $value
     */
    protected $value;

    /**
     * Default constructor
     *
     * @param string $value The option value. This might be "inherit", "AllowAnyURI" or "MergeBase"
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($value = 'inherit')
    {
        // Fill the default values for our members here
        $this->allowedValues = array("inherit", "AllowAnyURI", "MergeBase");

        if (!isset(array_flip($this->allowedValues)[$value])) {

            throw new \InvalidArgumentException($value . ' is not an allowed RewriteOptions value.');
        }

        $this->fillFromArray(array(__CLASS__, $value));
    }

    /**
     * Will return a string representation of $this
     *
     * @return string
     */
    public function __tostring()
    {
        return $this->getValue();
    }

    /**
     * Getter for the $value member
     *
     * @return string|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Will have to extend or security-check the current config according to $this->value
     *
     * @param \TechDivision\WebServer\Modules\Parser\Config $config  The current module configuration
     * @param \TechDivision\Http\HttpRequestInterface       $request The current request object
     *
     * @return null
     */
    public function apply(Config $config, HttpRequestInterface $request)
    {
        switch ($this->value) {

            case "inherit":


                break;

            case "AllowAnyURI":

                break;

            case "MergeBase":


                break;

            default:


                break;
        }
    }

    /**
     * Will fill an empty directive object with vital information delivered via an array.
     * This is mostly useful as an interface for different parsers.
     *
     * @param array $parts The array to extract information from
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
        $this->value = $parts[1];
    }
}
