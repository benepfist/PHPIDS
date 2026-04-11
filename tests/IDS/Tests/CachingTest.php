<?php
/**
 * PHPIDS
 * Requirements: PHP 8.4, SimpleXML
 *
 * Copyright (c) 2010 PHPIDS group (https://phpids.org)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the license.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @package	PHPIDS tests
 */
namespace IDS\Tests;

use IDS\Init;
use IDS\Caching\ApcCache;
use IDS\Caching\CacheFactory;
use IDS\Caching\DatabaseCache;
use IDS\Caching\FileCache;
use IDS\Caching\MemcachedCache;
use IDS\Caching\SessionCache;
use IDS\Tests\Support\RuntimeFunctionOverrides;

class CachingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Init
     */
    protected $init;

    /**
     * @var list<string>
     */
    private array $tempFiles = [];

    protected function setUp(): void {
        $config = parse_ini_file(IDS_CONFIG, true);
        $this->init = new Init($config === false ? [] : $config);
        $this->init->config['Caching']['key_prefix'] = 'phpids-test';
        RuntimeFunctionOverrides::reset();
    }

    public function testCachingNone()
    {
        $this->init->config['Caching']['caching'] = 'none';
        $this->assertFalse(CacheFactory::factory($this->init, 'storage'));
    }

    public function testCachingFile()
    {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['expiration_time'] = 0;
        $this->assertTrue(CacheFactory::factory($this->init, 'storage') instanceof FileCache);
    }

    public function testCachingFileGetInstanceReturnsFileCache()
    {
        $this->init->config['Caching']['path'] = IDS_FILTER_CACHE_FILE;

        $this->assertInstanceOf(FileCache::class, FileCache::getInstance($this->init));
    }

    public function testCachingFileSetCache()
    {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['expiration_time'] = 0;
        $this->init->config['Caching']['path'] = IDS_FILTER_CACHE_FILE;
        $cache = CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $this->assertTrue($cache instanceof FileCache);
    }

    public function testCachingFileGetCache()
    {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['expiration_time'] = 0;
        $this->init->config['Caching']['path'] = IDS_FILTER_CACHE_FILE;
        $cache = CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $this->assertEquals($cache->getCache(), array(1,2,3,4));
    }

    public function testCachingFileGetCacheReturnsFalseWhenFileIsMissing()
    {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['expiration_time'] = 0;
        $this->init->config['Caching']['path'] = IDS_FILTER_CACHE_FILE;

        $cache = CacheFactory::factory($this->init, 'storage');

        $this->assertFalse($cache->getCache());
    }

    public function testCachingFileGetCacheReturnsFalseForDirectoryPath()
    {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['expiration_time'] = 0;
        $this->init->config['Caching']['path'] = 'tmp';

        $cache = new FileCache($this->init);

        $this->assertFalse($cache->getCache());
    }

    public function testCachingFileGetCacheExpiresWhenTtlIsPositive()
    {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['expiration_time'] = 1;
        $this->init->config['Caching']['path'] = IDS_FILTER_CACHE_FILE;

        $cache = CacheFactory::factory($this->init, 'storage');
        $cache->setCache(array(1,2,3,4));

        sleep(2);

        $this->assertFalse($cache->getCache());
    }

    public function testCachingFileSetCacheThrowsForUnwritableDirectory()
    {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['expiration_time'] = 0;
        $this->init->config['Caching']['path'] = 'Z:/phpids/does-not-exist/default_filter.cache';

        $cache = CacheFactory::factory($this->init, 'storage');

        $this->expectException(\Exception::class);
        $cache->setCache(array(1,2,3,4));
    }

    public function testCachingFileConstructorThrowsForReadonlyCacheFile()
    {
        $file = tempnam(sys_get_temp_dir(), 'phpids-cache-readonly-');
        if ($file === false) {
            $this->fail('Unable to create readonly cache fixture.');
        }

        file_put_contents($file, 'fixture');
        chmod($file, 0444);

        $this->init->config['Caching']['path'] = $file;

        try {
            $this->expectException(\Exception::class);
            new FileCache($this->init);
        } finally {
            chmod($file, 0644);
            @unlink($file);
        }
    }

    public function testCachingFileSetCacheThrowsWhenCacheFileCannotBeCreated()
    {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['expiration_time'] = 0;
        $this->init->config['Caching']['path'] = IDS_FILTER_CACHE_FILE;

        $cache = CacheFactory::factory($this->init, 'storage');
        RuntimeFunctionOverrides::$cachingFopenFailures[IDS_FILTER_CACHE_FILE] = true;

        $this->expectException(\Exception::class);
        $cache->setCache(array(1,2,3,4));
    }

    public function testCachingFileSetCacheThrowsWhenSerializationFails()
    {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['expiration_time'] = 0;
        $this->init->config['Caching']['path'] = IDS_FILTER_CACHE_FILE;

        $cache = CacheFactory::factory($this->init, 'storage');
        RuntimeFunctionOverrides::$forceCachingSerializeFailure = true;

        $this->expectException(\Exception::class);
        $cache->setCache(array(1,2,3,4));
    }

    public function testCachingFileSetCacheLeavesExistingValidCacheUntouched()
    {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['expiration_time'] = 0;
        $this->init->config['Caching']['path'] = IDS_FILTER_CACHE_FILE;

        file_put_contents(IDS_FILTER_CACHE_FILE, serialize(['existing']));

        $cache = CacheFactory::factory($this->init, 'storage');
        $cache->setCache(array(1,2,3,4));

        $this->assertSame(['existing'], $cache->getCache());
    }

    public function testCachingSession()
    {
        $this->init->config['Caching']['caching'] = 'session';
        $this->assertTrue(CacheFactory::factory($this->init, 'storage') instanceof SessionCache);
    }

    public function testCachingSessionSetCache()
    {
        $this->init->config['Caching']['caching'] = 'session';

        $cache = CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $this->assertTrue($cache instanceof SessionCache);
    }

    public function testCachingSessionGetCache()
    {
        $this->init->config['Caching']['caching'] = 'session';

        $cache = CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $this->assertEquals($cache->getCache(), array(1,2,3,4));
    }

    public function testCachingSessionGetCacheDestroyed()
    {
        $this->init->config['Caching']['caching'] = 'session';

        $cache = CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $_SESSION['PHPIDS']['storage'] = null;
        $this->assertFalse($cache->getCache());
    }

    public function testCachingApcGetInstanceReturnsApcCache()
    {
        $this->assertInstanceOf(ApcCache::class, ApcCache::getInstance($this->init));
    }

    public function testCachingApcSetAndGetCache()
    {
        $cache = new ApcCache($this->init);

        $cache->setCache(['value']);

        $this->assertSame(['value'], $cache->getCache());
    }

    public function testCachingApcDoesNotOverwriteExistingCachedValue()
    {
        $cache = new ApcCache($this->init);
        $cache->setCache(['existing']);
        $cache->getCache();

        $cache->setCache(['replacement']);

        $this->assertSame(['existing'], $cache->getCache());
    }

    public function testCachingMemcachedGetInstanceReturnsMemcachedCache()
    {
        $this->init->config['Caching']['host'] = '127.0.0.1';
        $this->init->config['Caching']['port'] = 11211;

        $this->assertInstanceOf(MemcachedCache::class, MemcachedCache::getInstance($this->init));
    }

    public function testCachingMemcachedSetAndGetCache()
    {
        $this->init->config['Caching']['host'] = '127.0.0.1';
        $this->init->config['Caching']['port'] = 11211;

        $cache = new MemcachedCache($this->init);
        $cache->setCache(['value']);

        $this->assertSame(['value'], $cache->getCache());
    }

    public function testCachingMemcachedThrowsForInsufficientConnectionParameters()
    {
        $this->init->config['Caching']['host'] = '';
        $this->init->config['Caching']['port'] = 0;

        $this->expectException(\Exception::class);
        new MemcachedCache($this->init);
    }

    public function testCachingDatabaseThrowsForInsufficientConnectionParameters()
    {
        $this->init->config['Caching']['wrapper'] = '';
        $this->init->config['Caching']['user'] = '';
        $this->init->config['Caching']['password'] = '';
        $this->init->config['Caching']['table'] = '';

        $this->expectException(\Exception::class);
        new DatabaseCache('storage', $this->init);
    }

    public function testCachingDatabaseGetInstanceReturnsDatabaseCache()
    {
        $sqliteFile = $this->createSqliteCacheDatabase();
        $this->configureSqliteDatabaseCache($sqliteFile);

        $this->assertInstanceOf(DatabaseCache::class, DatabaseCache::getInstance('storage', $this->init));
    }

    public function testCachingDatabaseGetCacheReturnsFalseWhenNoRowsExist()
    {
        $sqliteFile = $this->createSqliteCacheDatabase();
        $this->configureSqliteDatabaseCache($sqliteFile);

        $cache = new DatabaseCache('storage', $this->init);

        $this->assertFalse($cache->getCache());
    }

    public function testCachingDatabaseGetCacheReturnsStoredPayload()
    {
        $sqliteFile = $this->createSqliteCacheDatabase();
        $this->configureSqliteDatabaseCache($sqliteFile);

        $pdo = new \PDO('sqlite:' . $sqliteFile, 'user', 'pass');
        $statement = $pdo->prepare(
            'INSERT INTO cache (type, data, created, modified) VALUES (:type, :data, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $statement->execute([
            ':type' => 'storage',
            ':data' => serialize(['cached']),
        ]);

        $cache = new DatabaseCache('storage', $this->init);

        $this->assertSame(['cached'], $cache->getCache());
    }

    public function testCachingDatabaseSetCacheThrowsWhenWriteFails()
    {
        $sqliteFile = $this->createSqliteCacheDatabase();
        $this->configureSqliteDatabaseCache($sqliteFile);

        $cache = new DatabaseCache('storage', $this->init);

        $this->expectException(\PDOException::class);
        $cache->setCache(['value']);
    }

    protected function tearDown(): void {
        RuntimeFunctionOverrides::reset();
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
        @unlink(IDS_FILTER_CACHE_FILE);
    }

    private function createSqliteCacheDatabase(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'phpids-cache-sqlite-');
        if ($file === false) {
            $this->fail('Unable to create temporary SQLite database.');
        }

        $pdo = new \PDO('sqlite:' . $file, 'user', 'pass');
        $pdo->exec(
            'CREATE TABLE cache (
                type TEXT NOT NULL,
                data TEXT NOT NULL,
                created TEXT NOT NULL,
                modified TEXT NOT NULL
            )'
        );

        $this->tempFiles[] = $file;

        return $file;
    }

    private function configureSqliteDatabaseCache(string $sqliteFile): void
    {
        $this->init->config['Caching']['wrapper'] = 'sqlite:' . $sqliteFile;
        $this->init->config['Caching']['user'] = 'user';
        $this->init->config['Caching']['password'] = 'pass';
        $this->init->config['Caching']['table'] = 'cache';
        $this->init->config['Caching']['expiration_time'] = 60;
    }
}
