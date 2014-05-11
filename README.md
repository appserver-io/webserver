TechDivision_WebServer
======================
[![Latest Stable Version](https://poser.pugx.org/techdivision/webserver/v/stable.png)](https://packagist.org/packages/techdivision/webserver) [![Total Downloads](https://poser.pugx.org/techdivision/webserver/downloads.png)](https://packagist.org/packages/techdivision/webserver) [![Latest Unstable Version](https://poser.pugx.org/techdivision/webserver/v/unstable.png)](https://packagist.org/packages/techdivision/webserver) [![License](https://poser.pugx.org/techdivision/webserver/license.png)](https://packagist.org/packages/techdivision/webserver) [![Build Status](https://travis-ci.org/techdivision/TechDivision_WebServer.png)](https://travis-ci.org/techdivision/TechDivision_WebServer)


####Are you serious? A web server written in pure PHP for PHP?

Ohhhh Yes! :) This is a HTTP/1.1 compliant webserver written in php. And the best... it has a php module and it's multithreaded!
We use this in the [`appserver.io`](<http://www.appserver.io>) project as a server component for the WebContainer.

Requirements
------------
If you want to use the php webserver in stand alone mode, you have to prepare several things
* [`PHP`](<http://php.net>) compiled with ZTS Enabled. (Thread Safety)
* [`pthreads`](<https://github.com/krakjoe/pthreads>) PHP extension for Multithreading. (Version 1.0.1 is preferred!)
* [`appserver`](<https://github.com/techdivision/php-ext-appserver>) PHP extension to handle php headers and uploads within a daemon process.

... or just install [`appserver.io`](<http://www.appserver.io>) which provides the perfect runtime environment for the webserver to rock.

Usage
-----
If you can satisfy the requirements it is very simple to use the webserver. Just do this:
```bash
git clone https://github.com/techdivision/TechDivision_WebServer
PHP_BIN=/path/to/your/threadsafe/php-binary TechDivision_WebServer/src/bin/webserver
```
If you're using [`appserver.io`](<http://www.appserver.io>) it'll be this:
```bash
git clone https://github.com/techdivision/TechDivision_WebServer
./TechDivision_WebServer/src/bin/webserver
```

Goto http://127.0.0.1:9080 and if all went good, you will see the welcome page of the php webserver.
It will startup on unsecure http port 9080 and secure https port 9443.

To test a php script just drop a `info.php` to `var/www`.
```php
<pre><?php phpinfo() ?></pre>
```
Now goto http://127.0.0.1:9080/info.php and see what happens... ;)

Configuration
-------------
The phpwebserver can be configured either with xml (default) or json. The demo configuration files are in `etc/`.
If you want to test json configuration format and / or use your own config file, just run the webserver with config argument:
```
src/bin/webserver etc/phpwebserver.json
```

The configuration itself is highly self-explanatory so just have a look to the preferred config file and try to change settings. A detailed overview of all configuration settings will follow...

### VirtualHostModule Configuration

Using virtual hosts you can extend the default server configuration and produce a host specific environment for your
app to run.
You can do so by adding a virtual host configuration to your global server configuration file.
See the example for a XML based configuration below:

```xml
<virtualHost name="example.local">
    <params>
        <param name="admin" type="string">admin@appserver.io</param>
        <param name="documentRoot" type="string">/opt/appserver/webapps/example</param>
    </params>
</virtualHost>
```

The above configuration sits within the server element and opens up the virtual host `example.local` which has a different
document root than the global configuration has. The virtual host is born. :-)
The `virtualHost` element can hold params, rewrite rules or environment variables which are only available for the host specifically.

### EnvironmentVariableModule Configuration

You can set environment variables using either the global or the virtual host based configuration.
The example below shows a basic usage of environment variables in XML format.

```xml
<environmentVariables>
    <environmentVariable condition="" definition="EXAMPLE_VAR=example" />
    <environmentVariable condition="Apple@$HTTP_USER_AGENT" definition="USER_HAS_APPLE=true" />
</environmentVariables>
```

There are several ways in which this feature is used. You can get a rough idea when having a look at Apache modules
[mod_env](<http://httpd.apache.org/docs/2.2/mod/mod_env.html>) and [mod_setenvif](<http://httpd.apache.org/docs/2.2/mod/mod_setenvif.html>) which we adopted.

You can make definitions of environment variables dependent on REGEX based conditions which will be performed on so called backreferences.
These backreferences are request related server variables like `HTTP_USER_AGENT`.
A condition has the format `<REGEX_CONDITION>@$<BACKREFERENCE>`.
If the condition is empty the environment variable will be set every time.

The definition you can use has the form `<NAME_OF_VAR>=<THE_VALUE_TO_SET>`.
The definition has some specialities too:

- Setting a var to `null` will unset the variable if it existed before
- You can use backreferences for the value you want to set as well. But those are limited to environment variables of the PHP process
- Values will be treated as strings

Modules
-------
The request processing workflow is module based within the php web server. Modules can be implemented according to the `\TechDivision\WebServer\Interfaces\ModuleInterface` interface. It needs an initial call of the `init` method and will process any request offered to the `process` method.
Just have a look to the core modules `TechDivision/WebServer/Modules/*Modules.php`

Every module can use various points of executing it's logic by checking the current hook which comes to every process call

```php
public function process(HttpRequestInterface $request, HttpResponseInterface $response, $hook)
{
    // if false hook is comming do nothing
    if (ModuleHooks::REQUEST_POST !== $hook) {
        return;
    }

    // do modules logic
}
```

### Module Hooks

##### ModuleHooks::REQUEST_PRE
The request pre hook should be used to do something before the request will be parsed.
So if there is a keep-alive loop going on this will be triggered every request loop.

##### ModuleHooks::REQUEST_POST
The request post hook should be used to do something after the request has been parsed.
Most modules such as CoreModule will use this hook to do their job.

##### ModuleHooks::RESPONSE_PRE
The response pre hook will be triggered at the point before the response will be prepared.
For sending it to the to the connection endpoint.

##### ModuleHooks::RESPONSE_POST
The response post hook is the last hook triggered within a keep-alive loop and will execute
the modules logic when the response is well prepared and ready to dispatch


Have a look at some of the core modules and their configuration here:

* [`TechDivision_VirtualHostModule`](#virtualhostmodule-configuration)
* [`TechDivision_EnvironmentVariableModule`](#environmentvariablemodule-configuration)

We offer additional modules which are based on external packages. Have a look at them here:

* [`TechDivision_RewriteModule`](<https://github.com/techdivision/TechDivision_RewriteModule>)
* [`TechDivision_ServletModule`](<https://github.com/techdivision/TechDivision_ServletModule>)

Socket Options
--------------

We thought about setting the socket options via configuration to optimize socket handling for specific os but we currently just in testing phase for this so all following text are just notices on socket options and their behaviour in php userland using php sockets.

####Default streaming socket combination

These are the default values for the streaming socket implementation. The values are also the default values for the PHP socket implementation. We actually can't discover any performance improvements by changing one of these values.

* [SO_REUSADDR](#so_reusaddr): 	 4
* [SO_DEBUG](#so_debug): 		 0
* [SO_BROADCAST](#so_broadcast): 0
* [SO_KEEPALIVE](#so_keepalive): 0
* [SO_LINGER](#so_linger): 		 array('l_onoff' => 0, 'l_linger' => 0)
* [SO_OOBINLINE](#so_oobinline): 0
* [SO_SNDBUF](#so_sndbuf): 		 131072
* [SO_RCVBUF](#so_rcvbuf): 		 131072
* [SO_ERROR](#so_error): 		 0
* [SO_TYPE](#so_type): 			 1
* [SO_DONTROUTE](#so_dontroute): 0
* [SO_RCVLOWAT](#so_rcvlowat):   1
* [SO_RCVTIMEO](#so_rcvtimeo):   array('sec' => 0, 'usec' => 0)
* [SO_SNDTIMEO](#so_sndtimeo):   array('sec' => 0, 'usec' => 0)
* [SO_SNDLOWAT](#so_sndlowat):   2048
* [TCP_NODELAY](#tcp_nodelay):   0

#####SO_REUSADDR

TCP's primary design goal is to allow reliable data communication in the face of packet
loss, packet reordering, and — key, here — packet duplication.

It's fairly obvious how a TCP/IP network stack deals with all this while the connection
is up, but there's an edge case that happens just after the connection closes. What
happens if a packet sent right at the end of the conversation is duplicated and delayed,
such that the 4-way shutdown packets get to the receiver before the delayed packet? The
stack dutifully closes down its connection. Then later, the delayed duplicate packet
shows up. What should the stack do?

More importantly, what should it do if the program that owned that connection immediately
dies, then another starts up wanting the same IP address and TCP port number?

There are a couple of choices:

Disallow reuse of that IP/port combo for at least 2 times the maximum time a packet could
be in flight. In TCP, this is usually called the 2×MSL delay. You sometimes also see
2×RTT, which is roughly equivalent.

This is the default behavior of all common TCP/IP stacks. 2×MSL is typically between 30
and 120 seconds. (This is the TIME_WAIT period.) After that time, the stack assumes that
any rogue packets have been dropped en route due to expired TTLs, so it leaves the
TIME_WAIT state, allowing that IP/port combo to be reused.

Allow the new program to re-bind to that IP/port combo. In stacks with BSD sockets
interfaces — essentially all Unixes and Unix-like systems, plus Windows via Winsock —
you have to ask for this behavior by setting the SO_REUSEADDR option via setsockopt()
before you call bind().

SO_REUSEADDR is most commonly set in server programs.

The reason is, a common pattern is that you change a server configuration file and need
to restart that server to make it reload its configuration. Without SO_REUSEADDR, the
bind() call in the restarted program's new instance will fail if there were connections
open to the previous instance when you killed it. Those connections will hold the TCP port
in the TIME_WAIT state for 30-120 seconds, so you fall into case 1 above.

The safe thing to do is wait out the TIME_WAIT period, but in practice this isn't a big
enough risk that it's worth doing that. It's better to get the server back up immediately
so as to not miss any more incoming connections than necessary

* [stackoverflow](http://stackoverflow.com/questions/3229860/what-is-the-meaning-of-so-reuseaddr-setsockopt-option-linux)

#####SO_DEBUG
Still to come.

#####SO_BROADCAST
Still to come.

#####SO_KEEPALIVE
Still to come.

#####SO_LINGER
Still to come.

#####SO_OOBINLINE
Still to come.

#####SO_SNDBUF
Still to come.

#####SO_RCVBUF
Still to come.

#####SO_ERROR
Still to come.

#####SO_TYPE
Still to come.

#####SO_DONTROUTE
Still to come.

#####SO_RCVLOWAT
Still to come.

#####SO_RCVTIMEO
Still to come.

#####SO_SNDTIMEO
Still to come.

#####SO_SNDLOWAT
Still to come.

#####TCP_NODELAY
Still to come.
