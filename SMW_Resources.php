<?php

/**
 * Collection of resource module definitions
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.8
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2 or later
 * @author mwjames
 */

$moduleTemplate = array(
	'localBasePath' => $smwgIP,
	'remoteBasePath' => $smwgScriptPath,
	'group' => 'ext.smw'
);

/******************************************************************************/
/* SMW core
/******************************************************************************/
$wgResourceModules['ext.smw'] = $moduleTemplate + array(
	'scripts' => array(
		'resources/ext.smw.js',
		'resources/ext.smw.compat.js',
	),
);

$wgResourceModules['ext.smw.style'] = $moduleTemplate + array(
	'styles' => 'resources/ext.smw.core.css'
);

$wgResourceModules['ext.smw.query.ui'] = $moduleTemplate + array(
	'styles' => 'resources/ext.smw.query.ui.css'
);

/******************************************************************************/
/* Tooltip resources
/******************************************************************************/
$wgResourceModules['ext.jquery.qtip'] = $moduleTemplate + array(
	'scripts' => 'resources/jquery/jquery.qtip.js',
	'styles' => 'resources/jquery/jquery.qtip.css',
);

$wgResourceModules['ext.smw.tooltip'] = $moduleTemplate + array(
	'scripts' => 'resources/ext.smw.util.tooltip.js',
	'styles' => 'resources/ext.smw.util.tooltip.css',
	'dependencies' => array(
		'ext.smw',
		'ext.jquery.qtip'
	),
	'messages' => array(
		'smw-ui-tooltip-title-property',
		'smw-ui-tooltip-title-quantity',
		'smw-ui-tooltip-title-info',
		'smw-ui-tooltip-title-service',
		'smw-ui-tooltip-title-warning',
		'smw-ui-tooltip-title-parameter',
		'smw-ui-tooltip-title-event',
	),
);

// Resource is loaded at the top otherwise the stylesheet will only
// become active after all content is loaded with icons appearing with a
// delay due to missing stylesheet definitions at the time of the display
$wgResourceModules['ext.smw.tooltips'] = $moduleTemplate + array(
	'dependencies' => array(
		'ext.smw.tooltip'
	),
	'position' => 'top'
);

/******************************************************************************/
/* Autocomplete resources
/******************************************************************************/
$wgResourceModules['ext.smw.autocomplete'] = $moduleTemplate + array(
	'scripts' => 'resources/ext.smw.util.autocomplete.js',
	'dependencies' => array(
		// 'jquery.ui.widget',
		// 'jquery.ui.position',
		'jquery.ui.autocomplete'
	)
);

/******************************************************************************/
/* Special:Ask
/******************************************************************************/
$wgResourceModules['ext.smw.ask'] = $moduleTemplate + array(
	'scripts' => 'resources/ext.smw.special.ask.js',
	'styles' => 'resources/ext.smw.special.ask.css',
	'dependencies' => array(
		'ext.smw.tooltip',
		'ext.smw.style',
		'ext.smw.autocomplete'
	),
	'messages' => array(
		'smw-ask-delete',
		'smw-ask-format-selection-help'
	),
	'position' => 'top'
);

/******************************************************************************/
/* Special:Browse
/******************************************************************************/
// should replace $wgOut->addStyle( '../extensions/SemanticMediaWiki/resources/ext..css' ); line 104
// and since it was loaded at the top we do the same here with position -> top
$wgResourceModules['ext.smw.browse'] = $moduleTemplate + array(
	'scripts' => 'resources/ext.smw.special.browse.js',
	'dependencies' => array(
		'ext.smw.style',
		'ext.smw.autocomplete'
	),
	'position' => 'top'
);

/******************************************************************************/
/* Special:SearchByProperty
/******************************************************************************/
$wgResourceModules['ext.smw.property'] = $moduleTemplate + array(
	'scripts' => 'resources/ext.smw.special.property.js',
	'dependencies' => 'ext.smw.autocomplete'
);

unset( $moduleTemplate );