<?php

namespace IDS\Tests\Filter\Provider;

use IDS\Filter;
use IDS\Filter\Provider\JsonFilterProvider;
use IDS\Tests\Support\RuntimeFunctionOverrides;
use PHPUnit\Framework\TestCase;

class JsonFilterProviderTest extends TestCase
{
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        RuntimeFunctionOverrides::reset();
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
    }

    public function testGetFiltersLoadsDefaultFilterSet(): void
    {
        $provider = new JsonFilterProvider(IDS_FILTER_SET_JSON);

        $filters = $provider->getFilters();

        $this->assertNotEmpty($filters);
        $this->assertContainsOnlyInstancesOf(Filter::class, $filters);
    }

    public function testGetFiltersThrowsForMissingSource(): void
    {
        $provider = new JsonFilterProvider(__DIR__ . '/missing-filter-set.json');

        $this->expectException(\InvalidArgumentException::class);
        $provider->getFilters();
    }

    public function testGetFiltersThrowsForInvalidJsonPayload(): void
    {
        $file = $this->createTempFile('{"filters":{"filter":');
        $provider = new JsonFilterProvider($file);

        $this->expectException(\RuntimeException::class);
        $provider->getFilters();
    }

    public function testGetFiltersThrowsWhenJsonExtensionIsUnavailable(): void
    {
        RuntimeFunctionOverrides::$providerExtensionOverrides['json'] = false;
        $provider = new JsonFilterProvider(IDS_FILTER_SET_JSON);

        $this->expectException(\RuntimeException::class);
        $provider->getFilters();
    }

    public function testGetFiltersThrowsWhenJsonSourceCannotBeRead(): void
    {
        $file = $this->createTempFile('{"filters":{"filter":[]}}');
        RuntimeFunctionOverrides::$providerFileContentsOverrides[$file] = false;
        $provider = new JsonFilterProvider($file);

        $this->expectException(\RuntimeException::class);
        $provider->getFilters();
    }

    private function createTempFile(string $contents): string
    {
        $file = tempnam(sys_get_temp_dir(), 'phpids-json-');
        if ($file === false) {
            $this->fail('Unable to create temporary JSON fixture.');
        }

        file_put_contents($file, $contents);
        $this->tempFiles[] = $file;

        return $file;
    }
}
