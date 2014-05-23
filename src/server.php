<?php
/**
 * server.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category  WebServer
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/TechDivision_Server
 */

require('../vendor/techdivision/server/src/TechDivision/Server/Standalone.php');

// check if first argument is given for configuration
if (isset($argv[1])) {
    $config = $argv[1];
} else {
    $config = 'etc/phpwebserver.xml';
}

$server = new \TechDivision\Server\Standalone(__DIR__, $config, '../vendor/autoload.php');
$server->start();