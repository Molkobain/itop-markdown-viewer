<?php
/*
 * Copyright (c) 2015 - 2023 Molkobain.
 *
 * This file is part of licensed extension.
 *
 * Use of this extension is bound by the license you purchased. A license grants you a non-exclusive and non-transferable right to use and incorporate the item in your personal or commercial projects. There are several licenses available (see https://www.molkobain.com/usage-licenses/ for more informations)
 */

namespace Molkobain\iTop\Extension\HandyFramework\Tests\PHPUnitTests\UnitaryTests\Extension\Helper;

use Molkobain\iTop\Extension\HandyFramework\Helper\ConfigHelper;
use Combodo\iTop\Test\UnitTest\ItopDataTestCase;
use Molkobain\iTop\Extension\HandyFramework\Tests\PHPUnitTests\UnitaryTests\Extension\Helper\ConfigHelper\Mock\ConfigHelperA;
use utils;

/**
 * @covers \Molkobain\iTop\Extension\HandyFramework\Helper\ConfigHelper
 */
class ConfigHelperTest extends ItopDataTestCase
{
	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		require_once __DIR__ . "/../../../../../../../src/Extension/Helper/ConfigHelper.php";
		require_once __DIR__ . "/Mock/ConfigHelperA.php";
		require_once __DIR__ . "/Mock/ConfigHelperB.php";
	}

	/**
	 * @covers       \Molkobain\iTop\Extension\HandyFramework\Helper\ConfigHelper::GetModuleCode
	 * @covers       \Molkobain\iTop\Extension\HandyFramework\Helper\ConfigHelper::GetModuleVersion
	 * @covers       \Molkobain\iTop\Extension\HandyFramework\Helper\ConfigHelper::GetAbsoluteModuleUrl
	 * @covers       \Molkobain\iTop\Extension\HandyFramework\Helper\ConfigHelper::GetAbsoluteModulePath
	 * @dataProvider providerClassOverloads
	 *
	 * @param string $sConfigHelperFQCN
	 * @param string $sExpectedModuleName
	 *
	 * @return void
	 */
	public function testConstantsOverloads(string $sConfigHelperFQCN, string $sExpectedModuleName)
	{
		// MODULE_NAME overload
		$this->assertSame($sExpectedModuleName, $sConfigHelperFQCN::GetModuleCode());
		// Module URL
		$sExpectedModuleUrl = utils::GetAbsoluteUrlModulesRoot() . '/' . $sConfigHelperFQCN::GetModuleCode() . '/';
		$this->assertSame($sExpectedModuleUrl, $sConfigHelperFQCN::GetAbsoluteModuleUrl());
		// Module path
		$sExpectedModulePath = APPROOT . '/env-' . utils::GetCurrentEnvironment() . '/' . $sConfigHelperFQCN::GetModuleCode() . '/';
		$this->assertSame($sExpectedModulePath, $sConfigHelperFQCN::GetAbsoluteModulePath());
	}

	public function providerClassOverloads(): array
	{
		return [
			'Handy Framework ConfigHelper' => [
				'Molkobain\\iTop\\Extension\\HandyFramework\\Helper\\ConfigHelper',                // ConfigHelper class
				'molkobain-handy-framework',          // Expected module name for that CH
			],
			'Fake module A ConfigHelper' => [
				'Molkobain\\iTop\\Extension\\HandyFramework\\Tests\\PHPUnitTests\\UnitaryTests\\Extension\\Helper\\ConfigHelper\\Mock\\ConfigHelperA',
				'mock-config-helper-a',
			],
			'Fake module B ConfigHelper' => [
				'Molkobain\\iTop\\Extension\\HandyFramework\\Tests\\PHPUnitTests\\UnitaryTests\\Extension\\Helper\\ConfigHelper\\Mock\\ConfigHelperB',
				'mock-config-helper-b',
			],
		];
	}

	/**
	 * @covers \Molkobain\iTop\Extension\HandyFramework\Helper\ConfigHelper::GetSetting
	 * @dataProvider providerGetSetting
	 *
	 * @param string $sSettingName
	 * @param mixed $mExpectedValue
	 *
	 * @return void
	 */
	public function testGetSetting(string $sSettingName, $mExpectedValue)
	{
		$mTestedValue = ConfigHelperA::GetSetting($sSettingName);
		$this->assertSame($mExpectedValue, $mTestedValue);
	}

	public function providerGetSetting(): array
	{
		return [
			'Non existing setting, no default value, no custom value' => [
				'bim',
				null,
			],
			'Existing setting, default value, no custom value' => [
				'foo',
				'bar',
			],
			'Existing setting, default value, custom value' => [
				'enabled',
				true,
			],
		];
	}

	/**
	 * @covers \Molkobain\iTop\Extension\HandyFramework\Helper\ConfigHelper::IsEnabled
	 *
	 * @return void
	 */
	public function testIsEnabled()
	{
		$this->assertTrue(ConfigHelper::IsEnabled());
	}
}