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
 * @package    PHPIDS tests
 */
namespace IDS\Tests;

use IDS\Init;

class InitTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \IDS\Init
     */
    private $init = null;

    protected function setUp(): void {
        $config = parse_ini_file(IDS_CONFIG, true);
        $this->init = new Init($config === false ? [] : $config);
    }

    public function testInit()
    {
        $this->assertTrue($this->init instanceof Init);
    }

    public function testInitConfig()
    {
        $keys = array('General', 'Caching');
        $this->assertEquals($keys, array_keys($this->init->config));
    }

    public function testInitClone()
    {
        $config2 = clone $this->init;
        $this->assertEquals($config2, $this->init);
    }

    public function testInitSetConfigOverwrite()
    {
        $this->init->setConfig(array('General' => array('filter_type' => 'json')), true);
        $this->assertEquals($this->init->config['General']['filter_type'], 'json');

        $this->init->setConfig(
            array('General' => array('exceptions' => array('foo'))),
            true
        );
        $this->assertSame(
            array('foo', 'GET.__utmc'),
            $this->init->config['General']['exceptions']
        );
    }

    public function testInitSetConfigNoOverwrite()
    {
        $this->init->setConfig(array('General' => array('filter_type' => 'xml')), true);
        $this->init->setConfig(array('General' => array('filter_type' => 'json')));
        $this->assertEquals($this->init->config['General']['filter_type'], 'xml');
    }

    public function testInitGetConfig()
    {
        $data = $this->init->getConfig();
        $this->assertEquals($this->init->config, $data);
    }

    public function testGetBasePathReturnsConfiguredPathWhenEnabled()
    {
        $this->init->config['General']['base_path'] = '/tmp/phpids';
        $this->init->config['General']['use_base_path'] = true;

        $this->assertSame('/tmp/phpids', $this->init->getBasePath());
    }

    public function testGetBasePathReturnsNullWhenDisabled()
    {
        $this->init->config['General']['base_path'] = '/tmp/phpids';
        $this->init->config['General']['use_base_path'] = false;

        $this->assertNull($this->init->getBasePath());
    }

    public function testInstanciatingInitObjectWithoutPassingConfigFile()
    {
        $init = Init::init();
        $this->assertInstanceOf('IDS\\Init', $init);
    }

    public function testInitReturnsCachedInstanceForSameConfigPath()
    {
        $first = Init::init(IDS_CONFIG);
        $second = Init::init(IDS_CONFIG);

        $this->assertSame($first, $second);
    }

    public function testInitThrowsRuntimeExceptionForUnparseableConfig()
    {
        $file = tempnam(sys_get_temp_dir(), 'phpids-ini-');
        if ($file === false) {
            $this->fail('Unable to create temporary ini fixture.');
        }

        file_put_contents($file, "[General\nbroken = true");

        try {
            $this->expectException(\RuntimeException::class);
            Init::init($file);
        } finally {
            @unlink($file);
        }
    }
}
