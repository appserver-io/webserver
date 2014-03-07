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
 * TechDivision\WebServer\ConfigParser\Directives\RewriteRule
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
class RewriteRule implements DirectiveInterface
{
    /**
     * @var array $allowedTypes <TODO FIELD COMMENT>
     */
    protected $allowedTypes = array();

    /**
     * @var array $flagMapping <TODO FIELD COMMENT>
     */
    protected $flagMapping = array();

    /**
     * @var  $type <TODO FIELD COMMENT>
     */
    protected $type;

    /**
     * To check
     *
     * @var  $pattern <TODO FIELD COMMENT>
     */
    protected $pattern;

    /**
     * Regex, File check etc
     *
     * @var  $target <TODO FIELD COMMENT>
     */
    protected $target;

    /**
     * Flags
     *
     * @var  $modifier <TODO FIELD COMMENT>
     */
    protected $modifier;

    /**
     * @param      $type
     * @param      $pattern
     * @param      $target
     * @param null $modifier
     */
    public function __construct($type = 'relative', $pattern = '', $target = '', $modifier = null)
    {
        // Fill the default values for our members here
        $this->allowedTypes = array('relative', 'absolute', 'redirect');
        $this->flagMapping = array();

        if (!isset(array_flip($this->allowedTypes)[$type])) {

            throw new \InvalidArgumentException($type . ' is not an allowed rule type.');
        }

        $this->fillFromArray(array(__CLASS__, $pattern, $target, $modifier));
    }

    /**
     * <TODO FUNCTION DESCRIPTION>
     *
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * <TODO FUNCTION DESCRIPTION>
     *
     * @return mixed
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * <TODO FUNCTION DESCRIPTION>
     *
     * @return mixed
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * <TODO FUNCTION DESCRIPTION>
     *
     * @return mixed
     */
    public function getModifier()
    {
        return $this->modifier;
    }

    /**
     * <TODO FUNCTION DESCRIPTION>
     *
     * @param array $backreferences
     *
     * @return void
     */
    public function resolve(array $backreferences)
    {
        // Separate the keys from the values so we can use them in str_replace
        $backreferenceHolders = array_keys($backreferences);
        $backreferenceValues = array_values($backreferences);

        // Substitute the backreferences in our operation
        $this->target = str_replace($backreferenceHolders, $backreferenceValues, $this->target);
    }

    /**
     * <TODO FUNCTION DESCRIPTION>
     *
     * @param $requestedUri
     *
     * @return bool
     */
    public function matches($requestedUri)
    {
        if (preg_match('`' . $this->pattern . '`', $requestedUri) === 1) {

            return true;

        } else {

            return false;
        }
    }

    /**
     * <TODO FUNCTION DESCRIPTION>
     *
     * @param $offset
     * @param $requestedUri
     *
     * @return array
     */
    public function getBackreferences($offset, $requestedUri)
    {
        $backreferences = array();
        $matches = array();
        if ($this->type === 'relative') {

            preg_match('`' . $this->pattern . '`', $requestedUri, $matches);

            // Unset the first find of our backreferences, so we can use it automatically
            unset($matches[0]);
        }

        // Iterate over all our found matches and give them a fine name
        foreach ($matches as $key => $match) {

            $backreferences['$' . (string)($offset + $key)] = $match;
        }

        return $backreferences;
    }

    /**
     * <TODO FUNCTION DESCRIPTION>
     *
     * @return mixed
     */
    public function apply()
    {
        return $this->target;
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
        // Array should be 3 or 4 pieces long
        if (count($parts) < 3 || count($parts) > 4) {

            throw new \InvalidArgumentException('Could not process line ' . implode($parts, ' '));
        }

        // Fill pattern and target as they are pretty straight forward
        $this->pattern = $parts[1];
        $this->target = $parts[2];

        // Fill the instance, "relative" is the default type
        $this->type = 'relative';
        if (filter_var($parts[2], FILTER_VALIDATE_URL)) {

            // Do we have a valid url? If so we have a redirect at hand
            $this->type = 'redirect';

        } else {

            // If we can find the file with all we got then we are absolute
            $fileInfo = new \SplFileInfo($parts[2]);
            if ($fileInfo->isReadable()) {

                $this->type = 'absolute';
            }

        }

        // Get the modifier, if there is any
        if (isset($parts[3])) {

            $this->modifier = $parts[3];
        }
    }
}
