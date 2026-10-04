<?php
/*
 * Copyright (c) 2015 - 2026 Molkobain.
 *
 * This file is part of licensed extension.
 *
 * Use of this extension is bound by the license you purchased. A license grants you a non-exclusive and non-transferable right to use and incorporate the item in your personal or commercial projects. There are several licenses available (see https://www.molkobain.com/usage-licenses/ for more informations)
 */

namespace Molkobain\iTop\Extension\MarkdownViewer\Tests\PHPUnitTests\UnitaryTests\Common;

use Combodo\iTop\Test\UnitTest\ItopDataTestCase;
use HTMLDOMSanitizer;
use MetaModel;
use Molkobain\iTop\Extension\MarkdownViewer\Common\Helper\ConfigHelper;
use Molkobain\iTop\Extension\MarkdownViewer\Tests\PHPUnitTests\UnitaryTests\Common\Mock\CustomHTMLSanitizer;

/**
 * @covers \Molkobain\iTop\Extension\MarkdownViewer\Common\Helper\ConfigHelper
 */
class ConfigHelperTest extends ItopDataTestCase
{
	/** @var array $aAlteredSettings Module settings changed during the test, to be restored on tear down */
	private $aAlteredSettings = array();
	/** @var array $aAlteredConfigParams iTop config. parameters changed during the test, to be restored on tear down */
	private $aAlteredConfigParams = array();

	/**
	 * @inheritdoc
	 */
	protected function tearDown(): void
	{
		foreach ($this->aAlteredSettings as $sSettingName => $mOriginalValue) {
			MetaModel::GetConfig()->SetModuleSetting(ConfigHelper::GetModuleCode(), $sSettingName, $mOriginalValue);
		}
		$this->aAlteredSettings = array();

		foreach ($this->aAlteredConfigParams as $sParamName => $mOriginalValue) {
			MetaModel::GetConfig()->Set($sParamName, $mOriginalValue);
		}
		$this->aAlteredConfigParams = array();

		parent::tearDown();
	}

