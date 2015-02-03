# webserver

[![Latest Stable Version](https://poser.pugx.org/appserver-io/webserver/v/stable.png)](https://packagist.org/packages/appserver-io/webserver) [![Total Downloads](https://poser.pugx.org/appserver-io/webserver/downloads.png)](https://packagist.org/packages/appserver-io/webserver) [![License](https://poser.pugx.org/appserver-io/webserver/license.png)](https://packagist.org/packages/appserver-io/webserver) [![Build Status](https://travis-ci.org/appserver-io/webserver.png)](https://travis-ci.org/appserver-io/webserver) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/appserver-io/webserver/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/appserver-io/webserver/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/appserver-io/webserver/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/appserver-io/webserver/?branch=master)

## Introduction

Are you serious? A web server written in pure PHP for PHP? Ohhhh Yes! :) This is a HTTP/1.1 compliant webserver written in php.
And the best... it has a php module and it's multithreaded!

We use this in the [`appserver.io`](<http://www.appserver.io>) project as a server component for handling HTTP requests.

## Installation

If you want to use the web server with your application add this

```sh
{
    "require": {
        "appserver-io/webserver": "dev-master"
    },
}
```

to your ```composer.json``` and invoke ```composer update``` in your project.

Usage
-----
If you can satisfy the requirements it is very simple to use the webserver. Just do this:
```bash
git clone https://github.com/appserver-io/webserver
cd webserver
PHP_BIN=/path/to/your/threadsafe/php-binary bin/webserver
```

If you're using [`appserver.io`](<http://www.appserver.io>) the start line will be:
```bash
bin/webserver
```

Goto http://127.0.0.1:9080 and if all went good, you will see the welcome page of the php webserver.
It will startup on unsecure http port 9080 and secure https port 9443.

To test a php script just goto http://127.0.0.1:9080/info.php and see what happens... ;)

# External Links

* Documentation at [appserver.io](http://docs.appserver.io)