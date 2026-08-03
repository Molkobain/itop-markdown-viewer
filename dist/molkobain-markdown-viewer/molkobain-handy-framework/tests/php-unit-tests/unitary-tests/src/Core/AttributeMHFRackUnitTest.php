<?php
/*
 * Copyright (c) 2015 - 2023 Molkobain.
 *
 * This file is part of licensed extension.
 *
 * Use of this extension is bound by the license you purchased. A license grants you a non-exclusive and non-transferable right to use and incorporate the item in your personal or commercial projects. There are several licenses available (see https://www.molkobain.com/usage-licenses/ for more informations)
 */

namespace Molkobain\iTop\Extension\HandyFramework\Tests\PHPUnitTests\UnitaryTests\Core;

use AttributeMHFRackUnit;
use Combodo\iTop\Test\UnitTest\ItopDataTestCase;

/**
 * @covers \AttributeMHFRackUnit
 */
class AttributeMHFRackUnitTest extends ItopDataTestCase
{
	/**
	 * @covers \AttributeMHFRackUnit::GetValueLabel
	 * @dataProvider providerExamplesOfAttributeUsages
	 *
	 * @param string $sAttCode
	 * @param mixed $mDefaultValue
	 * @param bool $bIsNullAllowed
	 * @param mixed $mCurrentValue
	 * @param string $sExpectedValueLabel
	 *
	 * @return void
	 */
	public function testGetValueLabel(string $sAttCode, $mDefaultValue, bool $bIsNullAllowed, $mCurrentValue, string $sExpectedValueLabel)
	{
		$oAttDef = new AttributeMHFRackUnit($sAttCode, [
			"allowed_values" => null,
			"sql" => $sAttCode,
			"default_value" => $mDefaultValue,
			"is_null_allowed" => $bIsNullAllowed,
			"depends_on" => [],
			"always_load_in_tables" => false,
		]);

		$sTestedValueLabel = $oAttDef->GetValueLabel($mCurrentValue);
		$this->assertSame($sExpectedValueLabel, $sTestedValueLabel);
	}

	/**
	 * @covers \AttributeMHFRackUnit::GetAsHTML
	 * @dataProvider providerExamplesOfAttributeUsages
	 *
	 * @param string $sAttCode
	 * @param mixed $mDefaultValue
	 * @param bool $bIsNullAllowed
	 * @param mixed $mCurrentValue
	 * @param string $sExpectedValueLabel
	 *
	 * @return void
	 */
	public function testGetAsHTML(string $sAttCode, $mDefaultValue, bool $bIsNullAllowed, $mCurrentValue, string $sExpectedValueLabel)
	{
		$oAttDef = new AttributeMHFRackUnit($sAttCode, [
			"allowed_values" => null,
			"sql" => $sAttCode,
			"default_value" => $mDefaultValue,
			"is_null_allowed" => $bIsNullAllowed,
			"depends_on" => [],
			"always_load_in_tables" => false,
		]);

		$sTestedValueLabel = $oAttDef->GetAsHTML($mCurrentValue);
		$this->assertSame($sExpectedValueLabel, $sTestedValueLabel);
	}

	public function providerExamplesOfAttributeUsages(): array
	{
		// Note that we can't use {@see \AttributeMHFRackUnit::EMPTY_VALUE_LABEL} or {@see \AttributeMHFRackUnit::RACK_UNIT_SUFFIX} as the `setUpBeforeClass` is called after the provider... ???
		return [
			'nb_u, nullable, empty default value, null current value' => [
				'nb_u', // Att. code and SQL col. name
				'',     // Default value
				true,   // Is null allowed
				null,   // Att. current value
				'-',    // Att. expected value label.
			],
			'nb_u, nullable, empty default value, 0 as current value' => [
				'nb_u', // Att. code and SQL col. name
				'',     // Default value
				true,   // Is null allowed
				0,      // Att. current value
				'0U',    // Att. expected value label. NOte that we can't use `\AttributeMHFRackUnit::EMPTY_VALUE_LABEL` as the `setUpBeforeClass` is called after the provider... ???
			],
			'nb_u, nullable, empty default value, significant number (2) as current value' => [
				'nb_u', // Att. code and SQL col. name
				'',     // Default value
				true,   // Is null allowed
				2,      // Att. current value
				'2U',   // Att. expected value label. NOte that we can't use `\AttributeMHFRackUnit::EMPTY_VALUE_LABEL` as the `setUpBeforeClass` is called after the provider... ???
			],
			'nb_u, nullable, 0 as default value, null current value' => [
				'nb_u', // Att. code and SQL col. name
				0,      // Default value
				true,   // Is null allowed
				null,   // Att. current value
				'-',    // Att. expected value label.
			],
			'nb_u, nullable, 0 as default value, 0 as current value' => [
				'nb_u', // Att. code and SQL col. name
				0,      // Default value
				true,   // Is null allowed
				0,      // Att. current value
				'0U',    // Att. expected value label.
			],
			'nb_u, nullable, 0 as default value, significant number (2) as current value' => [
				'nb_u', // Att. code and SQL col. name
				0,      // Default value
				true,   // Is null allowed
				2,      // Att. current value
				'2U',    // Att. expected value label.
			],
			'nb_u, nullable, 2 as default value, null current value' => [
				'nb_u', // Att. code and SQL col. name
				2,      // Default value
				true,   // Is null allowed
				null,   // Att. current value
				'-',    // Att. expected value label.
			],
			'nb_u, nullable, 2 as default value, 0 as current value' => [
				'nb_u', // Att. code and SQL col. name
				2,      // Default value
				true,   // Is null allowed
				0,      // Att. current value
				'0U',    // Att. expected value label.
			],
			'nb_u, nullable, 2 as default value, significant number (2) as current value' => [
				'nb_u', // Att. code and SQL col. name
				2,      // Default value
				true,   // Is null allowed
				2,      // Att. current value
				'2U',   // Att. expected value label.
			],
		];
	}
}