<?php

/**
 * \AppserverIo\WebServer\Modules\RewriteMap\MagentoRewriteMapper
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */

namespace AppserverIo\WebServer\Modules\RewriteMap;

use AppserverIo\WebServer\Interfaces\RewriteMapperInterface;

/**
 * Class MagentoRewriteMapper
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class MagentoRewriteMapper implements RewriteMapperInterface
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
     * Looks up a target url for given request url
     *
     * @param string $requestUrl The requested url without query params
     *
     * @return null|string
     */
    public function lookup($requestUrl)
    {
        // set targetUrl to null by default
        $targetUrl = null;
        // set base to local ref
        $base = $this->params['base'];

        // check if request path matches to base. if not we don't need to do anything.
        // find store code from magento.
        // important: be sure that magento is configured to add store codes to url!
        if (preg_match('/^' . preg_quote($base, '/') . '\/([a-z0-9_]+)/', $requestUrl, $matches)) {
            // get store code
            $storeCode = $matches[1];

            // connect to db
            $db = new \PDO($this->params['dsn'], $this->params['username'], $this->params['password']);

            // get table names
            $storeTableName = 'core_store';
            if (isset($this->params['storeTableName'])) {
                $storeTableName = $this->params['storeTableName'];
            }
            $rewriteTableName = 'core_url_rewrite';
            if (isset($this->params['rewriteTableName'])) {
                $rewriteTableName = $this->params['rewriteTableName'];
            }

            // get magento store entry by given store code string
            $query = $db->query("select * from $storeTableName where code = '$storeCode'");
            if ($magentoStore = $query->fetch(\PDO::FETCH_OBJ)) {
                // build up base url
                $baseUrl = $base . '/' . $storeCode . '/';

                // build magento request path for comparison in core_url_rewrite table
                $magentoRequestPath = str_replace($baseUrl, '', $requestUrl);

                // get magento url rewrite
                $query = $db->query(
                    "select * from $rewriteTableName
                    where request_path = '$magentoRequestPath'
                    and store_id = '$magentoStore->store_id'
                    and redirect_type=301"
                );

                // Check if we got something useful
                if (is_a($query, '\PDOStatement')) {
                    // try to fetch the rewrite URL
                    $magentoUrlRewrite = $query->fetch(\PDO::FETCH_OBJ);

                    // check if target_path was found and set target url for return
                    if (isset($magentoUrlRewrite->target_path)) {
                        $targetUrl .= $this->params['protocol'] . $this->params['headerHost'] . $baseUrl . $magentoUrlRewrite->target_path;
                    }
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
