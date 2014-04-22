<?php
/**
 * \TechDivision\WebServer\Sockets\StreamSocket
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Library
 * @package    TechDivision_WebServer
 * @subpackage Sockets
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */

namespace TechDivision\WebServer\Sockets;

/**
 * Class StreamSocket
 *
 * @category   Library
 * @package    TechDivision_WebServer
 * @subpackage Sockets
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class StreamSocket implements SocketInterface
{

    /**
     * Holds the connection resource it selfe.
     *
     * @var resource
     */
    protected $connectionResource;

    /**
     * Holds the actual resource id
     *
     * @var int
     */
    protected $connectionResourceId;

    /**
     * Hold's the peername of the client who connected
     *
     * @var string
     */
    protected $connectionPeername;

    /**
     * Creates a stream socket server and returns a instance of Stream implementation with server socket in it.
     *
     * @param string   $socket  The address incl. transport the server should be listening to. For example 0.0.0.0:8080
     * @param string   $flags   The flags to be set on server create
     * @param resource $context The context to be set on stream create
     *
     * @return \TechDivision\WebServer\Sockets\Stream The Stream instance with a server socket created.
     */
    public static function getServerInstance($socket, $flags = null, $context = null)
    {
        // init flags if none were given
        if (is_null($flags)) {
            $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;
        }

        // init context if none was given
        if (is_null($context)) {
            $context = @stream_context_create();
        }

        // create stream socket server resource
        $serverResource = @stream_socket_server($socket, $errno, $errstr, $flags, $context);

        // throw exception if it was not possible to create server socket binding
        if (!$serverResource) {
            throw new SocketServerException($errstr, $errno);
        }

        // set blocking mode
        @stream_set_blocking($serverResource, 1);
        // create instance and return it.
        return self::getInstance($serverResource);
    }

    /**
     * Return's an instance of Stream with preset resource in it.
     *
     * @param resource $connectionResource The resource to use
     *
     * @return \TechDivision\WebServer\Sockets\StreamSocket
     */
    public static function getInstance($connectionResource)
    {
        $connection = new self();
        $connection->setConnectionResource($connectionResource);
        return $connection;
    }

    /**
     * Accepts connections from clients and build up a instance of Stream with connection resource in it.
     *
     * @param int $acceptTimeout  The timeout in seconds to wait for accepting connections.
     * @param int $receiveTimeout The timeout in seconds to wait for read a line.
     *
     * @return \TechDivision\WebServer\Sockets\StreamSocket|bool The Stream instance with the connection socket
     *                                                           accepted or bool false if timeout or error occurred.
     */
    public function accept($acceptTimeout = 600, $receiveTimeout = 16)
    {
        $connectionResource = @stream_socket_accept($this->getConnectionResource(), $acceptTimeout, $peername);
        // if timeout or error occurred return false as accept function does
        if ($connectionResource === false) {
            return false;
        }
        // set timeout for read data fom client
        stream_set_timeout($connectionResource, $receiveTimeout);
        $connection = $this->getInstance($connectionResource);
        $connection->setPeername($peername);
        return $connection;
    }

    /**
     * Return's the line read from connection resource
     *
     * @param int $readLength     The max length to read for a line.
     * @param int $receiveTimeout The max time to wait for read the next line
     *
     * @return string
     * @throws \TechDivision\WebServer\Sockets\SocketReadTimeoutException
     */
    public function readLine($readLength = 1024, $receiveTimeout = null)
    {
        if ($receiveTimeout) {
            // set timeout for read data fom client
            @stream_set_timeout($this->getConnectionResource(), $receiveTimeout);
        }
        $line = @fgets($this->getConnectionResource(), $readLength);
        // check if timeout occured
        if (strlen($line) === 0) {
            throw new SocketReadTimeoutException();
        }
        return $line;
    }

    /**
     * Read's the given length from connection resource
     *
     * @param int $readLength     The max length to read for a line.
     * @param int $receiveTimeout The max time to wait for read the next line
     *
     * @return string
     * @throws \TechDivision\WebServer\Sockets\SocketReadTimeoutException
     */
    public function read($readLength = 1024, $receiveTimeout = null)
    {
        if ($receiveTimeout) {
            // set timeout for read data fom client
            @stream_set_timeout($this->getConnectionResource(), $receiveTimeout);
        }
        $line = @fread($this->getConnectionResource(), $readLength);
        // check if timeout occured
        if (strlen($line) === 0) {
            throw new SocketReadTimeoutException();
        }
        return $line;
    }

    /**
     * Writes the given message to the connection resource.
     *
     * @param string $message The message to write to the connection resource.
     *
     * @return int
     */
    public function write($message)
    {
        return @fwrite($this->getConnectionResource(), $message, strlen($message));
    }

    /**
     * Copies data from a stream
     *
     * @param resource $sourceResource The source stream
     *
     * @return int The total count of bytes copied.
     */
    public function copyStream($sourceResource)
    {
        @rewind($sourceResource);
        return @stream_copy_to_stream($sourceResource, $this->getConnectionResource());
    }

    /**
     * Closes the connection resource
     *
     * @return bool
     */
    public function close()
    {
        // check if resource still exists
        if (is_resource($this->getConnectionResource())) {
            return @fclose($this->getConnectionResource());
        }
        return false;
    }

    /**
     * Set's the connection resource
     *
     * @param resource $connectionResource The resource to socket file descriptor
     *
     * @return void
     */
    public function setConnectionResource($connectionResource)
    {
        $this->connectionResourceId = (int)$connectionResource;
        $this->connectionResource = $connectionResource;
    }

    /**
     * Set's the peername
     *
     * @param string $peername The peername in format ip:port
     *
     * @return void
     */
    public function setPeername($peername)
    {
        $this->connectionPeername = $peername;
    }

    /**
     * Return's the peername in format ip:port (e.g. 10.20.30.40:57128)
     *
     * @return string
     */
    public function getPeername()
    {
        return $this->connectionPeername;
    }

    /**
     * Return's the address of connection
     *
     * @return string
     */
    public function getAddress()
    {
        return strstr($this->getPeername(), ':', true);
    }

    /**
     * Return's the port of connection
     *
     * @return string
     */
    public function getPort()
    {
        return str_replace(':', '', strstr($this->getPeername(), ':'));
    }

    /**
     * Return's the connection resource
     *
     * @return mixed
     */
    public function getConnectionResource()
    {
        return $this->connectionResource;
    }

    /**
     * Return's connection resource id
     *
     * @return int
     */
    public function getConnectionResourceId()
    {
        return $this->connectionResourceId;
    }
}
