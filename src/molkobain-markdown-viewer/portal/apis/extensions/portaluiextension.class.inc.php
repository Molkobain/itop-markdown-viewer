<?php
/**
 * Copyright (c) 2015 - 2019 Molkobain.
 *
 * This file is part of licensed extension.
 *
 * Use of this extension is bound by the license you purchased. A license grants you a non-exclusive and non-transferable right to use and incorporate the item in your personal or commercial projects. There are several licenses available (see https://www.molkobain.com/usage-licenses/ for more informations)
 */

namespace Molkobain\iTop\Extension\MarkdownViewer\Portal\Extension;

use AbstractPortalUIExtension;
use utils;
use Dict;
use MetaModel;
use Silex\Application;
use Molkobain\iTop\Extension\MarkdownViewer\Common\Helper\ConfigHelper;

// Compatibility check as there is no html markup to find the object class before iTop 2.6.0
if(version_compare(ITOP_VERSION, '2.6.0', '>='))
{
	/**
	 * Class PortalUIExtension
	 *
	 * @package Molkobain\iTop\Extension\MarkdownViewer\Portal\Extension
	 */
	class PortalUIExtension extends AbstractPortalUIExtension
	{
		/**
		 * @inheritdoc
		 */
		public function GetCSSFiles(Application $oApp)
		{
			// Check if enabled
			if(ConfigHelper::IsEnabled() === false)
			{
				return array();
			}

			$sModuleVersion = utils::GetCompiledModuleVersion(ConfigHelper::GetModuleCode());
			$sURLBase = utils::GetAbsoluteUrlModulesRoot() . '/' . ConfigHelper::GetModuleCode() . '/';

			// Note: Here we pass the compiled .css file in order to be compatible with iTop 2.5 and earlier (ApplicationHelper::LoadUIExtensions() refactoring that uses utils::GetCSSFromSASS())
			$aReturn = array(
				$sURLBase . 'common/css/markdown-viewer.css?v=' . $sModuleVersion,
			);

			return $aReturn;
		}

		/**
		 * @inheritdoc
		 */
		public function GetJSFiles(Application $oApp)
		{
			// Check if enabled
			if(ConfigHelper::IsEnabled() === false)
			{
				return array();
			}

			$sModuleVersion = utils::GetCompiledModuleVersion(ConfigHelper::GetModuleCode());
			$sURLBase = utils::GetAbsoluteUrlModulesRoot() . '/' . ConfigHelper::GetModuleCode() . '/';

			$aJSFiles = array(
				$sURLBase . '/common/lib/showdown/showdown.min.js?v=' . $sModuleVersion,
			);

			return $aJSFiles;
		}

		/**
		 * @inheritdoc
		 *
		 * @throws \DictExceptionMissingString
		 */
		public function GetJSInline(Application $oApp)
		{
			// Check if enabled
			if(ConfigHelper::IsEnabled() === false)
			{
				return '';
			}

			// Prepare dict entries
			$sPreviewIconTooltip = Dict::S('Molkobain:MarkdownViewer:Preview:Button:Show');
			$sPreviewTitle = Dict::S('Molkobain:MarkdownViewer:Preview:Title');
			$sPreviewCloseLabel = Dict::S('Molkobain:MarkdownViewer:Preview:Button:Close');

			// Prepare JS vars
			$aAllAttCodes = ConfigHelper::GetAttributeCodes();
			$sAllAttCodesAsJSON = json_encode($aAllAttCodes);
			$iImageMaxWidth = (int) MetaModel::GetConfig()->Get('inline_image_max_display_width', 0);

			$sJSInline =
<<<EOF
// Molkobain markdown viewer
function InstanciateMarkdownViewer(oElem)
{
    var iImageMaxWidth = {$iImageMaxWidth};
    var oAllAttCodes = {$sAllAttCodesAsJSON};
    var bEditMode = (oElem.attr('data-form-mode') !== 'view') ? true : false;
    var sObjClass = oElem.attr('data-object-class');
    
    // Stop if object not concerned
    if(oAllAttCodes.hasOwnProperty(sObjClass) === false)
    {
        return;
    }
    
    oElem.find('[data-field-id]').each(function(){
        var me = $(this);
        var sFieldAttCode = me.attr('data-field-id');
        
        // Stop if not a markdown field
        if(oAllAttCodes[sObjClass].indexOf(sFieldAttCode) < 0)
        {
            return;
        }
        
        // Add widget class
        me.addClass('molkobain-markdown-viewer');
        
        // If not editing, view markdown as html...
        if(bEditMode === false)
        {
            // Convert Markdown to HTML
            var oValueElem = me.find('.form_field_control .form-control-static > *');
            var oConverter = new showdown.Converter();
            var sMarkdownValue = oValueElem.text();
            var sHTMLValue = oConverter.makeHtml(sMarkdownValue);
            oValueElem.html(sHTMLValue);
            
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
                .attr('title', '{$sPreviewIconTooltip}')
                .attr('data-toggle', 'tooltip')
                .tooltip();
            me.find('.form_field_label').append(oPreviewIconElem);
            
            // Add preview window
            oPreviewIconElem.on('click', function(oEvent){
                oEvent.preventDefault();
                
                // Retrieve value
                if(me.hasClass('portal_form_field_html') === true)
                {
                    var sMarkdownValue = $('<div></div>').html(me.portal_form_field_html('getCurrentValue')).text();
                }
                else
                {
                    var sMarkdownValue = me.portal_form_field('getCurrentValue');
                }
                var oConverter = new showdown.Converter();
	            var sHTMLValue = oConverter.makeHtml(sMarkdownValue);
	            
	            // Show preview
                var oModalElem = $('#modal-for-alert')
                    .clone()
                    .attr('id', '')
                    .appendTo('body');
                oModalElem.find('.modal-content')
                    .addClass('mmv-preview-content')
                    .append( $('<div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">{$sPreviewCloseLabel}</button></div>') );
                oModalElem.find('.modal-title').text('{$sPreviewTitle}');
				oModalElem.find('.modal-body').html(sHTMLValue);
				oModalElem.modal('show');
            });
        }
    });
}

// Instanciate widget on modals
$('body').on('loaded.bs.modal', function (oEvent) {
    setTimeout(function(){
        var oForm = $(oEvent.target).find('.modal-content .portal_form_handler');
        if(oForm.length > 0)
        {
            InstanciateMarkdownViewer(oForm);
        }
    }, 200);
});

// Instanciate widget on initial elements
$(document).ready(function(){
    $('.portal_form_handler').each(function(){
        InstanciateMarkdownViewer($(this));
    });
});
EOF;

			return $sJSInline;
		}
	}
}
