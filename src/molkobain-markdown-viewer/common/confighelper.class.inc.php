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
use Molkobain\iTop\Extension\HandyFramework\Common\Helper\ConfigHelper as BaseConfigHelper;

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
		$sObjClass = get_class($oObject);
		$aMarkdownAttributes = static::GetSetting('markdown_attributes');

		$aAttCodes = array();
		if(array_key_exists($sObjClass, $aMarkdownAttributes) && is_array($aMarkdownAttributes[$sObjClass]))
		{
			foreach($aMarkdownAttributes[$sObjClass] as $sAttCode)
			{
				$oAttDef = MetaModel::GetAttributeDef($sObjClass, $sAttCode);
				// Only add text and html attributes, we don't want caselog, ...
				if(in_array($oAttDef->GetEditClass(), array('Text', 'HTML')))
				{
					$aAttCodes[] = $sAttCode;
				}
			}
			$aAttCodes = $aMarkdownAttributes[$sObjClass];
		}

		return $aAttCodes;
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
}
