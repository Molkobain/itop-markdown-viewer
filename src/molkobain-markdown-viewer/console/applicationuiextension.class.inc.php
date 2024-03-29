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

		$bIsItop27OrOlder = !ConfigHelper::IsRunningiTop30OrNewer();
		$sIsItop27OrOlderForJS = $bIsItop27OrOlder ? 'true' : 'false';

		$sModuleVersion = utils::GetCompiledModuleVersion(ConfigHelper::GetModuleCode());
		$sURLBase = utils::GetAbsoluteUrlModulesRoot() . '/' . ConfigHelper::GetModuleCode() . '/';

		// Add css files
		// Note: Here we pass the compiled .css file in order to be compatible with iTop 2.5 and earlier (utils::GetCSSFromSASS() refactoring)
		$oPage->add_saas('env-' . utils::GetCurrentEnvironment() . '/' . ConfigHelper::GetModuleCode() . '/common/css/markdown-viewer.scss');
//        $oPage->add_linked_stylesheet($sURLBase . 'common/css/markdown-viewer.css?v=' . $sModuleVersion);

		// Add js files
		$oPage->add_linked_script($sURLBase . '/common/lib/showdown/showdown.min.js?v=' . $sModuleVersion);

		// Prepare dict entries
		$sPreviewIconTooltip = Dict::S('Molkobain:MarkdownViewer:Preview:Button:Show');
		$sPreviewTitle = Dict::S('Molkobain:MarkdownViewer:Preview:Title');
		$sPreviewCloseLabel = Dict::S('Molkobain:MarkdownViewer:Preview:Button:Close');

		// Prepare JS vars
		$sEditModeAsString = ($bEditMode) ? 'true' : 'false';
		$aAttCodes = ConfigHelper::GetAttributeCodesForObject($oObject);
		$sAttCodesAsJSON = json_encode($aAttCodes);
		$sConverterOptionsAsJSON = json_encode(ConfigHelper::GetMarkdownOptions());
		$iImageMaxWidth = (int) MetaModel::GetConfig()->Get('inline_image_max_display_width');

		// Prepare JS selectors
		if ($bIsItop27OrOlder) {
			$sJSSelectorForFieldElement = '.field_container';
			$sJSSelectorForFieldLabelElement = '.field_label';
			$sJSSelectorForFieldValueElement = '.field_value';
		} else {
			$sJSSelectorForFieldElement = '[data-role="ibo-field"]';
			$sJSSelectorForFieldLabelElement = '.ibo-field--label';
			$sJSSelectorForFieldValueElement = '.ibo-field--value';
		}

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
        var aAttCodes = {$sAttCodesAsJSON};
        var sFieldAttCode = me.attr('data-attribute-code');
        
        // iTop 2.6 and earlier copatibility
        if(sFieldAttCode === undefined)
        {
            sFieldAttCode = me.attr('data-attcode');
        }
        
        // Stop if not a markdown field
        if(aAttCodes.indexOf(sFieldAttCode) < 0)
        {
            return;
        }
        
        // Add widget class
        me.addClass('molkobain-markdown-viewer');
        
        var bEditableAttribute = ((bEditMode === true) && (me.find('{$sJSSelectorForFieldValueElement} > *:first').hasClass('field_value_container') === true));
        // If not editing, view markdown as html...
        if(bEditableAttribute === false)
        {
			// Convert Markdown to HTML
            var oValueElem = me.find('{$sJSSelectorForFieldValueElement} > *');
            var sMarkdownValue = oValueElem.text().replace(/\\n\\n/g, '\\n'); // Note: I don't know why but in read only we have to replace double line endings with a single one. Seems to be the HTML rendering of an AttributeText field that adds them on each lines, making the MarkDown rendering false.
            var oConverter = new showdown.Converter({$sConverterOptionsAsJSON});
            var sHTMLValue = oConverter.makeHtml(sMarkdownValue);
            oValueElem.html(sHTMLValue);
			
			// iTop 3.0+, enforce plain text field to be styled with standard HTML rules
			oValueElem.addClass('ibo-is-html-content');
            
            // Enable image zoom-in
            if(iImageMaxWidth !== 0)
            {
	            oValueElem.find('img').each(function() {
					if ($(this).width() > iImageMaxWidth)
					{
						$(this).css({'max-width': iImageMaxWidth + 'px', width: '', height: '', 'max-height': ''});
					}
					$(this).addClass('inline-image').attr('href', $(this).attr('src'));
				}).magnificPopup({type: 'image', closeOnContentClick: true });
			}
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
			if ({$sIsItop27OrOlderForJS}) {
				oPreviewIconElem.qtip({ style: { name: 'molkobain-dark', tip: 'bottomMiddle' }, position: { corner: { target: 'topMiddle', tooltip: 'bottomMiddle' }} });
			} else {
				oPreviewIconElem.attr('data-tooltip-content', '{$sPreviewIconTooltip}');
			}
				
            // Add preview window
            oPreviewIconElem.on('click', function(oEvent){
                oEvent.preventDefault();
                
                // Retrieve value
                var sMarkdownValue = '';
                var oInputZoneElem = me.find('.field_input_zone');
                if(oInputZoneElem.hasClass('field_input_html') === true)
                {
                    sMarkdownValue = $('<div></div>').html(oInputZoneElem.find('textarea[name="attr_' + sFieldAttCode + '"]').val()).text();
                }
                else
                {
                    sMarkdownValue = oInputZoneElem.find('textarea[name="attr_' + sFieldAttCode + '"]').val();
                }
                var oConverter = new showdown.Converter({$sConverterOptionsAsJSON});
	            var sHTMLValue = oConverter.makeHtml(sMarkdownValue);
	            
	            // Show preview
	            $('<div title="{$sPreviewTitle}" class="mmv-preview-content ibo-is-html-content">'+sHTMLValue+'</div>').dialog({
	                modal: true,
	                minWidth: 500,
	                maxWidth: window.innerHeight * 0.8,
	                maxHeight: window.innerHeight * 0.8,	                
	                buttons:[ {text: '{$sPreviewCloseLabel}', click: function() { $(this).dialog('close'); } }], 
	                close: function() { $(this).remove(); }
	            })
            });
        }
    });
});
JS
		);

		return;
	}
}