	/**
	 * @covers       \Molkobain\iTop\Extension\MarkdownViewer\Common\Helper\ConfigHelper::GetAttributeFormats
	 * @dataProvider providerGetAttributeFormats
	 *
	 * @param mixed $mMarkdownAttributes
	 * @param array $aExpectedFormats
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function testGetAttributeFormats($mMarkdownAttributes, array $aExpectedFormats): void
	{
		$this->AlterModuleSetting('markdown_attributes', $mMarkdownAttributes);

		$this->assertSame($aExpectedFormats, ConfigHelper::GetAttributeFormats());
	}

	public function providerGetAttributeFormats(): array
	{
		return [
			"Text attribute" => [
				['FAQ' => ['summary']],               // "markdown_attributes" module setting
				['FAQ' => ['summary' => 'text']],     // Expected formats
			],
			"HTML attribute" => [
				['FAQ' => ['description']],
				['FAQ' => ['description' => 'html']],
			],
			"Text attribute in HTML format" => [
				['UserRequest' => ['solution']],
				['UserRequest' => ['solution' => 'html']],
			],
			"Several classes and attributes" => [
				['FAQ' => ['summary', 'description'], 'UserRequest' => ['solution']],
				['FAQ' => ['summary' => 'text', 'description' => 'html'], 'UserRequest' => ['solution' => 'html']],
			],
			"Attribute which is not a text, ignored" => [
				['FAQ' => ['title', 'summary']],
				['FAQ' => ['summary' => 'text']],
			],
			"Caselog attribute, ignored" => [
				['UserRequest' => ['public_log']],
				[],
			],
			"Invalid attribute code (wrong case), ignored" => [
				['UserRequest' => ['Description']],
				[],
			],
			"Invalid class, ignored" => [
				['Molkobain\\Foo\\Bar' => ['description']],
				[],
			],
			"Attribute codes not as an array, ignored" => [
				['FAQ' => 'summary'],
				[],
			],
			"Empty configuration" => [
				[],
				[],
			],
		];
	}

	/**
	 * @covers       \Molkobain\iTop\Extension\MarkdownViewer\Common\Helper\ConfigHelper::GetAttributeFormatsForObject
	 * @covers       \Molkobain\iTop\Extension\MarkdownViewer\Common\Helper\ConfigHelper::GetAttributeCodesForObject
	 * @covers       \Molkobain\iTop\Extension\MarkdownViewer\Common\Helper\ConfigHelper::IsConcernedObject
	 * @dataProvider providerAttributesForObject
	 *
	 * @param string $sObjClass
	 * @param array $aExpectedFormats
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function testAttributesForObject(string $sObjClass, array $aExpectedFormats): void
	{
		$this->AlterModuleSetting('markdown_attributes', [
			'FAQ' => ['summary', 'description'],
			'Ticket' => ['description'],
		]);
		$oObject = MetaModel::NewObject($sObjClass);

		$this->assertSame($aExpectedFormats, ConfigHelper::GetAttributeFormatsForObject($oObject));
		$this->assertSame(array_keys($aExpectedFormats), ConfigHelper::GetAttributeCodesForObject($oObject));
		$this->assertSame(count($aExpectedFormats) > 0, ConfigHelper::IsConcernedObject($oObject));
	}

	public function providerAttributesForObject(): array
	{
		return [
			"Configured class" => [
				'FAQ',                                                // Object class
				['summary' => 'text', 'description' => 'html'],      // Expected formats
			],
			"Class not configured" => [
				'Person',
				[],
			],
			"Subclass of a configured class, only the exact class is concerned" => [
				'UserRequest',
				[],
			],
		];
	}

	/**
	 * @covers \Molkobain\iTop\Extension\MarkdownViewer\Common\Helper\ConfigHelper::GetHTMLSanitizerRules
	 *
	 * @return void
	 */
	public function testGetHTMLSanitizerRules(): void
	{
		$this->AlterConfigParam('html_sanitizer', 'HTMLDOMSanitizer');

		$this->assertSame($this->GetRulesOfSanitizer(new HTMLDOMSanitizer()), ConfigHelper::GetHTMLSanitizerRules());
	}

	/**
	 * @covers \Molkobain\iTop\Extension\MarkdownViewer\Common\Helper\ConfigHelper::GetHTMLSanitizerRules
	 *
	 * @return void
	 */
	public function testGetHTMLSanitizerRulesFollowsTheConfiguredSanitizer(): void
	{
		require_once __DIR__.'/Mock/CustomHTMLSanitizer.php';
		$this->AlterConfigParam('html_sanitizer', CustomHTMLSanitizer::class);

		$this->assertSame(
			[
				'tags_whitelist' => ['p' => ['class'], 'a' => ['href', 'title']],
				'tags_blacklist' => ['script'],
				'attrs_whitelist' => ['href' => '/^https:/i'],
				'attrs_blacklist' => ['onclick'],
				'styles_whitelist' => ['color'],
			],
			ConfigHelper::GetHTMLSanitizerRules()
		);
	}

	/**
	 * @covers       \Molkobain\iTop\Extension\MarkdownViewer\Common\Helper\ConfigHelper::GetHTMLSanitizerRules
	 * @dataProvider providerGetHTMLSanitizerRulesFallsBackToTheDefaultSanitizer
	 *
	 * @param string $sSanitizerClass
	 *
	 * @return void
	 */
	public function testGetHTMLSanitizerRulesFallsBackToTheDefaultSanitizer(string $sSanitizerClass): void
	{
		$this->AlterConfigParam('html_sanitizer', $sSanitizerClass);

		$this->assertSame($this->GetRulesOfSanitizer(new HTMLDOMSanitizer()), ConfigHelper::GetHTMLSanitizerRules());
	}

	public function providerGetHTMLSanitizerRulesFallsBackToTheDefaultSanitizer(): array
	{
		return [
			"Not a DOM sanitizer, so no rules to read" => [
				'HTMLNullSanitizer',
			],
			"Non existing class" => [
				'Molkobain\\Foo\\Bar',
			],
		];
	}

