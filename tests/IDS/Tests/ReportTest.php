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

use IDS\Report;
use IDS\Event;
use IDS\Filter;

class ReportTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Report
     */
    protected $report;

    protected function setUp(): void {
        $this->report = new Report([new Event("key_a", 'val_b',
            [new Filter(1, '^test_a1$', 'desc_a1', ['tag_a1', 'tag_a2'], 1), new Filter(1, '^test_a2$', 'desc_a2', ['tag_a2', 'tag_a3'], 2)]
        ), new Event('key_b', 'val_b',
            [new Filter(1, '^test_b1$', 'desc_b1', ['tag_b1', 'tag_b2'], 3), new Filter(1, '^test_b2$', 'desc_b2', ['tag_b2', 'tag_b3'], 4)]
        )]);
    }

    public function testEmpty()
    {
        $this->assertFalse($this->report->isEmpty());
        $report = new Report;
        $this->assertTrue($report->isEmpty());
    }

    public function testCountable()
    {
        $this->assertEquals(2, count($this->report));
    }

    public function testGetterByName()
    {
        $this->assertEquals("key_a", $this->report->getEvent("key_a")->getName());
        $this->assertEquals("key_b", $this->report->getEvent("key_b")->getName());
    }

    public function testGetTags()
    {
        $this->assertEquals(['tag_a1', 'tag_a2', 'tag_a3', 'tag_b1', 'tag_b2', 'tag_b3'], $this->report->getTags());
    }

    public function testImpactSum()
    {
        $this->assertEquals(10, $this->report->getImpact());
    }

    public function testHasEvent()
    {
        $this->assertTrue($this->report->hasEvent('key_a'));
    }

    public function testAddingAnotherEventAfterCalculation()
    {
        $this->testImpactSum();
        $this->testGetTags();
        $this->report->addEvent(new Event('key_c', 'val_c', [new Filter(1, 'test_c1', 'desc_c1', ['tag_c1'], 10)]));
        $this->assertEquals(20, $this->report->getImpact());
        $this->assertEquals(['tag_a1', 'tag_a2', 'tag_a3', 'tag_b1', 'tag_b2', 'tag_b3', 'tag_c1'], $this->report->getTags());
    }

    public function testIteratorAggregate()
    {
        $this->assertInstanceOf('IteratorAggregate', $this->report);
        $this->assertInstanceOf('Iterator', $this->report->getIterator());
    }

    public function testToString()
    {
        $this->assertEquals(preg_match('/Total impact: 10/', $this->report->__toString()),1);
    }

    public function testToStringEmpty()
    {
        $this->report = new Report();
        $this->assertEquals('', $this->report->__toString());
    }

    public function testGetEvent()
    {
        $this->report->addEvent(new Event('key_c', 'val_c', [new Filter(1, 'test_c1', 'desc_c1', ['tag_c1'], 10)]));
        $this->assertTrue($this->report->getEvent('key_c') instanceof Event);
    }

    public function testGetEventWrong()
    {
        $this->assertNull($this->report->getEvent('not_available'));
    }

    public function testSetAndGetCentrifuge()
    {
        $data = ['threshold' => 3.5, 'ratio' => 1.2, 'converted' => 'alert(1)'];
        $this->report->setCentrifuge($data);

        $this->assertSame($data, $this->report->getCentrifuge());
        $this->assertStringContainsString('Centrifuge detection data', $this->report->__toString());
    }

    public function testSetCentrifugeRejectsEmptyArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->report->setCentrifuge([]);
    }

}
