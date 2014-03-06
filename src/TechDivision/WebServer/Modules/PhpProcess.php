<?php

namespace TechDivision\WebServer\Modules;

class PhpProcess extends \Thread
{
    public $headers;
    public $outputBuffer;

    public function __construct($scriptFilename, $globals) //, $outputBufferStream)
    {
        $this->globals = $globals;
        $this->scriptFilename = $scriptFilename;
        // $this->outputBufferStream = $outputBufferStream;
    }

    public function run()
    {
        error_log(__METHOD__);

        // init globals to local var
        $globals = $this->globals;
        // register shutdown handler
        register_shutdown_function(array(&$this, "shutdown" ));
        // start output buffering
        ob_start();
        // set globals
        $_SERVER = $globals->server;
        $_REQUEST = $globals->request;
        $_POST = $globals->post;
        $_GET = $globals->get;
        //$_COOKIE = $globals->cookie;
        //$_FILES = $globals->files;
        // change dir to be in real php process context
        chdir(dirname($this->scriptFilename));
        // reset headers sent
        appserver_headers_sent(false);

        error_log(var_export($globals, true));

        // require script filename
        require $this->scriptFilename;
    }

    public function shutdown()
    {
        // set headers set by script inclusion
        $this->headers = appserver_get_headers(true);

        // set output buffer set by script inclusion
        $this->outputBuffer = ob_get_clean();
    }

    public function getOutputBuffer()
    {
        return $this->outputBuffer;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

}
