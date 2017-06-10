<?php

/**
 * Collection of resource module definitions
 *
 * @license GNU GPL v2 or later
 * @since 1.8
 *
 * @author mwjames
 */

// #1466 (Make sure to work on both Win and Ux)
$pathParts = explode( '/', str_replace( DIRECTORY_SEPARATOR, '/', __DIR__ ) );

$moduleTemplate = array(
	'localBasePath' => __DIR__,
	'remoteExtPath' => implode( '/', array_slice( $pathParts, -2 ) ),
	'group' => 'ext.smw'
);

return array(
	// SMW core class
	'ext.smw' => $moduleTemplate + array(
		'scripts' => 'smw/ext.smw.js',
		'dependencies' => 'ext.jquery.async',
		'targets' => array( 'mobile', 'desktop' )
	),

	// Resource is loaded at the top otherwise the stylesheet will only
	// become active after all content is loaded with icons appearing with a
	// delay due to missing stylesheet definitions at the time of the display
	'ext.smw.style' => $moduleTemplate + array(
		'styles' => array(
			'smw/ext.smw.css'
		),
		'position' => 'top',
		'targets' => array( 'mobile', 'desktop' )
	),

	'ext.smw.special.style' => $moduleTemplate + array(
		'styles' => array(
			'smw/special/ext.smw.special.css'
		),
		'position' => 'top',
		'targets' => array( 'mobile', 'desktop' )
	),

	// Load the module explicitly, otherwise mobile will complain with
	// "Uncaught Error: Unknown dependency: jquery.async"
	'ext.jquery.async' => $moduleTemplate + array(
		'scripts' => 'jquery/jquery.async.js',
		'targets' => array( 'mobile', 'desktop' )
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
			'mediawiki.util',
			'ext.smw.data',
			'ext.smw.query',
			'ext.jquery.jStorage',
			'ext.jquery.md5'
		)
	),

	// https://github.com/devbridge/jQuery-Autocomplete
	'ext.jquery.autocomplete' => $moduleTemplate + array(
		'scripts' => 'jquery/jquery.autocomplete.js',
		'targets' => array( 'mobile', 'desktop' )
	),

	// Tooltip qtip2 resources
	'ext.jquery.qtip.styles' => $moduleTemplate + array(
		'styles' => 'jquery/jquery.qtip.css',
		'targets' => array( 'mobile', 'desktop' )
	),

	// Tooltip qtip2 resources
	'ext.jquery.qtip' => $moduleTemplate + array(
		'scripts' => 'jquery/jquery.qtip.js',
		'targets' => array( 'mobile', 'desktop' )
	),

	// Tooltip
	'ext.smw.tooltip.styles' => $moduleTemplate + array(
		'styles' => array(
			// Style dependencies don't work
			// therefore make sure to load it
			// together
			'jquery/jquery.qtip.css',
			'smw/util/ext.smw.util.tooltip.css'
		),
		'position' => 'top',
		'targets' => array( 'mobile', 'desktop' )
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
		),
		'targets' => array( 'mobile', 'desktop' )
	),

	'ext.smw.tooltips' => $moduleTemplate + array(
		'dependencies' => array(
			'ext.smw.style',
			'ext.smw.tooltip'
		),
		'targets' => array( 'mobile', 'desktop' )
	),

	// Autocomplete resources
	'ext.smw.autocomplete' => $moduleTemplate + array(
		'scripts' => 'smw/util/ext.smw.util.autocomplete.js',
		'dependencies' => 'jquery.ui.autocomplete',
		'targets' => array( 'mobile', 'desktop' )
	),
	
	// Purge resources
	'ext.smw.purge' => $moduleTemplate + array(
		'scripts' => 'smw/util/ext.smw.util.purge.js',
		'messages' => array(
			'smw-purge-failed'
		),
		'position' => 'top',
		'targets' => array(
			'mobile',
			'desktop'
		)
	),
	
	// Special:Ask
	'ext.smw.ask' => $moduleTemplate + array(
		'scripts' => 'smw/special/ext.smw.special.ask.js',
		'styles' => 'smw/special/ext.smw.special.ask.css',
		'dependencies' => array(
			'ext.smw.tooltip',
			'ext.smw.style'
		),
		'messages' => array(
			'smw-ask-delete',
			'smw-ask-format-selection-help'
		),
		'position' => 'top'
	),

	// Facts and browse
	'ext.smw.browse.styles' => $moduleTemplate + array(
		'styles' => array(
			'smw/ext.smw.table.css',
			'smw/special/ext.smw.special.browse.css',
		),
		'position' => 'top',
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	'ext.smw.browse' => $moduleTemplate + array(
		'scripts' => 'smw/special/ext.smw.special.browse.js',
		'dependencies' => array(
			'mediawiki.api',
			'ext.smw.style'
		),
		'position' => 'top',
		'messages' => array(
			'smw-browse-api-subject-serialization-invalid'
		),
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	'ext.smw.browse.page.autocomplete' => $moduleTemplate + array(
		'dependencies' => array(
			'ext.smw.browse',
			'ext.smw.autocomplete'
		),
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	// Special:Admin/SemanticMediaWiki
	'ext.smw.admin' => $moduleTemplate + array(
		'scripts' => 'smw/special/ext.smw.special.admin.js',
		'dependencies' => array(
			'mediawiki.api'
		),
		'messages' => array(
			'smw-no-data-available'
		),
		'position' => 'top',
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	// Special:SearchByProperty
	'ext.smw.property' => $moduleTemplate + array(
		'scripts' => 'smw/special/ext.smw.special.property.js',
		'dependencies' => array(
			'mediawiki.util',
			'ext.jquery.autocomplete'
		),
		'position' => 'bottom',
		'targets' => array( 'mobile', 'desktop' )
	)
);
