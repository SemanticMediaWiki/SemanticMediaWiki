<?php

namespace SMW;

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

global $smwgIP, $smwgScriptPath;

$moduleTemplate = array(
	'localBasePath' => $smwgIP ,
	'remoteBasePath' => $smwgScriptPath,
	'group' => 'ext.smw'
);

return array(
	// SMW core class
	'ext.smw' => $moduleTemplate + array(
		'scripts' => array(
			'resources/ext.smw.js'
		)
	),

	// Common styles independent from JavaScript
	'ext.smw.style' => $moduleTemplate + array(
		'styles' => 'resources/ext.smw.core.css',
		'position' => 'top'
	),

	// jStorage was added in MW 1.20
	'ext.jquery.jStorage' => $moduleTemplate + array(
		'scripts' => 'resources/jquery/jquery.jstorage.js',
		'dependencies' => 'jquery.json',
	),

	// md5 hash key generator
	'ext.jquery.md5' => $moduleTemplate + array(
		'scripts' => 'resources/jquery/jquery.md5.js'
	),

	// dataItem representation
	'ext.smw.dataItem' => $moduleTemplate + array(
		'scripts' => array(
			'resources/smw.data/ext.smw.dataItem.wikiPage.js',
			'resources/smw.data/ext.smw.dataItem.uri.js',
			'resources/smw.data/ext.smw.dataItem.time.js',
			'resources/smw.data/ext.smw.dataItem.property.js',
			'resources/smw.data/ext.smw.dataItem.unknown.js',
			'resources/smw.data/ext.smw.dataItem.number.js',
			'resources/smw.data/ext.smw.dataItem.text.js',
		),
		'dependencies' => array(
			'ext.smw',
			'mediawiki.Title',
			'mediawiki.Uri'
		)
	),

	// dataValue representation
	'ext.smw.dataValue' => $moduleTemplate + array(
		'scripts' => array(
			'resources/smw.data/ext.smw.dataValue.quantity.js',
		),
		'dependencies' => 'ext.smw.dataItem'
	),

	// dataItem representation
	'ext.smw.data' => $moduleTemplate + array(
		'scripts' => 'resources/smw.data/ext.smw.data.js',
		'dependencies' => array(
			'ext.smw.dataItem',
			'ext.smw.dataValue'
		)
	),

	// Query
	'ext.smw.query' => $moduleTemplate + array(
		'scripts' => 'resources/smw.query/ext.smw.query.js',
		'dependencies' => array(
			'ext.smw',
			'mediawiki.util'
		)
	),

	// API
	'ext.smw.api' => $moduleTemplate + array(
		'scripts' => 'resources/smw.api/ext.smw.api.js',
		'dependencies' => array(
			'ext.smw.data',
			'ext.smw.query',
			'ext.jquery.jStorage',
			'ext.jquery.md5'
		)
	),

	// This one is obsolete since SMW_QueryUI.php isn't officially supported
	'ext.smw.query.ui' => $moduleTemplate + array(
		'styles' => 'resources/ext.smw.query.ui.css'
	),

	// Tooltip qtip2 resources
	'ext.jquery.qtip' => $moduleTemplate + array(
		'scripts' => 'resources/jquery/jquery.qtip.js',
		'styles' => 'resources/jquery/jquery.qtip.css',
	),
	// Tooltip
	'ext.smw.tooltip' => $moduleTemplate + array(
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
		)
	),
	// Resource is loaded at the top otherwise the stylesheet will only
	// become active after all content is loaded with icons appearing with a
	// delay due to missing stylesheet definitions at the time of the display
	'ext.smw.tooltips' => $moduleTemplate + array(
		'dependencies' => array(
			'ext.smw.style',
			'ext.smw.tooltip'
		),
		'position' => 'top'
	),
	// Autocomplete resources
	'ext.smw.autocomplete' => $moduleTemplate + array(
		'scripts' => 'resources/ext.smw.util.autocomplete.js',
		'dependencies' => 'jquery.ui.autocomplete'
	),
	// Special:Ask
	'ext.smw.ask' => $moduleTemplate + array(
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
	),
	// Facts and browse
	'ext.smw.browse' => $moduleTemplate + array(
		'scripts' => 'resources/ext.smw.special.browse.js',
		'dependencies' => array(
			'ext.smw.style',
			'ext.smw.autocomplete'
		),
		'position' => 'top'
	),
	// Special:SearchByProperty
	'ext.smw.property' => $moduleTemplate + array(
		'scripts' => 'resources/ext.smw.special.property.js',
		'dependencies' => 'ext.smw.autocomplete'
	)
);