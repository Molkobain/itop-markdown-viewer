<?php
/**
 * Copyright (c) 2015 - 2019 Molkobain.
 *
 * This file is part of licensed extension.
 *
 * Use of this extension is bound by the license you purchased. A license grants you a non-exclusive and non-transferable right to use and incorporate the item in your personal or commercial projects. There are several licenses available (see https://www.molkobain.com/usage-licenses/ for more informations)
 */

namespace Molkobain\iTop\Extension\MarkdownViewer\Console\Extension;

use Dict;
use AbstractApplicationUIExtension;
use MetaModel;
use Molkobain\iTop\Extension\MarkdownViewer\Common\Helper\ConfigHelper;
use utils;
use WebPage;

/**
 * Class ApplicationUIExtension
 *
 * @package Molkobain\iTop\Extension\MarkdownViewer\Console\Extension
 */
class ApplicationUIExtension extends AbstractApplicationUIExtension
{
	/**
	 * @inheritdoc
	 *
	 * @throws \Exception
	 */
	public function OnDisplayProperties($oObject, WebPage $oPage, $bEditMode = false)
	{
		// Check if enabled
		if(ConfigHelper::IsEnabled() === false)
		{
			return;
		}

		// Check if object has markdown attributes
		if(ConfigHelper::IsConcernedObject($oObject) === false)
		{
			return;
		}

		$sModuleVersion = utils::GetCompiledModuleVersion(ConfigHelper::GetModuleCode());
		$sURLBase = ConfigHelper::GetModuleCode() . '/';

		// Add css files
		$oPage->add_saas('env-' . utils::GetCurrentEnvironment() . '/' . ConfigHelper::GetModuleCode() . '/common/css/markdown-viewer.scss');

		// Add js files
		$oPage->LinkScriptFromModule($sURLBase . 'common/lib/showdown/showdown.min.js?v=' . $sModuleVersion);
		$oPage->LinkScriptFromModule($sURLBase . 'common/lib/dompurify/purify.min.js?v=' . $sModuleVersion);
		$oPage->LinkScriptFromModule($sURLBase . 'common/js/markdown-viewer.js?v=' . $sModuleVersion);

		// Prepare dict entries
		$sPreviewIconTooltip = Dict::S('Molkobain:MarkdownViewer:Preview:Button:Show');
		$sPreviewTitle = Dict::S('Molkobain:MarkdownViewer:Preview:Title');
		$sPreviewCloseLabel = Dict::S('Molkobain:MarkdownViewer:Preview:Button:Close');

		// Prepare JS vars
		$sEditModeAsString = ($bEditMode) ? 'true' : 'false';
		$sAttFormatsAsJSON = json_encode(ConfigHelper::GetAttributeFormatsForObject($oObject), JSON_FORCE_OBJECT);
		$sConverterOptionsAsJSON = json_encode(ConfigHelper::GetMarkdownOptions());
		$sSanitizerRulesAsJSON = json_encode(ConfigHelper::GetHTMLSanitizerRules());
		$iImageMaxWidth = (int) MetaModel::GetConfig()->Get('inline_image_max_display_width');

		// Prepare JS selectors
        $sJSSelectorForFieldElement = '[data-role="ibo-field"]';
        $sJSSelectorForFieldLabelElement = '.ibo-field--label';
        $sJSSelectorForFieldValueElement = '.ibo-field--value';

		// Instantiate widget on object's caselogs
		$oPage->add_ready_script(
			<<<JS
// Molkobain markdown viewer
$(document).ready(function(){
    // Initializing widget
    // Note: .field_container for 2.7, data-role for 3.0+ 
    $('{$sJSSelectorForFieldElement}').each(function(){
        var me = $(this);
        var iImageMaxWidth = {$iImageMaxWidth};
        var bEditMode = {$sEditModeAsString};
        var oAttFormats = {$sAttFormatsAsJSON};
        var oConverterOptions = {$sConverterOptionsAsJSON};
        var oSanitizerRules = {$sSanitizerRulesAsJSON};
        var sFieldAttCode = me.attr('data-attribute-code');
        
        // iTop 2.6 and earlier copatibility
        if(sFieldAttCode === undefined)
        {
            sFieldAttCode = me.attr('data-attcode');
        }
        
        // Stop if not a markdown field
        if(oAttFormats.hasOwnProperty(sFieldAttCode) === false)
        {
            return;
        }
        var sFieldFormat = oAttFormats[sFieldAttCode];
        
        // Add widget class
        me.addClass('molkobain-markdown-viewer');
        
        var bEditableAttribute = ((bEditMode === true) && (me.find('{$sJSSelectorForFieldValueElement} > *:first').hasClass('field_value_container') === true));
        // If not editing, view markdown as html...
        if(bEditableAttribute === false)
        {
			// Convert Markdown to HTML
            var oValueElem = me.find('{$sJSSelectorForFieldValueElement} > *').first();
            var sMarkdownValue = MolkobainMarkdownViewer.GetMarkdownFromDisplayedValue(oValueElem, sFieldFormat);
            oValueElem.empty().append(MolkobainMarkdownViewer.MakeHtml(sMarkdownValue, oConverterOptions, oSanitizerRules));
			
			// iTop 3.0+, enforce plain text field to be styled with standard HTML rules
			oValueElem.addClass('ibo-is-html-content');
            
            // Enable image zoom-in
            MolkobainMarkdownViewer.EnableImageZoom(oValueElem, iImageMaxWidth);
        }
        // ... otherwise show preview mode
        else
        {
            // Add preview icon
            var oPreviewIconElem = $('<a></a>')
                .attr('href', '#')
                .addClass('mmv-preview-icon')
                .addClass('fa')
                .addClass('fa-eye')
                .attr('title', '{$sPreviewIconTooltip}');
            me.find('{$sJSSelectorForFieldLabelElement}').append(oPreviewIconElem);
            
			// Add tooltip on icon
			oPreviewIconElem.attr('data-tooltip-content', '{$sPreviewIconTooltip}');
				
            // Add preview window
            oPreviewIconElem.on('click', function(oEvent){
                oEvent.preventDefault();
                
                // Retrieve value
                var oInputElem = me.find('.field_input_zone textarea').first();
                var sMarkdownValue = MolkobainMarkdownViewer.GetMarkdownFromEditedValue(oInputElem, sFieldFormat);
	            
	            // Show preview
	            CombodoModal.OpenModal({
	                title: '{$sPreviewTitle}',
	                // Note: Otherwise the modal is as narrow as possible, as its content is only made of blocks
	                size: {
	                    width: 500,
	                },
	                classes: {
	                    'ui-dialog-content': 'mmv-preview-content ibo-is-html-content',
	                },
	                content: '',
	                // Note: Called before the modal is opened, so it is sized and positioned with the preview
	                callback_on_content_loaded: function(oModalElem) {
	                    oModalElem.append(MolkobainMarkdownViewer.MakeHtml(sMarkdownValue, oConverterOptions, oSanitizerRules));
	                },
	                buttons: {
	                    close: {
	                        text: '{$sPreviewCloseLabel}',
	                        classes: ['ibo-is-regular', 'ibo-is-neutral'],
	                        callback_on_click: function() {
	                            $(this).dialog('close');
	                        },
	                    },
	                },
	                extra_options: {
	                    callback_on_modal_close: function() {
	                        $(this).dialog('destroy');
	                    },
	                },
	            });
            });
        }
    });
});
JS
		);

		return;
	}
}
