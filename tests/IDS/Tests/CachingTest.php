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

class FakePdoStatement extends \PDOStatement implements \IteratorAggregate
{
    /**
     * @var list<array<string, mixed>>
     */
    public array $rows = [];

    public int $rowCountValue = 0;

    public bool $executeResult = true;

    public string $errorCodeValue = 'HY000';

    /**
     * @var array<string, mixed>
     */
    public array $boundValues = [];

    public function __construct()
    {
    }

    public function rowCount(): int
    {
        return $this->rowCountValue;
    }

    public function execute(?array $params = null): bool
    {
        return $this->executeResult;
    }

    public function bindValue($param, $value, $type = \PDO::PARAM_STR): bool
    {
        $this->boundValues[(string) $param] = $value;

        return true;
    }

    public function errorCode(): ?string
    {
        return $this->errorCodeValue;
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->rows);
    }
}

class FakePdo extends \PDO
{
    public ?FakePdoStatement $selectStatement = null;

    public ?FakePdoStatement $insertStatement = null;

    public bool $throwOnSelectPrepare = false;

    public string $quotedValue = "'storage'";

    public function __construct()
    {
    }

    public function setAttribute($attribute, $value): bool
    {
        return true;
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): \PDOStatement|false
    {
        return $this->selectStatement;
    }

    public function prepare(string $query, array $options = []): \PDOStatement|false
    {
        if ($this->throwOnSelectPrepare && str_contains($query, 'SELECT *')) {
            throw new \PDOException('select failed');
        }

        return $this->insertStatement;
    }

