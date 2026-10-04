<?php
/*
 * Copyright (c) 2015 - 2026 Molkobain.
 *
 * This file is part of licensed extension.
 *
 * Use of this extension is bound by the license you purchased. A license grants you a non-exclusive and non-transferable right to use and incorporate the item in your personal or commercial projects. There are several licenses available (see https://www.molkobain.com/usage-licenses/ for more informations)
 */

namespace Molkobain\iTop\Extension\MarkdownViewer\Tests\PHPUnitTests\UnitaryTests\Common\Mock;

use HTMLDOMSanitizer;

/**
 * Class CustomHTMLSanitizer
 *
 * Mimics what an administrator would do to customize the HTML sanitizer (see "html_sanitizer" config. parameter).
 *
 * @package Molkobain\iTop\Extension\MarkdownViewer\Tests\PHPUnitTests\UnitaryTests\Common\Mock
 */
class CustomHTMLSanitizer extends HTMLDOMSanitizer
{
	/**
	 * @inheritdoc
	 */
	public function GetTagsWhiteList()
	{
		return array(
			'p' => array('class'),
			'a' => array('href', 'title'),
		);
	}

	/**
	 * @inheritdoc
	 */
	public function GetTagsBlackList()
	{
		return array('script');
	}

	/**
	 * @inheritdoc
	 */
	public function GetAttrsWhiteList()
	{
		return array(
			'href' => '/^https:/i',
		);
	}

	/**
	 * @inheritdoc
	 */
	public function GetAttrsBlackList()
	{
		return array('onclick');
	}

	/**
	 * @inheritdoc
	 */
	public function GetStylesWhiteList()
	{
		return array('color');
	}
}
