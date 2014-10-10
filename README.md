# TechDivision_WebServer

[![Latest Stable Version](https://poser.pugx.org/techdivision/webserver/v/stable.png)](https://packagist.org/packages/techdivision/webserver) [![Total Downloads](https://poser.pugx.org/techdivision/webserver/downloads.png)](https://packagist.org/packages/techdivision/webserver) [![Latest Unstable Version](https://poser.pugx.org/techdivision/webserver/v/unstable.png)](https://packagist.org/packages/techdivision/webserver) [![License](https://poser.pugx.org/techdivision/webserver/license.png)](https://packagist.org/packages/techdivision/webserver) [![Build Status](https://travis-ci.org/techdivision/TechDivision_WebServer.png)](https://travis-ci.org/techdivision/TechDivision_WebServer)[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/techdivision/TechDivision_WebServer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/techdivision/TechDivision_WebServer/?branch=master)[![Code Coverage](https://scrutinizer-ci.com/g/techdivision/TechDivision_WebServer/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/techdivision/TechDivision_WebServer/?branch=master)

## Introduction

Are you serious? A web server written in pure PHP for PHP? Ohhhh Yes! :) This is a HTTP/1.1 compliant webserver written in php.
And the best... it has a php module and it's multithreaded!

We use this in the [`appserver.io`](<http://www.appserver.io>) project as a server component for the WebContainer.

## Installation

If you want to use the web server with your application you can install it by adding

```sh
{
    "require": {
        "techdivision/webserver": "dev-master"
    },
}
```

to your ```composer.json``` and invoke ```composer update``` in your project.

Usage
-----
If you can satisfy the requirements it is very simple to use the webserver. Just do this:
```bash
git clone https://github.com/techdivision/TechDivision_WebServer
PHP_BIN=/path/to/your/threadsafe/php-binary TechDivision_WebServer/src/bin/phpwebserver
```
If you're using [`appserver.io`](<http://www.appserver.io>) it'll be this:
```bash
git clone https://github.com/techdivision/TechDivision_WebServer
./TechDivision_WebServer/src/bin/phpwebserver
```

Goto http://127.0.0.1:9080 and if all went good, you will see the welcome page of the php webserver.
It will startup on unsecure http port 9080 and secure https port 9443.

To test a php script just drop a `info.php` to `var/www`.
```php
<pre><?php phpinfo() ?></pre>
```
Now goto http://127.0.0.1:9080/info.php and see what happens... ;)

# External Links

* Documentation at [appserver.io](http://docs.appserver.io)
* Documentation on [GitHub](https://github.com/techdivision/TechDivision_AppserverDocumentation)
* [Getting started](https://github.com/techdivision/TechDivision_AppserverDocumentation/tree/master/docs/getting-started)
* [Web Server](https://github.com/techdivision/TechDivision_AppserverDocumentation/tree/master/docs/components/servers/webserver)