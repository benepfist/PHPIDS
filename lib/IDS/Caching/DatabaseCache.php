<?php
/**
 * PHPIDS
 *
 * Requirements: PHP5, SimpleXML
 *
 * Copyright (c) 2008 PHPIDS group (https://phpids.org)
 *
 * PHPIDS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, version 3 of the License, or
 * (at your option) any later version.
 *
 * PHPIDS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with PHPIDS. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5.1.6+
 *
 * @category Security
 * @package  PHPIDS
 * @author   Mario Heiderich <mario.heiderich@gmail.com>
 * @author   Christian Matthies <ch0012@gmail.com>
 * @author   Lars Strojny <lars@strojny.net>
 * @license  http://www.gnu.org/licenses/lgpl.html LGPL
 * @link     http://php-ids.org/
 */
namespace IDS\Caching;

/**
 *
 */

/**
 * Database caching wrapper
 *
 * This class inhabits functionality to get and set cache via a database.
 *
 * Needed SQL:
 *

#create the database

CREATE DATABASE IF NOT EXISTS `phpids` DEFAULT CHARACTER
SET utf8 COLLATE utf8_general_ci;
DROP TABLE IF EXISTS `cache`;

#now select the created datbase and create the table

CREATE TABLE `cache` (
`type` VARCHAR( 32 ) NOT null ,
`data` TEXT NOT null ,
`created` DATETIME NOT null ,
`modified` DATETIME NOT null
) ENGINE = MYISAM ;
 *
 * @category  Security
 * @package   PHPIDS
 * @author    Christian Matthies <ch0012@gmail.com>
 * @author    Mario Heiderich <mario.heiderich@gmail.com>
 * @author    Lars Strojny <lars@strojny.net>
 * @copyright 2007-2009 The PHPIDS Groupup
 * @license   http://www.gnu.org/licenses/lgpl.html LGPL
 * @link      http://php-ids.org/
 * @since     Version 0.4
 */
class DatabaseCache implements CacheInterface
{

    /**
     * Caching type
     *
     * @var string
     */
    private $type = null;

    /**
     * Cache configuration
     *
     * @var array<string, mixed>
     */
    private $config = null;

    /**
     * Database connection handle
     *
     * @var \PDO
     */
    private $handle;

    /**
     * Holds an instance of this class
     *
     * @var self|null
     */
    private static $cachingInstance = null;

    /**
     * Constructor
     *
     * Connects to database.
     *
     * @param string   $type caching type
     * @param \IDS\Init $init the IDS_Init object
     *
     * @return void
     */
    public function __construct($type, $init)
    {
        $this->type   = $type;
        /** @var array<string, mixed> $config */
        $config       = $init->config['Caching'];
        $this->config = $config;
        $this->handle = $this->connect();
    }

    /**
     * Returns an instance of this class
     *
     * @static
     * @param string   $type caching type
     * @param \IDS\Init $init the IDS_Init object
     *
     * @return object $this
     */
    public static function getInstance($type, $init)
    {

        if (!self::$cachingInstance) {
            self::$cachingInstance = new DatabaseCache($type, $init);
        }

        return self::$cachingInstance;
    }

    /**
     * Writes cache data into the database
     *
     * @param array<int|string, mixed> $data the caching data
     *
     * @throws \PDOException if a db error occurred
     * @return object       $this
     */
    public function setCache(array $data)
    {
        $handle = $this->handle;

        $rows = $handle->query('SELECT created FROM `' . $this->config['table'].'`');

        if (!$rows || $rows->rowCount() === 0) {

            $this->write($handle, $data);
        } else {

            foreach ($rows as $row) {

                /** @var int $ttl */
                $ttl = $this->config['expiration_time'];
                if ((time() - strtotime((string) $row['created'])) >
                    $ttl) {

                    $this->write($handle, $data);
                }
            }
        }

        return $this;
    }

    /**
     * Returns the cached data
     *
     * Note that this method returns false if either type or file cache is
     * not set
     *
     * @throws \PDOException if a db error occurred
     * @return mixed        cache data or false
     */
    public function getCache()
    {
        try {
            $handle = $this->handle;
            $result = $handle->prepare(
                'SELECT * FROM `' .
                $this->config['table'] .
                '` where type=?'
            );
            $result->execute(array($this->type));

            foreach ($result as $row) {
                /** @var string $data */
                $data = $row['data'];
                return unserialize($data);
            }

        } catch (\PDOException $e) {
            throw new \PDOException('PDOException: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Connect to database and return a handle
     *
     * @return \PDO       PDO
     * @throws \Exception    if connection parameters are faulty
     * @throws \PDOException if a db error occurred
     */
    private function connect(): \PDO
    {
        // validate connection parameters
        if (!$this->config['wrapper']
            || !$this->config['user']
                || !$this->config['password']
                    || !$this->config['table']) {

            throw new \Exception('Insufficient connection parameters');
        }

        // try to connect
        try {
            /** @var string $dsn */
            $dsn = $this->config['wrapper'];
            /** @var string $user */
            $user = $this->config['user'];
            /** @var string $password */
            $password = $this->config['password'];
            $handle = new \PDO(
                $dsn,
                $user,
                $password
            );
            $handle->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        } catch (\PDOException $e) {
            throw new \PDOException('PDOException: ' . $e->getMessage());
        }

        return $handle;
    }

    /**
     * Write the cache data to the table
     *
     * @param \PDO   $handle the database handle
     * @param array<int|string, mixed>  $data   the caching data
     *
     * @return void
     * @throws \PDOException if a db error occurred
     */
    private function write(\PDO $handle, $data)
    {
        try {
            $handle->query('TRUNCATE ' . $this->config['table'].'');
            $statement = $handle->prepare(
                'INSERT INTO `' .
                $this->config['table'].'` (
                    type,
                    data,
                    created,
                    modified
                )
                VALUES (
                    :type,
                    :data,
                    now(),
                    now()
                )'
            );

            $statement->bindValue(
                'type',
                $handle->quote($this->type)
            );
            $statement->bindValue('data', serialize($data));

            if (!$statement->execute()) {
                throw new \PDOException($statement->errorCode());
            }
        } catch (\PDOException $e) {
            throw new \PDOException('PDOException: ' . $e->getMessage());
        }
    }
}
