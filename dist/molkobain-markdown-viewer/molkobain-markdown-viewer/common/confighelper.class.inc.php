<?php
/**
 * Copyright (c) 2015 - 2019 Molkobain.
 *
 * This file is part of licensed extension.
 *
 * Use of this extension is bound by the license you purchased. A license grants you a non-exclusive and non-transferable right to use and incorporate the item in your personal or commercial projects. There are several licenses available (see https://www.molkobain.com/usage-licenses/ for more informations)
 */

namespace Molkobain\iTop\Extension\MarkdownViewer\Common\Helper;

use DBObject;
use MetaModel;
use Molkobain\iTop\Extension\HandyFramework\Helper\ConfigHelper as BaseConfigHelper;

/**
 * Class ConfigHelper
 *
 * @package Molkobain\iTop\Extension\MarkdownViewer\Common\Helper
 */
class ConfigHelper extends BaseConfigHelper
{
	const MODULE_NAME = 'molkobain-markdown-viewer';
	const SETTING_CONST_FQCN = 'Molkobain\\iTop\\Extension\\MarkdownViewer\\Common\\Helper\\ConfigHelper';

	const DEFAULT_SETTING_MARKDOWN_ATTRIBUTES = array();
	const DEFAULT_SETTING_MARKDOWN_OPTIONS = array();

	/** @var string Format of a plain text attribute, see \AttributeText::GetFormat() */
	const ENUM_FORMAT_TEXT = 'text';
	/** @var string Format of an HTML attribute (edited with CKEditor), see \AttributeText::GetFormat() */
	const ENUM_FORMAT_HTML = 'html';

	/**
	 * Returns true if the $oObject has some attributes to render as markdown, false otherwise.
	 *
	 * @param \DBObject $oObject
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	public static function IsConcernedObject(DBObject $oObject)
	{
		$aAttCodes = static::GetAttributeCodesForObject($oObject);

		return (!empty($aAttCodes));
	}

	/**
	 * Returns an array of markdown attribute codes for $oObject.
	 *
	 * @param \DBObject $oObject
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public static function GetAttributeCodesForObject(DBObject $oObject)
	{
		return array_keys(static::GetAttributeFormatsForObject($oObject));
	}

	/**
	 * Returns an array of markdown attribute codes => format (see static::ENUM_FORMAT_XXX) for $oObject.
	 *
	 * @param \DBObject $oObject
	 *
	 * @return array
	 *
	 * @throws \Exception
     * @since v1.6.0
	 */
	public static function GetAttributeFormatsForObject(DBObject $oObject)
	{
		$sObjClass = get_class($oObject);
		$aAttributeFormats = static::GetAttributeFormats();

		return array_key_exists($sObjClass, $aAttributeFormats) ? $aAttributeFormats[$sObjClass] : array();
	}

	/**
	 * Returns an array of classes => (markdown attribute codes => format (see static::ENUM_FORMAT_XXX)).
	 * Invalid classes / attributes of the configuration are ignored.
	 *
	 * @return array
	 *
	 * @throws \Exception
     * @since v1.6.0
	 */
	public static function GetAttributeFormats()
	{
		$aAttributeFormats = array();
		foreach(static::GetSetting('markdown_attributes') as $sClass => $aAttCodes)
		{
			if((MetaModel::IsValidClass($sClass) === false) || (is_array($aAttCodes) === false))
			{
				continue;
			}

			foreach($aAttCodes as $sAttCode)
			{
                if(MetaModel::IsValidAttCode($sClass, $sAttCode) === false)
				{
					continue;
				}

				// Only add text and html attributes, we don't want log attributes, ...
				switch(MetaModel::GetAttributeDef($sClass, $sAttCode)->GetEditClass())
				{
					case 'Text':
						$aAttributeFormats[$sClass][$sAttCode] = static::ENUM_FORMAT_TEXT;
						break;

					case 'HTML':
						$aAttributeFormats[$sClass][$sAttCode] = static::ENUM_FORMAT_HTML;
						break;
				}
			}
		}

		return $aAttributeFormats;
	}

	/**
	 * Returns an array of all classes / markdown attribute codes.
	 *
	 * @return array
	 */
	public static function GetAttributeCodes()
	{
		return static::GetSetting('markdown_attributes');
	}

	/**
	 * Returns an array of options for the showdown.js converter (eg. allow tables, ...)
	 *
	 * @return array
	 */
	public static function GetMarkdownOptions()
	{
		return static::GetSetting('markdown_options');
	}

	/**
	 * Returns the rules of the HTML sanitizer configured in iTop (see "html_sanitizer" config. parameter), so the HTML generated from the markdown is sanitized the same way as iTop HTML attributes.
	 * Only DOM sanitizers expose their rules, so the default one is used otherwise (as \HTMLSanitizer::Sanitize() does for an invalid configuration).
	 *
	 * @return array
	 *
	 * @since v1.6.0
	 */
	public static function GetHTMLSanitizerRules()
	{
		$sSanitizerClass = MetaModel::GetConfig()->Get('html_sanitizer');
		if((class_exists($sSanitizerClass) === false) || (is_subclass_of($sSanitizerClass, 'DOMSanitizer') === false))
		{
			$sSanitizerClass = 'HTMLDOMSanitizer';
		}

		/** @var \DOMSanitizer $oSanitizer */
		$oSanitizer = new $sSanitizerClass();

		return array(
			'tags_whitelist' => $oSanitizer->GetTagsWhiteList(),
			'tags_blacklist' => $oSanitizer->GetTagsBlackList(),
			'attrs_whitelist' => $oSanitizer->GetAttrsWhiteList(),
			'attrs_blacklist' => $oSanitizer->GetAttrsBlackList(),
			'styles_whitelist' => $oSanitizer->GetStylesWhiteList(),
		);
	}
}
