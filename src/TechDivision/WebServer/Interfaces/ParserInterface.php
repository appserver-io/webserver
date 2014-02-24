<?php

namespace TechDivision\Http;

interface ParserInterface
{
    /**
     * Parses the start line
     *
     * @param string $line The start line
     * @return void
     * @throws
     *
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.1
     */
    public function parseStartLine($line);

    /**
     * @param string $line The line defining a http request header
     *
     * @return mixed
     */
    public function parseHeaderLine($line);

}