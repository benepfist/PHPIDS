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
 * APC caching wrapper
 *
 * This class inhabits functionality to get and set cache via memcached.
 *
 * @category  Security
 * @package   PHPIDS
 * @author    Yves Berkholz <godzilla80@gmx.net>
 * @copyright 2007-2009 The PHPIDS Groupoup
 * @license   http://www.gnu.org/licenses/lgpl.html LGPL
 * @link      http://php-ids.org/
 * @since     Version 0.6.5
 */
class ApcCache implements CacheInterface
{

    /**
     * Cache configuration
     *
     * @var array<string, mixed>
     */
    private $config = null;

    /**
     * Flag if the filter storage has been found in memcached
     *
     * @var boolean
     */
    private $isCached = false;

    /**
     * Holds an instance of this class
     *
     * @var self|null
     */
    private static $cachingInstance = null;

    /**
     * Constructor
     *
     * @param \IDS\Init $init the IDS_Init object
     *
     * @return void
     */
    public function __construct($init)
    {
        /** @var array<string, mixed> $config */
        $config = $init->config['Caching'];
        $this->config = $config;
    }

    /**
     * Returns an instance of this class
     *
     * @param \IDS\Init $init the IDS_Init object
     *
     * @return object $this
     */
    public static function getInstance($init)
    {
        if (!self::$cachingInstance) {
            self::$cachingInstance = new ApcCache($init);
        }

        return self::$cachingInstance;
    }

    /**
     * Writes cache data
     *
     * @param array<int|string, mixed> $data the caching data
     *
     * @return object $this
     */
    public function setCache(array $data)
    {
        if (!$this->isCached) {
            /** @var int $ttl */
            $ttl = $this->config['expiration_time'];
            apc_store(
                $this->config['key_prefix'] . '.storage',
                $data,
                $ttl
            );
        }

        return $this;
    }

    /**
     * Returns the cached data
     *
     * Note that this method returns false if either type or file cache is
     * not set
     *
     * @return mixed cache data or false
     */
    public function getCache()
    {
        $data = apc_fetch($this->config['key_prefix'] . '.storage');
        $this->isCached = !empty($data);

        return $data;
    }
}