	/**
	 * Contract with iTop: Attributes patterns of the default sanitizer are converted to JS regular expressions by the
	 * markdown viewer (see MolkobainMarkdownViewer.ConvertPattern()), which only supports some delimiters and modifiers.
	 * Otherwise attributes using them (eg. "href") would be removed from the rendered markdown.
	 *
	 * @covers \Molkobain\iTop\Extension\MarkdownViewer\Common\Helper\ConfigHelper::GetHTMLSanitizerRules
	 *
	 * @return void
	 */
	public function testGetHTMLSanitizerRulesPatternsCanBeConvertedToJS(): void
	{
		$this->AlterConfigParam('html_sanitizer', 'HTMLDOMSanitizer');
		$aBracketDelimiters = ['(' => ')', '[' => ']', '{' => '}', '<' => '>'];

		$aPatterns = ConfigHelper::GetHTMLSanitizerRules()['attrs_whitelist'];
		$this->assertNotEmpty($aPatterns, 'Default sanitizer should have attributes patterns');

		foreach ($aPatterns as $sAttCode => $sPattern) {
			$this->assertNotFalse(@preg_match($sPattern, ''), "Pattern of '$sAttCode' attribute should be a valid PCRE pattern");

			$sStartDelimiter = substr($sPattern, 0, 1);
			$sEndDelimiter = $aBracketDelimiters[$sStartDelimiter] ?? $sStartDelimiter;
			$iEndDelimiterPos = strrpos($sPattern, $sEndDelimiter);
			$this->assertGreaterThan(0, $iEndDelimiterPos, "Pattern of '$sAttCode' attribute should have an end delimiter: $sPattern");

			$sModifiers = substr($sPattern, $iEndDelimiterPos + 1);
			$this->assertSame(1, preg_match('/^[imsu]*$/', $sModifiers), "Pattern of '$sAttCode' attribute should only use modifiers having a JS equivalent, found '$sModifiers'");
		}
	}


	//----------
	// Helpers
	//----------

	/**
	 * Changes the $sName module setting for the duration of the test only.
	 *
	 * Note: Only the in-memory configuration is changed, the conf. file is left untouched.
	 *
	 * @param string $sName
	 * @param mixed $mValue
	 *
	 * @return void
	 */
	private function AlterModuleSetting(string $sName, $mValue): void
	{
		if (false === array_key_exists($sName, $this->aAlteredSettings)) {
			$this->aAlteredSettings[$sName] = MetaModel::GetModuleSetting(ConfigHelper::GetModuleCode(), $sName, null);
		}

		MetaModel::GetConfig()->SetModuleSetting(ConfigHelper::GetModuleCode(), $sName, $mValue);
	}

	/**
	 * Changes the $sName iTop config. parameter for the duration of the test only.
	 *
	 * Note: Only the in-memory configuration is changed, the conf. file is left untouched.
	 *
	 * @param string $sName
	 * @param mixed $mValue
	 *
	 * @return void
	 */
	private function AlterConfigParam(string $sName, $mValue): void
	{
		if (false === array_key_exists($sName, $this->aAlteredConfigParams)) {
			$this->aAlteredConfigParams[$sName] = MetaModel::GetConfig()->Get($sName);
		}

		MetaModel::GetConfig()->Set($sName, $mValue);
	}

	/**
	 * @param \DOMSanitizer $oSanitizer
	 *
	 * @return array Rules of $oSanitizer, in the same format as ConfigHelper::GetHTMLSanitizerRules()
	 */
	private function GetRulesOfSanitizer(\DOMSanitizer $oSanitizer): array
	{
		return [
			'tags_whitelist' => $oSanitizer->GetTagsWhiteList(),
			'tags_blacklist' => $oSanitizer->GetTagsBlackList(),
			'attrs_whitelist' => $oSanitizer->GetAttrsWhiteList(),
			'attrs_blacklist' => $oSanitizer->GetAttrsBlackList(),
			'styles_whitelist' => $oSanitizer->GetStylesWhiteList(),
		];
	}
}
