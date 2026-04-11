<?php

namespace IDS\Tests\Filter\Provider;

use PHPUnit\Framework\TestCase;
use IDS\Filter\Provider\CachedFilterProvider;
use IDS\Filter\Provider\FilterProviderInterface;
use IDS\Caching\CacheInterface;
use IDS\Filter;

class CachedFilterProviderTest extends TestCase
{
    public function testGetFiltersReturnsCachedDataOnHit(): void
    {
        // Assert hit
        $innerMock = $this->createMock(FilterProviderInterface::class);
        $innerMock->expects($this->never())->method('getFilters');

        $cacheMock = $this->createMock(CacheInterface::class);
        $cacheMock->expects($this->once())
                  ->method('getCache')
                  ->willReturn([
                      [
                          'id' => 1,
                          'rule' => 'test rule',
                          'description' => 'test desc',
                          'tags' => [['xss', 'sqli']],
                          'impact' => 5
                      ]
                  ]);
                  
        $cacheMock->expects($this->never())->method('setCache');

        $provider = new CachedFilterProvider($innerMock, $cacheMock);
        $filters = $provider->getFilters();

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(Filter::class, $filters[0]);
        $this->assertSame(1, $filters[0]->getId());
        $this->assertSame('test rule', $filters[0]->getRule());
        $this->assertSame(['xss', 'sqli'], $filters[0]->getTags());
    }

    public function testGetFiltersLoadsAndCachesDataOnMiss(): void
    {
        // Assert miss
        $filter = new Filter(2, 'rule miss', 'desc miss', ['tags'], 9);

        $innerMock = $this->createMock(FilterProviderInterface::class);
        $innerMock->expects($this->once())
                  ->method('getFilters')
                  ->willReturn([$filter]);

        $cacheMock = $this->createMock(CacheInterface::class);
        $cacheMock->expects($this->once())
                  ->method('getCache')
                  ->willReturn(false);

        $cacheMock->expects($this->once())
                  ->method('setCache')
                  ->with([
                      [
                          'id' => 2,
                          'rule' => 'rule miss',
                          'impact' => 9,
                          'tags' => [['tags']], // Original nested tags format
                          'description' => 'desc miss'
                      ]
                  ]);

        $provider = new CachedFilterProvider($innerMock, $cacheMock);
        $filters = $provider->getFilters();

        $this->assertCount(1, $filters);
        $this->assertSame(2, $filters[0]->getId());
    }
}