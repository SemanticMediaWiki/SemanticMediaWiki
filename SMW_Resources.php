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

$wgResourceModules['ext.smw'] = $moduleTemplate + array(
	'scripts' => array(
		'resources/ext.smw.js',
		'resources/ext.smw.compat.js',
	),
);

$wgResourceModules['ext.smw.style'] = $moduleTemplate + array(
	'styles' => 'skins/SMW_custom.css'
);

/******************************************************************************/
/* Tooltip resources
/******************************************************************************/
$wgResourceModules['ext.jquery.qtip'] = $moduleTemplate + array(
	'scripts' => 'resources/jquery/jquery.qtip.js',
	'styles' => 'resources/jquery/jquery.qtip.css',
);

$wgResourceModules['ext.smw.tooltip'] = $moduleTemplate + array(
	'scripts' => 'resources/ext.smw.tooltip.js',
	'styles' => 'resources/ext.smw.tooltip.css',
	'dependencies' => 'ext.jquery.qtip',
	'messages' => array(
		'smw-ui-tooltip-title-property',
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
		'ext.smw.style',
		'ext.smw.tooltip'
	),
	'position' => 'top'
);

/******************************************************************************/
/* Autocomplete resources
/******************************************************************************/
$wgResourceModules['ext.smw.autocomplete'] = $moduleTemplate + array(
	'scripts' => 'resources/ext.smw.autocomplete.js',
	'dependencies' => array(
		// 'jquery.ui.widget',
		// 'jquery.ui.position',
		'jquery.ui.autocomplete'
	)
);

$wgResourceModules['ext.smw.ask'] = $moduleTemplate + array(
	'scripts' => array(
		'resources/ext.smw.ask.js',
	),
	'dependencies' => array(
		'jquery.tipsy',
		'ext.smw.style',
		'ext.smw.autocomplete'
	),
	'messages' => array(
		'smw-ask-delete',
	),
	'position' => 'top'
);

$wgResourceModules['ext.smw.browse'] = $moduleTemplate + array(
	'scripts' => 'resources/ext.smw.browse.js',
	'dependencies' => 'ext.smw.autocomplete'
);

$wgResourceModules['ext.smw.property'] = $moduleTemplate + array(
	'scripts' => 'resources/ext.smw.property.js',
	'dependencies' => 'ext.smw.autocomplete'
);

unset( $moduleTemplate );