<?php

require '../vendor/autoload.php';

class WorkerThread extends Thread {

    public function __construct($fd) {
        $this->fd = $fd;
        $this->start(PTHREADS_INHERIT_NONE);
    }

    public function serve($connection)
    {
        $this->connection = $connection;
        $this->notify();
    }

    public function run()
    {
        require '../vendor/autoload.php';

        error_log('Starting worker ' . $this->getThreadId());

        $request = new \TechDivision\Http\HttpRequest();
        $response = new \TechDivision\Http\HttpResponse();
        $queryParser = new \TechDivision\Http\HttpQueryParser();

        $parser = new \TechDivision\Http\HttpRequestParser($request, $response);
        $parser->injectQueryParser($queryParser);

        // init the request parser
        $parser->init();

        while (true) {

            // wait for requests to handle
            $this->wait();

            echo 'Worker wakeup to handle fd: ' . $this->fd. PHP_EOL;

            // build up connection

            $connection = $this->connection;//fopen('php://fd/' . $this->fd, 'w');

            if (!$connection) {
                continue;
            }

            try {

                $line = '';

                // set first line from connection
                $line = fgets($connection, 2048);

                /**
                 * In the interest of robustness, servers SHOULD ignore any empty
                 * line(s) received where a Request-Line is expected. In other words, if
                 * the server is reading the protocol stream at the beginning of a
                 * message and receives a CRLF first, it should ignore the CRLF.
                 *
                 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.1
                 */
                if ($line === "\r\n") {
                    // ignore the first CRLF and go on reading the expected start-line.
                    $line = fgets($connection, 2048);
                }

                if (!$line) {
                    continue;
                }

                // parse read line
                $parser->parseStartLine($line);

                $messageHeaders = '';
                while ($line != "\r\n") {
                    // read next line
                    $line = fgets($connection, 1024);
                    // enhance headers
                    $messageHeaders .= $line;
                }

                // parse headers
                $parser->parseHeaders($messageHeaders);

                // check if message body will be transmitted
                if ($request->hasHeader(\TechDivision\Http\HttpProtocol::HEADER_CONTENT_LENGTH)) {
                    // get content-length header
                    if (($contentLength = (int)$request->getHeader(\TechDivision\Http\HttpProtocol::HEADER_CONTENT_LENGTH)) > 0) {
                        // copy connection stream to body stream by given content length
                        $request->copyBodyStream($connection, $contentLength);
                        // get content out for oldschool query parsing todo: refactor query parsing
                        $content = $request->getBodyContent();
                        // check if request has to be parsed depending on Content-Type header
                        if ($queryParser->isParsingRelevant($request->getHeader(\TechDivision\Http\HttpProtocol::HEADER_CONTENT_TYPE))) {
                            // checks if request has multipart formdata or not
                            preg_match('/boundary=(.*)$/', $request->getHeader(\TechDivision\Http\HttpProtocol::HEADER_CONTENT_TYPE), $boundaryMatches);
                            // check if boundaryMatches are found
                            // todo: refactor content string var to be able to use bodyStream
                            if (count($boundaryMatches) > 0) {
                                $parser->parseMultipartFormData($content);
                            } else {
                                $queryParser->parseStr($content);
                            }
                        }
                    }
                }

            } catch (Exception $e) {
                error_log($e);
            }

            //usleep(10000);
            fwrite($connection, "HTTP/1.1 200 OK
Date: Tue, 08 Jul 2014 14:44:56 GMT
Server: TestServer
Last-Modified: Wed, 05 Mar 2014 20:22:53 GMT
Accept-Ranges: bytes
Content-Length: 10
Vary: Accept-Encoding
Content-Type: text/html

HTML TEXT
");

            stream_socket_shutdown($connection, STREAM_SHUT_WR);
            fclose($connection);

        }
    }
}

class WorkerAcceptThread extends Thread {

    public function __construct($serverStreamConnection) {
        $this->serverStreamConnection = $serverStreamConnection;
        $this->start();
    }

