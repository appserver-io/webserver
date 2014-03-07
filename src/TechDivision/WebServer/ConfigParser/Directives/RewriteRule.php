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
 * The RewriteRule directive
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage ConfigParser
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 *
 * TODO implement rule flags
 */
class RewriteRule implements DirectiveInterface
{
    /**
     * The allowed values the $type member my assume
     *
     * @var array $allowedTypes
     */
    protected $allowedTypes = array();

    /**
     * Mappings for possible flags and their needed reaction as strings
     *
     * @var array $flagMapping
     */
    protected $flagMapping = array();

    /**
     * The type of rule we have. This might be "relative", "absolute" or "redirect"
     *
     * @var string $type
     */
    protected $type;

    /**
     * The pattern to check the requested URI against
     *
     * @var string $pattern
     */
    protected $pattern;

    /**
     * The target we have to rewrite to if rules and conditions apply
     *
     * @var string $target
     */
    protected $target;

    /**
     * Modifier which should be used to integrate things like apache flags and others
     *
     * @var string $modifier
     */
    protected $modifier;

    /**
     * Default constructor
     *
     * @param string      $type     The type of rule we have. This might be "relative", "absolute" or "redirect"
     * @param string      $pattern  The pattern to check the requested URI against
     * @param string      $target   The target we have to rewrite to if rules and conditions apply
     * @param string|null $modifier Modifier which should be used to integrate things like apache flags and others
     *
     * @throws \InvalidArgumentException
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
     * Getter for the $type member
     *
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Getter for the $pattern member
     *
     * @return mixed
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Getter for the $target member
     *
     * @return mixed
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Getter for the $modifier member
     *
     * @return string
     */
    public function getModifier()
    {
        return $this->modifier;
    }

    /**
     * Will resolve the directive's parts by substituting placeholders with the corresponding backreferences
     *
     * @param array $backreferences The backreferences used for resolving placeholders
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
     * Will return true if the rule applies, false if not
     *
     * @param string $requestedUri The requested URI as implicit part of the rule
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
     * Will collect all backreferences based on regex typed conditions
     *
     * @param integer $offset       The offset to count from, used so no integer based directive will be overwritten
     * @param string  $requestedUri The requested URI as implicit part of the rule
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
     * Will return the necessary result after applying the rule
     *
     * @return mixed
     */
    public function apply()
    {
        return $this->target;
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
