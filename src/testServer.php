<?php

require '../vendor/autoload.php';

class WorkerThread extends Thread {
    public function __construct($socketResource) {
        $this->socketResource = $socketResource;
        $this->start();
    }

    public function run()
    {
        require '../vendor/autoload.php';

        error_log('Starting worker ' . $this->getThreadId());

        $request = new \TechDivision\Http\HttpRequest();
        $response = new \TechDivision\Http\HttpResponse();
        $parser = new \TechDivision\Http\HttpRequestParser($request, $response);

        $parser->injectQueryParser(new \TechDivision\Http\HttpQueryParser());
        $queryParser = $parser->getQueryParser();

        $serverConnection = \TechDivision\Server\Sockets\StreamSocket::getInstance($this->socketResource);

        while (true)
        {
            // init the request parser
            $parser->init();

            if ($connection = $serverConnection->accept()) {

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

                $connection->write("HTTP/1.1 200 OK
Date: Tue, 08 Jul 2014 14:44:56 GMT
Server: TestServer
Last-Modified: Wed, 05 Mar 2014 20:22:53 GMT
Accept-Ranges: bytes
Content-Length: 10
Vary: Accept-Encoding
Content-Type: text/html

HTML TEXT
");

                $connection->close();
            }
        }

    }
}

$serverConnection = \TechDivision\Server\Sockets\StreamSocket::getServerInstance('0.0.0.0:9081');

$csock = socket_import_stream($serverConnection->getConnectionResource());
$fdId = (int)$csock;

echo '$fdId: ' . $fdId  . PHP_EOL;

while (true) {
    if ($connection = $serverConnection->accept()) {

        $fstats = fstat($connection->getConnectionResource());

        error_log(var_export($fstats));

        $fdId = $fstats[1];

        echo '$fdId: ' . $fdId . PHP_EOL;

        $clientFD = fopen('socket:[' . $fdId . ']', 'w');
        fwrite($clientFD, "HTTP/1.1 200 OK
Date: Tue, 08 Jul 2014 14:44:56 GMT
Server: TestServer
Last-Modified: Wed, 05 Mar 2014 20:22:53 GMT
Accept-Ranges: bytes
Content-Length: 10
Vary: Accept-Encoding
Content-Type: text/html

HTML TEXT
");

        fclose($clientFD);
        $fdId++;
    }
}


/*
$workers = array();

for ($i=1; $i<=16; $i++) {
    $workers[$i] = new WorkerThread($serverConnection->getConnectionResource());
}
*/