    public function run()
    {
        require '../vendor/autoload.php';

        error_log('Starting worker ' . $this->getThreadId());

        $request = new \TechDivision\Http\HttpRequest();
        $response = new \TechDivision\Http\HttpResponse();
        $queryParser = new \TechDivision\Http\HttpQueryParser();

        $parser = new \TechDivision\Http\HttpRequestParser($request, $response);
        $parser->injectQueryParser($queryParser);

        $serverStream = $this->serverStreamConnection;
        $serverConnection = \TechDivision\Server\Sockets\StreamSocket::getInstance($serverStream);

        // init the request parser
        $parser->init();

        while (true) {

            // wait for requests to handle
            if ($connection = $serverConnection->accept()) {

                $startTime = microtime(true);

                try {

                    $line = '';

                    // set first line from connection
                    $line = $connection->readLine(2048);

                    /**
                     * In the interest of robustness, servers SHOULD ignore any empty
                     * line(s) received where a Request-Line is expected. In other words, if
                     * the server is reading the protocol stream at the beginning of a
                     * message and receives a CRLF first, it should ignore the CRLF.
                     *
                     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.1
                     */
                    if ($line === "\r\n") {
                        // ignore the first CRLF and go on reading the expected start-line.
                        $line = $connection->readLine(2048);
                    }

                    if (!$line) {
                        continue;
                    }

                    // parse read line
                    $parser->parseStartLine($line);

                    $messageHeaders = '';
                    while ($line != "\r\n") {
                        // read next line
                        $line = $connection->readLine();
                        // enhance headers
                        $messageHeaders .= $line;
                    }

                    // parse headers
                    $parser->parseHeaders($messageHeaders);

                    // check if message body will be transmitted
                    if ($request->hasHeader(\TechDivision\Http\HttpProtocol::HEADER_CONTENT_LENGTH)) {
                        // get content-length header
                        if (($contentLength = (int)$request->getHeader(\TechDivision\Http\HttpProtocol::HEADER_CONTENT_LENGTH)) > 0) {
                            // copy connection stream to body stream by given content length
                            $request->copyBodyStream($connection->getConnectionResource(), $contentLength);
                            // get content out for oldschool query parsing todo: refactor query parsing
                            $content = $request->getBodyContent();
                            // check if request has to be parsed depending on Content-Type header
                            if ($queryParser->isParsingRelevant($request->getHeader(\TechDivision\Http\HttpProtocol::HEADER_CONTENT_TYPE))) {
                                // checks if request has multipart formdata or not
                                preg_match('/boundary=(.*)$/', $request->getHeader(\TechDivision\Http\HttpProtocol::HEADER_CONTENT_TYPE), $boundaryMatches);
                                // check if boundaryMatches are found
                                // todo: refactor content string var to be able to use bodyStream
                                if (count($boundaryMatches) > 0) {
                                    $parser->parseMultipartFormData($content);
                                } else {
                                    $queryParser->parseStr($content);
                                }
                            }
                        }
                    }

                } catch (Exception $e) {
                    error_log($e);
                }

                $connection->write("HTTP/1.1 200 OK\r\nConnection: Close\r\n");
                $connection->close();

                $deltaTime = microtime(true) - $startTime;
                error_log(__METHOD__ . ' took: ' . $deltaTime . ' secs.');
            }
        }
    }
}

$serverStream = @stream_socket_server('tcp://0.0.0.0:9081', $errno, $errstr);
$workers = array();

/*
for ($i=1; $i<=256; $i++) {
    $workers[$i] = new WorkerThread($i);
}

$connections = array();

while (true) {
    if ($connection = stream_socket_accept($serverStream)) {

        // import file descriptor
        $fd = appserver_stream_import_file_descriptor($connection);

        // check which worker serve the response to the client
        echo 'accepted fd: ' . $fd . PHP_EOL;
        //$connections[$fd] = $connection;
        $workers[$fd]->serve($connection);
        $workers[$fd]->notify();

    }
}
*/


for ($i=1; $i<=16; $i++) {
    $workers[$i] = new WorkerAcceptThread($serverStream);
}


/*

$request = new \TechDivision\Http\HttpRequest();
$response = new \TechDivision\Http\HttpResponse();
$queryParser = new \TechDivision\Http\HttpQueryParser();

$parser = new \TechDivision\Http\HttpRequestParser($request, $response);
$parser->injectQueryParser($queryParser);

// init the request parser
$parser->init();

while (true) {
    // wait for requests to handle
    if ($connection = stream_socket_accept($serverStream)) {

        try {

            $line = '';

            // set first line from connection
            $line = fgets($connection, 2048);

            if ($line === "\r\n") {
                // ignore the first CRLF and go on reading the expected start-line.
                $line = fgets($connection, 2048);
            }

            if (!$line) {
                continue;
            }

            // parse read line
            $parser->parseStartLine($line);

            $messageHeaders = '';
            while ($line != "\r\n") {
                // read next line
                $line = fgets($connection, 1024);
                // enhance headers
                $messageHeaders .= $line;
            }

            // parse headers
            $parser->parseHeaders($messageHeaders);

            // check if message body will be transmitted
            if ($request->hasHeader(\TechDivision\Http\HttpProtocol::HEADER_CONTENT_LENGTH)) {
                // get content-length header
                if (($contentLength = (int)$request->getHeader(\TechDivision\Http\HttpProtocol::HEADER_CONTENT_LENGTH)) > 0) {
                    // copy connection stream to body stream by given content length
                    $request->copyBodyStream($connection, $contentLength);
                    // get content out for oldschool query parsing todo: refactor query parsing
                    $content = $request->getBodyContent();
                    // check if request has to be parsed depending on Content-Type header
                    if ($queryParser->isParsingRelevant($request->getHeader(\TechDivision\Http\HttpProtocol::HEADER_CONTENT_TYPE))) {
                        // checks if request has multipart formdata or not
                        preg_match('/boundary=(.*)$/', $request->getHeader(\TechDivision\Http\HttpProtocol::HEADER_CONTENT_TYPE), $boundaryMatches);
                        // check if boundaryMatches are found
                        // todo: refactor content string var to be able to use bodyStream
                        if (count($boundaryMatches) > 0) {
                            $parser->parseMultipartFormData($content);
                        } else {
                            $queryParser->parseStr($content);
                        }
                    }
                }
            }

        } catch (Exception $e) {
            error_log($e);
        }

        fwrite($connection, "HTTP/1.1 200 OK
        Date: Tue, 08 Jul 2014 14:44:56 GMT
        Server: TestServer
        Last-Modified: Wed, 05 Mar 2014 20:22:53 GMT
        Accept-Ranges: bytes
        Content-Length: 10
        Vary: Accept-Encoding
        Content-Type: text/html

        HTML TEXT
        ");

        //stream_socket_shutdown($connection, STREAM_SHUT_WR);
        fclose($connection);
    }
}
*/