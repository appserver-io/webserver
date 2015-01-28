<?php

/**
 * \AppserverIo\WebServer\Modules\RewriteMap\TotalIntegrationRewriteMapper
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Bernhard Wick <bw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */

namespace AppserverIo\WebServer\Modules\RewriteMap;

use AppserverIo\WebServer\Interfaces\RewriteMapperInterface;

/**
 * Class TotalIntegrationRewriteMap
 *
 * This class is able to provide a "total integration" mapping which will relate two tables over a join
 * so one might redirect to specific foreign URLs.
 *
 * @author    Bernhard Wick <bw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class TotalIntegrationRewriteMapper implements RewriteMapperInterface
{

    /**
     * Constructs the mapper
     *
     * @param array $params The array of params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * Look's up a target url for given request url
     *
     * @param string $requestUri The requested url without query params
     *
     * @throws \InvalidArgumentException
     *
     * @return null|string
     */
    public function lookup($requestUri)
    {
        // set targetUrl to null by default
        $targetUrl = null;
        // set base to local ref
        $base = $this->params['base'];

        // Get the requested host and strip it of the port (if any)
        $host = $this->params['headerHost'];
        if (strpos($host, ':') !== false) {
            $host = strstr($host, ':', true);
        }

        // check if request path matches to base. if not we don't need to do anything.
        if (strpos($requestUri, $base) !== false) {
            // connect to db
            $db = new \PDO($this->params['dsn'], $this->params['username'], $this->params['password']);

            // get table names
            if (isset($this->params['rewriteTableName']) && isset($this->params['hostTableName'])) {
                $rewriteTableName = $this->params['rewriteTableName'];
                $hostTableName = $this->params['hostTableName'];
            } else {
                throw new \InvalidArgumentException('Missing at least one essential table name: "rewriteTableName" or "hostTableName".');
            }

            // Build up the query containing
            $query = $db->query(
                "SELECT $rewriteTableName.target FROM $rewriteTableName, $hostTableName
                WHERE $rewriteTableName.uri = '$requestUri'
                AND $hostTableName.name = '$host'
                AND $rewriteTableName.customer = $hostTableName.customer;"
            );

            // Check if we got something useful
            if (is_a($query, '\PDOStatement')) {
                $targetEntry = $query->fetch(\PDO::FETCH_OBJ);

                // check if target was found and set target url for return
                if (is_object($targetEntry) && isset($targetEntry->target)) {
                    $targetUrl = $targetEntry->target;
                }
            }
            // disconnect PDO database and YES... this is the right way... read PDO documentation.
            $db = null;
        }

        return $targetUrl;
    }

    /**
     * Prepares the module for upcoming request in specific context
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function prepare()
    {
        // nothing to prepare for this module
    }
}
