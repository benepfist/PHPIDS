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
 * File caching wrapper
 *
 * This class inhabits functionality to get and set cache via session.
 *
 * @category  Security
 * @package   PHPIDS
 * @author    Christian Matthies <ch0012@gmail.com>
 * @author    Mario Heiderich <mario.heiderich@gmail.com>
 * @author    Lars Strojny <lars@strojny.net>
 * @copyright 2007-2009 The PHPIDS Group
 * @license   http://www.gnu.org/licenses/lgpl.html LGPL
 * @link      http://php-ids.org/
 * @since     Version 0.4
 */
class SessionCache implements CacheInterface
{
    /**
     * Caching type
     *
     * @var string
     */
    private $type = null;


    /**
     * Holds an instance of this class
     *
     * @var self|null
     */
    private static $cachingInstance = null;

    /**
     * Constructor
     *
     * @param string $type caching type
     * @param \IDS\Init $init the IDS_Init object
     *
     * @return void
     */
    public function __construct($type, $init)
    {
        $this->type   = $type;
        unset($init);
    }

    /**
     * Returns an instance of this class
     *
     * @param string   $type caching type
     * @param \IDS\Init $init the IDS_Init object
     *
     * @return object $this
     */
    public static function getInstance($type, $init)
    {

        if (!self::$cachingInstance) {
            self::$cachingInstance = new SessionCache($type, $init);
        }

        return self::$cachingInstance;
    }

    /**
     * Writes cache data into the session
     *
     * @param array<int|string, mixed> $data the caching data
     *
     * @return object $this
     */
    public function setCache(array $data)
    {

        $_SESSION['PHPIDS'][$this->type] = $data;

        return $this;
    }

    /**
     * Returns the cached data
     *
     * Note that this method returns false if either type or file cache is not set
     *
     * @return mixed cache data or false
     */
    public function getCache()
    {

        if ($this->type && $_SESSION['PHPIDS'][$this->type]) {
            return $_SESSION['PHPIDS'][$this->type];
        }

        return false;
    }
}