    public function quote(string $string, int $type = \PDO::PARAM_STR): string|false
    {
        return $this->quotedValue;
    }
}

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
        $cache = $cache->setCache([1, 2, 3, 4]);
        $this->assertTrue($cache instanceof FileCache);
    }

    public function testCachingFileGetCache()
    {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['expiration_time'] = 0;
        $this->init->config['Caching']['path'] = IDS_FILTER_CACHE_FILE;
        $cache = CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache([1, 2, 3, 4]);
        $this->assertEquals($cache->getCache(), [1, 2, 3, 4]);
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
        $cache->setCache([1, 2, 3, 4]);

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
        $cache->setCache([1, 2, 3, 4]);
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
        $cache->setCache([1, 2, 3, 4]);
    }

    public function testCachingFileSetCacheThrowsWhenSerializationFails()
    {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['expiration_time'] = 0;
        $this->init->config['Caching']['path'] = IDS_FILTER_CACHE_FILE;

        $cache = CacheFactory::factory($this->init, 'storage');
        RuntimeFunctionOverrides::$forceCachingSerializeFailure = true;

        $this->expectException(\Exception::class);
        $cache->setCache([1, 2, 3, 4]);
    }

    public function testCachingFileSetCacheLeavesExistingValidCacheUntouched()
    {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['expiration_time'] = 0;
        $this->init->config['Caching']['path'] = IDS_FILTER_CACHE_FILE;

        file_put_contents(IDS_FILTER_CACHE_FILE, serialize(['existing']));

        $cache = CacheFactory::factory($this->init, 'storage');
        $cache->setCache([1, 2, 3, 4]);

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
        $cache = $cache->setCache([1, 2, 3, 4]);
        $this->assertTrue($cache instanceof SessionCache);
    }

    public function testCachingSessionGetCache()
    {
        $this->init->config['Caching']['caching'] = 'session';

        $cache = CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache([1, 2, 3, 4]);
        $this->assertEquals($cache->getCache(), [1, 2, 3, 4]);
    }

    public function testCachingSessionGetCacheDestroyed()
    {
        $this->init->config['Caching']['caching'] = 'session';

        $cache = CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache([1, 2, 3, 4]);
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

    public function testCachingDatabaseThrowsForInvalidDsn()
    {
        $this->init->config['Caching']['wrapper'] = 'invalid-driver:';
        $this->init->config['Caching']['user'] = 'user';
        $this->init->config['Caching']['password'] = 'pass';
        $this->init->config['Caching']['table'] = 'cache';

        $this->expectException(\PDOException::class);
        new DatabaseCache('storage', $this->init);
    }

    public function testCachingDatabaseGetCacheThrowsWhenSelectFails()
    {
        $sqliteFile = $this->createSqliteCacheDatabase();
        $this->configureSqliteDatabaseCache($sqliteFile);

        $cache = new DatabaseCache('storage', $this->init);
        $this->init->config['Caching']['table'] = 'missing_cache';

        $reflection = new \ReflectionProperty(DatabaseCache::class, 'config');
        $reflection->setValue($cache, $this->init->config['Caching']);

        $this->expectException(\PDOException::class);
        $cache->getCache();
    }

    public function testCachingDatabaseSetCacheThrowsWhenWriteFails()
    {
        $sqliteFile = $this->createSqliteCacheDatabase();
        $this->configureSqliteDatabaseCache($sqliteFile);

        $cache = new DatabaseCache('storage', $this->init);

        $this->expectException(\PDOException::class);
        $cache->setCache(['value']);
    }

    public function testCachingDatabaseSetCacheWritesWhenNoRowsExist()
    {
        $cache = $this->createDatabaseCacheWithFakeHandle(
            selectStatement: $this->createFakeStatement(rowCount: 0),
            insertStatement: $this->createFakeStatement()
        );

        $result = $cache->setCache(['written']);

        $this->assertSame($cache, $result);
    }

    public function testCachingDatabaseSetCacheIteratesExpiredRowsBeforeFailingWrite()
    {
        $sqliteFile = $this->createSqliteCacheDatabase();
        $this->configureSqliteDatabaseCache($sqliteFile);
        $this->insertDatabaseCacheRow($sqliteFile, 'storage', ['expired'], '2000-01-01 00:00:00');

        $cache = new DatabaseCache('storage', $this->init);

        $this->expectException(\PDOException::class);
        $cache->setCache(['replacement']);
    }

    public function testCachingDatabaseSetCacheWritesWhenRowsAreExpired()
    {
        $select = $this->createFakeStatement(
            rowCount: 1,
            rows: [['created' => '2000-01-01 00:00:00']]
        );
        $insert = $this->createFakeStatement();
        $cache = $this->createDatabaseCacheWithFakeHandle(
            selectStatement: $select,
            insertStatement: $insert
        );

        $cache->setCache(['expired']);

        $this->assertArrayHasKey('type', $insert->boundValues);
        $this->assertArrayHasKey('data', $insert->boundValues);
    }

    public function testCachingDatabaseSetCacheThrowsWhenStatementExecuteFails()
    {
        $cache = $this->createDatabaseCacheWithFakeHandle(
            selectStatement: $this->createFakeStatement(rowCount: 0),
            insertStatement: $this->createFakeStatement(executeResult: false, errorCode: '23000')
        );

        $this->expectException(\PDOException::class);
        $cache->setCache(['broken']);
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

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function createFakeStatement(
        int $rowCount = 0,
        array $rows = [],
        bool $executeResult = true,
        string $errorCode = 'HY000'
    ): FakePdoStatement {
        $statement = new FakePdoStatement();
        $statement->rowCountValue = $rowCount;
        $statement->rows = $rows;
        $statement->executeResult = $executeResult;
        $statement->errorCodeValue = $errorCode;

        return $statement;
    }

    private function createDatabaseCacheWithFakeHandle(
        FakePdoStatement $selectStatement,
        FakePdoStatement $insertStatement
    ): DatabaseCache {
        $sqliteFile = $this->createSqliteCacheDatabase();
        $this->configureSqliteDatabaseCache($sqliteFile);
        $cache = new DatabaseCache('storage', $this->init);

        $handle = new FakePdo();
        $handle->selectStatement = $selectStatement;
        $handle->insertStatement = $insertStatement;

        $reflection = new \ReflectionProperty(DatabaseCache::class, 'handle');
        $reflection->setValue($cache, $handle);

        return $cache;
    }

    private function insertDatabaseCacheRow(
        string $sqliteFile,
        string $type,
        array $payload,
        string $created
    ): void {
        $pdo = new \PDO('sqlite:' . $sqliteFile, 'user', 'pass');
        $statement = $pdo->prepare(
            'INSERT INTO cache (type, data, created, modified) VALUES (:type, :data, :created, :modified)'
        );
        $statement->execute([
            ':type' => $type,
            ':data' => serialize($payload),
            ':created' => $created,
            ':modified' => $created,
        ]);
    }
}
