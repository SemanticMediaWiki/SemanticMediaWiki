<?php

namespace SMW;

/**
 * Collection of resource module definitions
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
	'localBasePath' => __DIR__,
	'remoteExtPath' => end( ( explode( DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR , __DIR__, 2 ) ) ),
	'group' => 'ext.smw'
);

return array(
	// SMW core class
	'ext.smw' => $moduleTemplate + array(
		'scripts' => 'smw/ext.smw.js',
		'dependencies' => 'jquery.async',
	),

	// Resource is loaded at the top otherwise the stylesheet will only
	// become active after all content is loaded with icons appearing with a
	// delay due to missing stylesheet definitions at the time of the display
	'ext.smw.style' => $moduleTemplate + array(
		'styles' => array(
			'smw/ext.smw.css'
		),
		'dependencies' => array(
			'ext.smw.tooltip.styles'
		),
		'position' => 'top'
	),

	// MW 1.24+ Fix Uncaught Error: Unknown dependency: jquery.json 
	// Introducing a mega-hack 
	// jStorage was added in MW 1.20
	'ext.jquery.jStorage' => $moduleTemplate + array(
		'scripts' => 'jquery/jquery.jstorage.js',
		'dependencies' => version_compare( $GLOBALS['wgVersion'], '1.24', '<' ) ? 'jquery.json' : 'json',
	),

	// md5 hash key generator
	'ext.jquery.md5' => $moduleTemplate + array(
		'scripts' => 'jquery/jquery.md5.js'
	),

	// dataItem representation
	'ext.smw.dataItem' => $moduleTemplate + array(
		'scripts' => array(
			'smw/data/ext.smw.dataItem.wikiPage.js',
			'smw/data/ext.smw.dataItem.uri.js',
			'smw/data/ext.smw.dataItem.time.js',
			'smw/data/ext.smw.dataItem.property.js',
			'smw/data/ext.smw.dataItem.unknown.js',
			'smw/data/ext.smw.dataItem.number.js',
			'smw/data/ext.smw.dataItem.text.js',
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
			'smw/data/ext.smw.dataValue.quantity.js',
		),
		'dependencies' => 'ext.smw.dataItem'
	),

	// dataItem representation
	'ext.smw.data' => $moduleTemplate + array(
		'scripts' => 'smw/data/ext.smw.data.js',
		'dependencies' => array(
			'ext.smw.dataItem',
			'ext.smw.dataValue'
		)
	),

	// Query
	'ext.smw.query' => $moduleTemplate + array(
		'scripts' => 'smw/query/ext.smw.query.js',
		'dependencies' => array(
			'ext.smw',
			'mediawiki.util'
		)
	),

	// API
	'ext.smw.api' => $moduleTemplate + array(
		'scripts' => 'smw/api/ext.smw.api.js',
		'dependencies' => array(
			'ext.smw.data',
			'ext.smw.query',
			'ext.jquery.jStorage',
			'ext.jquery.md5'
		)
	),

	// Tooltip qtip2 resources
	'ext.jquery.qtip.styles' => $moduleTemplate + array(
		'styles' => 'jquery/jquery.qtip.css'
	),

	// Tooltip qtip2 resources
	'ext.jquery.qtip' => $moduleTemplate + array(
		'scripts' => 'jquery/jquery.qtip.js',
		'dependencies' => array(
			'ext.jquery.qtip.styles'
		)
	),

	// Tooltip
	'ext.smw.tooltip.styles' => $moduleTemplate + array(
		'styles' => 'smw/util/ext.smw.util.tooltip.css',
		'dependencies' => array(
			'ext.jquery.qtip.styles'
		)
	),

	// Tooltip
	'ext.smw.tooltip' => $moduleTemplate + array(
		'scripts' => 'smw/util/ext.smw.util.tooltip.js',
		'dependencies' => array(
			'ext.smw.tooltip.styles',
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

	'ext.smw.tooltips' => $moduleTemplate + array(
		'dependencies' => array(
			'ext.smw.style',
			'ext.smw.tooltip'
		)
	),

	// Autocomplete resources
	'ext.smw.autocomplete' => $moduleTemplate + array(
		'scripts' => 'smw/util/ext.smw.util.autocomplete.js',
		'dependencies' => 'jquery.ui.autocomplete'
	),
	// Special:Ask
	'ext.smw.ask' => $moduleTemplate + array(
		'scripts' => 'smw/special/ext.smw.special.ask.js',
		'styles' => 'smw/special/ext.smw.special.ask.css',
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
		'scripts' => 'smw/special/ext.smw.special.browse.js',
		'dependencies' => array(
			'ext.smw.style',
			'ext.smw.autocomplete'
		)
	),

	// Special:SearchByProperty
	'ext.smw.property' => $moduleTemplate + array(
		'scripts' => 'smw/special/ext.smw.special.property.js',
		'dependencies' => 'ext.smw.autocomplete'
	)
);
