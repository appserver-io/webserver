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
            $context = stream_context_create();
        }

        // create stream socket server resource
        $serverResource = stream_socket_server($socket, $errno, $errstr, $flags, $context);

        // set blocking mode
        stream_set_blocking($serverResource, 1);
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
     * @return \TechDivision\WebServer\Sockets\StreamSocket The Stream instance with the connection socket accepted.
     */
    public function accept($acceptTimeout = 120, $receiveTimeout = 10)
    {
        $connectionResource = stream_socket_accept($this->getConnectionResource(), $acceptTimeout);
        // set timeout for read data fom client
        stream_set_timeout($connectionResource, $receiveTimeout);
        return $this->getInstance($connectionResource);

    }

    /**
     * Return's the line read from connection resource
     *
     * @param int $readLength     The max length to read for a line.
     * @param int $receiveTimeout The max time to wait for read the next line
     *
     * @return string;
     * @throws \TechDivision\WebServer\Sockets\SocketReadTimeoutException
     */
    public function readLine($readLength = 256, $receiveTimeout = null)
    {
        if ($receiveTimeout) {
            // set timeout for read data fom client
            stream_set_timeout($this->getConnectionResource(), $receiveTimeout);
        }
        $line = fgets($this->getConnectionResource(), $readLength);
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
        return fwrite($this->getConnectionResource(), $message, strlen($message));
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
        rewind($sourceResource);
        return stream_copy_to_stream($sourceResource, $this->getConnectionResource());
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
            return fclose($this->getConnectionResource());
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
