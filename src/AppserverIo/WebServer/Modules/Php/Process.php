<?php

/**
 * AppserverIo\WebServer\Modules\Php\Process
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Modules
 * @author     Johann Zelger <jz@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */

namespace AppserverIo\WebServer\Modules\Php;

/**
 * Class Process
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Modules
 * @author     Johann Zelger <jz@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */
class Process
{
    /**
     * Hold's the headers as array
     *
     * @var array
     */
    public $headers;

    /**
     * Hold's the output buffer generated by process run
     *
     * @var string
     */
    public $outputBuffer;

    /**
     * Hold's the uploaded filename's
     *
     * @var array
     */
    protected $uploadedFiles;

    /**
     * Constructs the process
     *
     * @param string                             $scriptFilename The script filename to execute
     * @param \AppserverIo\WebServer\Modules\Php\Globals $globals        The globals instance
     * @param array                              $uploadedFiles  The uploaded files as array
     */
    public function __construct($scriptFilename, $globals, array $uploadedFiles = array())
    {
        $this->scriptFilename = $scriptFilename;
        $this->globals = $globals;
        $this->uploadedFiles = $uploadedFiles;
    }

    /**
     * Run's the process
     *
     * @param int $flags Flags how to start the process
     *
     * @return void
     */
    public function start($flags)
    {
        // init globals to local var
        $globals = $this->globals;
        // start output buffering
        ob_start();
        // set globals
        $_SERVER = $globals['server'];
        $_REQUEST = $globals['request'];
        $_POST = $globals['post'];
        $_GET = $globals['get'];
        $_COOKIE = $globals['cookie'];
        $_FILES = $globals['files'];

        // get current working dir for reset after processing
        $oldCwd = getcwd();
        // change dir to be in real php process context
        chdir(dirname($this->scriptFilename));
        // reset headers sent
        appserver_set_headers_sent(false);
        // require script filename
        require $this->scriptFilename;
        // change dir to old cwd
        chdir($oldCwd);
    }

    /**
     * Dummy join implementation to be compatible to thread process
     *
     * @return bool
     */
    public function join()
    {
        // do nothing
    }

    /**
     * Return's the http response code
     *
     * @return int
     */
    public function getHttpResponseCode()
    {
        return appserver_get_http_response_code();
    }

    /**
     * Return's the output buffer
     *
     * @return string
     */
    public function getOutputBuffer()
    {
        return ob_get_clean();
    }

    /**
     * Return's the headers array
     *
     * @return array
     */
    public function getHttpHeaders()
    {
        return appserver_get_headers(true);
    }

    /**
     * Return's last error informations as array got from function error_get_last()
     *
     * @return array
     * @see error_get_last()
     */
    public function getLastError()
    {
        return error_get_last();
    }
}
