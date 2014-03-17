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

use TechDivision\WebServer\Interfaces\DirectiveInterface;

/**
 * TechDivision\WebServer\Modules\Parser\Directives\RewriteBase
 *
 * Class which contains the RewriteBase directive used in directory based rewrite rules
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
class RewriteBase implements DirectiveInterface
{
    /**
     * The url which builds up the rewrite base
     *
     * @var string $urlPath
     */
    protected $urlPath;

    /**
     * Default constructor
     *
     * @param string|null $urlPath The url which builds up the rewrite base
     */
    public function __construct($urlPath = null)
    {
        $this->urlPath = $urlPath;
    }

    /**
     * Will return a string representation of $this
     *
     * @return string
     */
    public function __tostring()
    {
        if (is_null($this->getUrlPath())) {

            return '';
        }

        return $this->getUrlPath();
    }

    /**
     * Getter for the url path of our rewrite base
     *
     * @return null|string
     */
    public function getUrlPath()
    {
        return $this->urlPath;
    }

    /**
     * Will fill an empty directive object with vital information delivered via an array.
     * This is mostly useful as an interface for different parsers
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

        // Fill the url
        $this->urlPath = $parts[1];
    }
}
