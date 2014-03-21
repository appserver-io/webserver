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

namespace TechDivision\WebServer\Modules\RewriteModule;

/**
 * TechDivision\WebServer\Modules\RewriteModule\Condition
 *
 * This class provides an object based representation of a rewrite rules condition including logic for checking itself.
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
class Condition
{
    /**
     * The allowed values for the $types member
     *
     * @var array<string> $allowedTypes
     */
    protected $allowedTypes = array();

    /**
     * All possible modifiers aka flags
     *
     * @var array<string> $flagMapping
     */
    protected $allowedModifiers = array();

    /**
     * Possible additions to the known PCRE regex. These additions get used by htaccess notation only.
     *
     * @var array<string> $htaccessAdditions
     */
    protected $htaccessAdditions = array();

    /**
     * The type of this condition
     *
     * @var string $type
     */
    protected $type;

    /**
     * The value to check with the given action
     *
     * @var string $operand
     */
    protected $operand;

    /**
     * How the operand has to be checked, this will hold the needed action as a string and cannot be
     * processed automatically.
     *
     * @var string $action
     */
    protected $action;

    /**
     * This is a combination of the operand and the action to perform, wrapped in an eval-able string
     *
     * @var string $operation
     */
    protected $operation;

    /**
     * Modifier which should be used to integrate things like apache flags and others
     *
     * @var string $modifier
     */
    protected $modifier;

    /**
     * At least in the apache universe we can negate the logical meaning with a "!"
     *
     * @var boolean $isNegated
     */
    protected $isNegated;

    /**
     * Default constructor
     *
     * @param string $operand  The value to check with the given action
     * @param string $action   How the operand has to be checked, this will hold the needed action
     * @param string $modifier Modifier which should be used to integrate things like apache flags and others
     *
     * @throws \InvalidArgumentException
     *
     * TODO missing Apache flags -F and -U
     */
    public function __construct($operand, $action, $modifier = '')
    {
        // Fill the default values for our members here
        $this->allowedTypes = array('regex', 'check');
        $this->htaccessAdditions = array(
            '<' => 'strcmp(\'#1\', \'#2\') < 0',
            '>' => 'strcmp(\'#1\', \'#2\') > 0',
            '=' => 'strcmp(\'#1\', \'#2\') == 0',
            '-d' => 'is_dir(\'$DOCUMENT_ROOT#1\')',
            '-f' => 'is_file(\'$DOCUMENT_ROOT#1\')',
            '-s' => 'is_file(\'$DOCUMENT_ROOT#1\') && filesize(\'$DOCUMENT_ROOT#1\') > 0',
            '-l' => 'is_link(\'$DOCUMENT_ROOT#1\')',
            '-x' => 'is_executable(\'$DOCUMENT_ROOT#1\')'
        );
        $this->allowedModifiers = array('[NC]', '[nocase]');

        // We do not negate by default, nor do we combine with the following condition via "or"
        $this->isNegated = false;

        // Check if the passed modifier is valid (or empty)
        if (!isset(array_flip($this->allowedModifiers)[$modifier]) && !empty($modifier)) {

            throw new \InvalidArgumentException($modifier . ' is not an allowed condition modifier.');
        }

        // Fill the more important properties
        $this->operand = $operand;
        $this->action = $action;
        $this->modifier = $modifier;

        // Check if we have a negation
        if (strpos($this->action, '!') === 0) {

            // Tell them we have to negate the check
            $this->isNegated = true;
            // Remove the "!" as it might kill the regex otherwise
            $this->action = ltrim($this->action, '!');
        }

        // Preset the operation with a regex check
        $this->type = 'regex';
        $this->operation = 'preg_match(\'`' . $this->action . '`\', \'' . $this->operand . '\') === 1';

        // Are we able to find any of the additions htaccess syntax offers?
        foreach ($this->htaccessAdditions as $addition => $realAction) {

            // This only makes sense if the action is a short string, otherwise we might fall into the trap that
            // any given regex might contain an addition string
            if (strlen($this->action) <= 2 && strpos($this->action, $addition) !== false) {

                // If we reach this point we are of the check type
                $this->type = 'check';

                // We have to extract the needed parts of our operation and refill it into our operation string
                $additionPart = substr($this->action, 1);
                $this->operation = str_replace(array('#1', '#2'), array($this->operand, $additionPart), $realAction);
                break;
            }
        }
    }

    /**
     * Getter for the $type member
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Getter for the $operand member
     *
     * @return string
     */
    public function getOperand()
    {
        return $this->operand;
    }

    /**
     * Getter for the $operation member
     *
     * @return string
     */
    public function getOperation()
    {
        return $this->operation;
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

        // Substitute the backreferences in our operand and operation
        $this->operand = str_replace($backreferenceHolders, $backreferenceValues, $this->operand);
        $this->operation = str_replace($backreferenceHolders, $backreferenceValues, $this->operation);
    }

    /**
     * Will return true if the condition is true, false if not
     *
     * @return boolean
     * @throws \InvalidArgumentException
     */
    public function matches()
    {
        if ($this->isNegated) {

            return eval('if (!(' . $this->operation . ')){return true;}');

        } else {

            return eval('if (' . $this->operation . '){return true;}');
        }
    }

    /**
     * Will collect all backreferences based on regex typed conditions
     *
     * @return array
     */
    public function getBackreferences()
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

            $backreferences['$' . (string)$key] = $match;
        }

        return $backreferences;
    }
}
