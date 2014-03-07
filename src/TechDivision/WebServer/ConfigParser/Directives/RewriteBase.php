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
 * TechDivision\WebServer\ConfigParser\Directives\RewriteBase
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
class RewriteBase implements DirectiveInterface
{
    /**
     * @var string $urlPath The url which builds up the rewrite base
     */
    protected $urlPath;

    /**
     * @param null $urlPath
     */
    public function __construct($urlPath = null)
    {
        $this->urlPath = $urlPath;
    }

    /**
     * <TODO FUNCTION DESCRIPTION>
     *
     * @return null|string
     */
    public function __tostring()
    {
        if (is_null($this->getUrlPath())) {

            return '';
        }

        return $this->getUrlPath();
    }

    /**
     * <TODO FUNCTION DESCRIPTION>
     *
     * @return null
     */
    public function getUrlPath()
    {
        return $this->urlPath;
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

        // Fill the url
        $this->urlPath = $parts[1];
    }
}
