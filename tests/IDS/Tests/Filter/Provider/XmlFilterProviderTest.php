<?php

namespace IDS\Tests\Filter\Provider;

use IDS\Filter;
use IDS\Filter\Provider\XmlFilterProvider;
use IDS\Tests\Support\RuntimeFunctionOverrides;
use PHPUnit\Framework\TestCase;

class LegacyLibxmlXmlFilterProvider extends XmlFilterProvider
{
    protected function getLibxmlVersion(): int
    {
        return 20620;
    }
}

class XmlFilterProviderTest extends TestCase
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
        $provider = new XmlFilterProvider(IDS_FILTER_SET_XML);

        $filters = $provider->getFilters();

        $this->assertNotEmpty($filters);
        $this->assertContainsOnlyInstancesOf(Filter::class, $filters);
    }

    public function testGetFiltersThrowsForMissingSource(): void
    {
        $provider = new XmlFilterProvider(__DIR__ . '/missing-filter-set.xml');

        $this->expectException(\InvalidArgumentException::class);
        $provider->getFilters();
    }

    public function testGetFiltersThrowsForInvalidXmlPayload(): void
    {
        $file = $this->createTempFile('<filters><filter>');
        $provider = new XmlFilterProvider($file);

        $this->expectException(\RuntimeException::class);
        $provider->getFilters();
    }

    public function testGetFiltersThrowsWhenSimpleXmlExtensionIsUnavailable(): void
    {
        RuntimeFunctionOverrides::$providerExtensionOverrides['simplexml'] = false;
        $provider = new XmlFilterProvider(IDS_FILTER_SET_XML);

        $this->expectException(\RuntimeException::class);
        $provider->getFilters();
    }

    public function testGetFiltersLoadsDefaultFilterSetWithLegacyLibxmlBranch(): void
    {
        $provider = new LegacyLibxmlXmlFilterProvider(IDS_FILTER_SET_XML);

        $filters = $provider->getFilters();

        $this->assertNotEmpty($filters);
        $this->assertContainsOnlyInstancesOf(Filter::class, $filters);
    }

    private function createTempFile(string $contents): string
    {
        $file = tempnam(sys_get_temp_dir(), 'phpids-xml-');
        if ($file === false) {
            $this->fail('Unable to create temporary XML fixture.');
        }

        file_put_contents($file, $contents);
        $this->tempFiles[] = $file;

        return $file;
    }
}
