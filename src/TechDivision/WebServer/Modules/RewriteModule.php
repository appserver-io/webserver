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
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */

namespace TechDivision\WebServer\Modules;

use TechDivision\WebServer\Exceptions\ModuleException;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;

/**
 * \TechDivision\WebServer\Modules\RewriteModule
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class RewriteModule extends AbstractModule
{

    /**
     * @var  $systemLogger <TODO FIELD COMMENT>
     */
    protected $systemLogger;

    /**
     * This array will hold all values which one would suspect as part of the PHP $_SERVER array.
     * As it will be filled from different sources we better keep it as a flat array here so we can
     * easily search for any value we need.
     * Filling and refilling will take place in init() and process() as we need it.
     *
     * @var array $serverVars
     */
    protected $serverVars = array();

    /**
     * @var array $dependencies The modules we depend on
     */
    protected $dependencies = array();

    /**
     * http://localhost:8586/magento-1.8.1.0/testcategory/testproduct.html => http://localhost:8586/magento-1.8.1.0/index.php/testcategory/testproduct.html
     */
    protected $mockConfig = array(
        'base' => 'http://localhost:8586/magento-1.8.1.0/',
        'conditions' => array('%{DOCUMENT_ROOT}/$1' => '!-f', '%{HTTP_POST}' => '^(admin.example.com)$'),
        'rules' => array('/rewritten([0-9]*)([a-z]*)' => '/example/?q=$1&m=$2')
    );

    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'rewrite';

    /**
     * Return's the request instance
     *
     * @return \TechDivision\Http\HttpRequestInterface The request instance
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns the response instance
     *
     * @return \TechDivision\Http\HttpResponseInterface The response instance;
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Initiates the module
     *
     * @return bool
     * @throws \TechDivision\WebServer\Exceptions\ModuleException
     */
    public function init()
    {
        // Register our dependencies
        $this->dependencies = array(
            'core',
            'log'
        );

        $this->systemLogger = $this->getInitialContext()->getSystemLogger();
    }

    /**
     * Implement's module logic
     *
     * @param \TechDivision\Http\HttpRequestInterface  $request  The request instance
     * @param \TechDivision\Http\HttpResponseInterface $response The response instance
     *
     * @return bool
     * @throws \TechDivision\WebServer\Exceptions\ModuleException
     */
    public function process(HttpRequestInterface $request, HttpResponseInterface $response)
    {
        $time = microtime(true);

        $conditionBackreferences = array();
        $ruleBackreferences = array();

        // We have to fill the request part of our $serverVars array here
        $this->serverVars = array_merge($this->serverVars, $request->getHeaders());

        // Save the request URI to save some method calls
        $requestedUri = $request->getUri();

        $rewrittenUri = '';
        $matches = array();

        //////////////////////////////////////////////////// backref rules

        $rulesMatched = array();
        foreach ($this->mockConfig['rules'] as $rule => $target) {

            // If we do not match we can continue our quest
            if (preg_match('`' . $rule . '`', $requestedUri, $matches) !== 1) {

                unset($this->mockConfig['rules'][$rule]);
                continue;
            }

            // Unset the first find of our backrefernces, so we can use it automatically
            unset($matches[0]);

            $ruleBackreferences = array_merge($ruleBackreferences, $matches);
        }

        // If there was no rule which matched we can stop right here (as rules need no condition backreferences for
        // patterns)
        if (empty($this->mockConfig['rules'])) {

            return;
        }

        //////////////////////////////////////////////////// resolve cond & check cond &  backref cond

        // We have to replace all $serverVar placeholder within the rewrite conditions we got
        $conditions = $this->mockConfig['conditions'];
        $condFailed = false;
        foreach ($conditions as $testString => $pattern) {

            //////////////////////////////////////////////////// resolve cond

            $originalTestString = $testString;
            preg_replace_callback(
                '/%\{.*?\}/',
                function ($match) {
                    if (isset($this->serverVars[$match[0]])) {

                        return $this->serverVars[$match[0]];
                    }
                },
                $testString
            );

            // Substitute the backreferences like $1, $2, ...
            foreach ($ruleBackreferences as $key => $ruleBackreference) {

                $testString = str_replace('$' . $key, $ruleBackreference, $testString);
            }

            // Write our changes back to our condition array
            unset($this->mockConfig['conditions'][$originalTestString]);
            $this->mockConfig['conditions'][$testString] = $pattern;

            //////////////////////////////////////////////////// check cond

            // If we do not match we will fail right here
            if (preg_match('`' . $pattern . '`', $testString) !== 1) {

                return;
            }

            //////////////////////////////////////////////////// backref cond

            // If we do not match we can continue our quest
            if (preg_match('`' . $pattern . '`', $testString, $matches) !== 1) {

                continue;
            }

            // Unset the first find of our backrefernces, so we can use it automatically
            unset($matches[0]);

            $conditionBackreferences = array_merge($conditionBackreferences, $matches);
        }

        //////////////////////////////////////////////////// resolve rules & check rules

        // This is similar to using the L flag and breaks non-L-flag usage!
        // TODO implement different flags
        $target = array_pop($this->mockConfig['rules']);

        // Substitute the placeholders like $1, $2, ...
        foreach ($ruleBackreferences as $key => $ruleBackreference) {

            $target = str_replace('$' . $key, $ruleBackreference, $target);
        }
        foreach ($conditionBackreferences as $key => $conditionBackreference) {

            $target = str_replace('$' . $key, $conditionBackreference, $target);
        }

        // We found something, so we need our target anyway
        $rewrittenUri = $target;

        //////////////////////////////////////////////////// act

        // Did we even get something useful? If not then give the other modules a chance
        if (empty($rewrittenUri)) {

            return;
        }

        // If the URI is an absolute file path we have to dispatch the request here
        if (is_readable($rewrittenUri)) {

            // Set the document root to the directory above the referenced file and the uri to the file itself
            $request->setDocumentRoot(dirname($rewrittenUri));
            $request->setUri(basename($rewrittenUri));

        } else {
            // Set the URI as we are relative to the original document root

            $request->setUri($rewrittenUri);
        }
        error_log(microtime(true) - $time);
        // Log what we do
        $this->systemLogger->debug('Rewriting ' . $requestedUri . ' to ' . $rewrittenUri);
    }

    /**
     * Return's an array of module names which should be executed first
     *
     * @return array The array of module names
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * Returns the module name
     *
     * @return string The module name
     */
    public function getModuleName()
    {
        return self::MODULE_NAME;
    }
}
