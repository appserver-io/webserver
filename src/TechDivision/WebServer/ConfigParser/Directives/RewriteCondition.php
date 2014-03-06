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
 * @category   Php-by-contract
 * @package    TechDivision_WebServer
 * @subpackage ConfigParser
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\WebServer\ConfigParser\Directives;

/**
 * TechDivision\WebServer\ConfigParser\Directives\RewriteCondition
 *
 * <TODO CLASS DESCRIPTION>
 *
 * @category   Php-by-contract
 * @package    TechDivision_WebServer
 * @subpackage ConfigParser
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class RewriteCondition
{
    /**
     * @var array $allowedTypes <TODO FIELD COMMENT>
     */
    protected $allowedTypes = array('regex', 'check');

    /**
     * @var array $htaccessAdditions <TODO FIELD COMMENT>
     */
    protected $htaccessAdditions = array(
        '!',
        '<' => 'strcmp(#1, #2) < 0',
        '>' => 'strcmp(#1, #2) > 0',
        '=' => 'strcmp(#1, #2) == 0',
        '-d' => 'is_dir(#1)',
        '-f' => 'is_file(#1)',
        '-s' => 'is_file(#1) && filesize(#1) > 0',
        '-l' => 'is_link(#1)',
        '-x' => 'is_executable(#1)',
        '-F',
        '-U'
    );

    /**
     * @var string $type <TODO FIELD COMMENT>
     */
    protected $type;

    /**
     * To check
     *
     * @var  $operand <TODO FIELD COMMENT>
     */
    protected $operand;

    /**
     * @var  $action <TODO FIELD COMMENT>
     */
    protected $action;

    /**
     * Regex, File check etc
     *
     * @var  $operation <TODO FIELD COMMENT>
     */
    protected $operation;

    /**
     * Flags
     *
     * @var  $modifier <TODO FIELD COMMENT>
     */
    protected $modifier;

    /**
     * @var  $isNegated <TODO FIELD COMMENT>
     */
    protected $isNegated;

    /**
     * @param string $type
     * @param string $operand
     * @param string $operation
     * @param null   $modifier
     */
    public function __construct($type = 'regex', $operand = '', $action = '', $modifier = null)
    {
        if (!isset(array_flip($this->allowedTypes)[$type])) {

            throw new \InvalidArgumentExeption($type . ' is not an allowed condition type.');
        }

        // We do not negate by default
        $this->isNegated = false;

        $this->fillFromArray(array($operand, $action, $modifier));
    }

    public function getType()
    {
        return $this->type;
    }

    public function getOperand()
    {
        return $this->operand;
    }

    public function getOperation()
    {
        return $this->operation;
    }

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

        // Substitute the backreferences in our operand and operation
        $this->operand = str_replace($backreferenceHolders, $backreferenceValues, $this->operand);
        $this->operation = str_replace($backreferenceHolders, $backreferenceValues, $this->operation);
    }

    /**
     * <TODO FUNCTION DESCRIPTION>
     *
     * @return boolean
     * @throws \InvalidArgumentException
     */
    public function matches()
    {
        switch ($this->type) {

            default:

                return eval('if (' . $this->operation . '){return true;}');
        }

        // Still here? That does not sound good
        return false;
    }

    /**
     * <TODO FUNCTION DESCRIPTION>
     *
     * @param $offset
     *
     * @return array
     */
    public function getBackreferences($offset)
    {
        $backreferences = array();
        $matches = array();
        if ($this->type === 'regex') {

            preg_match('`' . $this->action . '`', $this->operand, $matches);

            // Unset the first find of our backreferences, so we can use it automatically
            unset($matches[0]);
        }

        // Iterate over all our found matches and give them a fine name
        foreach ($matches as $key => $match) {

            $backreferences['%' . (string)($offset + $key)] = $match;
        }

        return $backreferences;
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
        // Array should be 2 or 3 pieces long
        if (count($parts) < 2 || count($parts) > 3) {

            throw new \InvalidArgumentException('Could not process line ' . implode($parts, ' '));
        }

        // Fill operand and action to preserve it
        $this->operand = $parts[0];
        $this->action = $parts[1];

        // Fill the instance, "regex" is the default type
        $this->type = 'regex';

        // Preset the operation with a regex check
        $this->operation = 'preg_match(\'`' . $parts[1] . '`\', \'' . $parts[0] . '\') === 1';

        // Are we able to find any of the additions htaccess syntax offers?
        foreach ($this->htaccessAdditions as $addition => $realAction) {

            if (strpos($parts[1], $addition) !== false) {

                // We have a "check" type
                $this->type = 'check';

                // Check if we have to negate
                if ($addition === '!') {

                    $this->isNegated = true;
                    continue;
                }

                // We have to extract the needed parts of our operation and refill it into our operation string
                $additionPart = substr($parts[1], 1);
                $this->$operation = str_replace(array('#1', '#2'), array($parts[0], $additionPart), $realAction);
                break;
            }
        }

        // Get the modifier, if there is any
        if (isset($parts[2])) {

            $this->modifier = $parts[2];
        }
    }
}
