<?php

/**
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

/**
 * This list contains rewrite rules as they would be used within our server configuration.
 * To make tests independent from config parsing we have to provide them already split up.
 *
 * All entries follow the structure below:
 *
 *  '<DATASET_NAME>' => array( // a specific name of the data set
 *      'redirect' => true, // optional, whether or not the listed rewrites are redirects (have to be tested differently)
 *      'redirectAs' => 301, // optional, might contain a custom status code used for redirects (between 300 and 399)
 *      'rules' => array( // array of rule arrays
 *          array(
 *              'condition' => '.*', // condition as one would use it within a rewrite definition
 *              'target' => 'https://www.google.com', // target as one would use it within a rewrite definition
 *              'flag' => 'R' // flag string as one would use it within a rewrite definition
 *          )
 *      ),
 *      'map' => array( // this map contains URI/URL pairs of the sort "incoming URI" => "expected URI/URL after rewrite"
 *          '/html/index.html' => 'https://www.google.com'
 *      )
 *  ),
 *
 * @var array $ruleSets The rewrite rule sets this test is based on
 */
$ruleSets = array(
    'singleBackreference' => array(
        'rules' => array(
            array(
                'condition' => '/html/(.*)\.',
                'target' => '/$1',
                'flag' => ''
            )
        ),
        'map' => array(
            '/html/index.html' => '/index',
            '/html/test.gif' => '/test',
            '/html/failing_test' => '/html/failing_test'
        )
    ),
    'doubleBackreference' => array(
        'rules' => array(
            array(
                'condition' => '/(html)/(.*)\.',
                'target' => '/$1/$2',
                'flag' => ''
            )
        ),
        'map' => array(
            '/html/index.html' => '/html/index',
            '/html/test.gif' => '/html/test',
            '/failing_test' => '/failing_test'
        )
    ),
    'mixedBackreference' => array(
        'rules' => array(
            array(
                'condition' => '/(html)/(.*)\.',
                'target' => '/$2/$1',
                'flag' => ''
            )
        ),
        'map' => array(
            '/html/index.html' => '/index/html',
            '/html/test.gif' => '/test/html',
            '/failing_test' => '/failing_test'
        )
    ),
    'blockingBackreferences' => array(
        'rules' => array(
            array(
                'condition' => '/(ppp){OR}/(html)',
                'target' => '/$1',
                'flag' => 'L'
            )
        ),
        'map' => array(
            '/html/index.html' => '/html',
            '/ppp/test.gif' => '/ppp',
            '/html/test.gif' => '/html'
        )
    )
);
