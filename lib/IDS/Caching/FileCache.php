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

use IDS\Init;

/**
 * File caching wrapper
 *
 * This class inhabits functionality to get and set cache via a static flatfile.
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
class FileCache implements CacheInterface
{

    /**
     * Cache configuration
     *
     * @var array<string, mixed>
     */
    private $config;

    /**
     * Path to cache file
     *
     * @var string
     */
    private $path;

    /**
     * Holds an instance of this class
     *
     * @var self|null
     */
    private static $cachingInstance;

    /**
     * Constructor
     *
     * @param \IDS\Init $init the IDS_Init object
     * @throws \Exception
     *
     * @return void
     */
    public function __construct(Init $init)
    {
        /** @var array<string, mixed> $config */
        $config = $init->config['Caching'];
        $this->config = $config;
        $this->path   = $init->getBasePath() . $this->config['path'];

        if (file_exists($this->path) && !is_writable($this->path)) {
            throw new \Exception(
                'Make sure all files in ' .
                htmlspecialchars($this->path, ENT_QUOTES, 'UTF-8') .
                'are writeable!'
            );
        }
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
            self::$cachingInstance = new FileCache($init);
        }

        return self::$cachingInstance;
    }

    /**
     * Writes cache data into the file
     *
     * @param array<int|string, mixed> $data the cache data
     *
     * @throws \Exception if cache file couldn't be created
     * @return object    $this
     */
    public function setCache(array $data)
    {
        $directory = dirname($this->path);
        if (!is_writable($directory)) {
            throw new \Exception(
                'Temp directory ' .
                htmlspecialchars($this->path, ENT_QUOTES, 'UTF-8') .
                ' seems not writable'
            );
        }

        if (!$this->isValidFile($this->path)) {
            $handle = @fopen($this->path, 'w+');

            if (!$handle) {
                throw new \Exception("Cache file couldn't be created");
            }

            $serialized = @serialize($data);
            if (!$serialized) {
                throw new \Exception("Cache data couldn't be serialized");
            }

            fwrite($handle, $serialized);
            fclose($handle);
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
        // make sure filters are parsed again if cache expired
        if (!$this->isValidFile($this->path)) {
            return false;
        }

        $content = file_get_contents($this->path);
        if ($content === false) {
            return false;
        }

        $data = unserialize($content);

        return $data;
    }

    /**
     * Returns true if the cache file is still valid
     *
     * @param  string $file
     * @return bool
     */
    private function isValidFile($file)
    {
        /** @var int $ttl */
        $ttl = $this->config['expiration_time'];
        return file_exists($file) && time() - filectime($file) <= $ttl;
    }
}
