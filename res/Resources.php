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
			'smw/ext.smw.css',
			'smw/ext.smw.skin.css',
			'smw/ext.smw.dropdown.css',
			'smw/ext.smw.table.css',
			'smw/ext.smw.tabs.css',
			'smw/ext.smw.factbox.css'
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
		),
		'targets' => array( 'mobile', 'desktop' )
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

	// VTabs
	'ext.smw.vtabs.styles' => $moduleTemplate + array(
		'styles' => array(
			'smw/util/ext.smw.vertical.tabs.css'
		),
		'position' => 'top',
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	// VTabs
	'ext.smw.vtabs' => $moduleTemplate + array(
		'styles' => array(
			'smw/util/ext.smw.vertical.tabs.css'
		),
		'scripts' => array(
			'smw/util/ext.smw.vertical.tabs.js'
		),
		'position' => 'top',
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	// Modal
	'ext.smw.modal.styles' => $moduleTemplate + array(
		'styles' => array(
			'smw/util/ext.smw.modal.css'
		),
		'position' => 'top',
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	// Modal
	'ext.smw.modal' => $moduleTemplate + array(
		'styles' => array(
			'smw/util/ext.smw.modal.css'
		),
		'scripts' => array(
			'smw/util/ext.smw.modal.js'
		),
		'position' => 'top',
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	// Special:Search
	'ext.smw.special.search.styles' => $moduleTemplate + array(
		'styles' => 'smw/special/ext.smw.special.search.css',
		'position' => 'top',
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	'ext.smw.special.search' => $moduleTemplate + array(
		'scripts' => 'smw/special/ext.smw.special.search.js',
		'styles' => 'smw/special/ext.smw.special.search.css',
		'position' => 'top',
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	// Postproc resources
	'ext.smw.postproc' => $moduleTemplate + array(
		'scripts' => 'smw/util/ext.smw.util.postproc.js',
		'position' => 'top',
		'messages' => array(
			'smw-postproc-queryref'
		),
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	// https://github.com/ichord/Caret.js
	'ext.jquery.caret' => $moduleTemplate + array(
		'scripts' => 'jquery/jquery.caret.js',
		'position' => 'top',
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	// https://github.com/ichord/At.js
	'ext.jquery.atwho' => $moduleTemplate + array(
		'scripts' => 'jquery/jquery.atwho.js',
		'styles' => 'jquery/jquery.atwho.css',
		'position' => 'top',
		'dependencies' => array(
			'ext.jquery.caret'
		),
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	'ext.smw.suggester' => $moduleTemplate + array(
		'scripts' => 'smw/suggester/ext.smw.suggester.js',
		'position' => 'top',
		'dependencies' => array(
			'ext.smw',
			'ext.jquery.atwho'
		),
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	'ext.smw.suggester.textInput' => $moduleTemplate + array(
		'scripts' => 'smw/suggester/ext.smw.suggester.textInput.js',
		'position' => 'top',
		'dependencies' => array(
			'ext.smw',
			'ext.smw.suggester'
		),
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	'ext.smw.autocomplete.article' => $moduleTemplate + array(
		'scripts' => 'smw/util/ext.smw.util.autocomplete.article.js',
		'dependencies' => array(
			'mediawiki.util',
			'ext.jquery.autocomplete'
		),
		'position' => 'bottom',
		'targets' => array( 'mobile', 'desktop' )
	),

	'ext.smw.autocomplete.property' => $moduleTemplate + array(
		'scripts' => 'smw/util/ext.smw.util.autocomplete.property.js',
		'dependencies' => array(
			'mediawiki.util',
			'ext.jquery.autocomplete'
		),
		'position' => 'bottom',
		'targets' => array( 'mobile', 'desktop' )
	),

	// Special:Ask
	'ext.smw.ask.styles' => $moduleTemplate + array(
		'styles' => 'smw/special/ext.smw.special.ask.css',
		'position' => 'top',
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	// Special:Ask
	'ext.smw.ask' => $moduleTemplate + array(
		'scripts' => 'smw/special/ext.smw.special.ask.js',
		'dependencies' => array(
			'ext.smw.tooltip',
			'ext.smw.style',
			'ext.smw.ask.styles',
			'ext.smw.suggester'
		),
		'messages' => array(
			'smw-ask-delete',
			'smw-ask-format-selection-help',
			'smw-ask-condition-change-info',
			'smw-ask-format-change-info',
			'smw-ask-format-export-info',
			'smw-ask-format-help-link',
			'smw-section-expand',
			'smw-section-collapse'
		),
		'position' => 'top',
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	// Table styles
	'ext.smw.table.styles' => $moduleTemplate + array(
		'styles' => array(
			'smw/ext.smw.table.css'
		),
		'position' => 'top',
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	// Facts and browse
	'ext.smw.browse.styles' => $moduleTemplate + array(
		'styles' => array(
			'smw/ext.smw.table.css',
			'smw/special/ext.smw.special.browse.css',
			'smw/special/ext.smw.special.browse.skin.css',
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

	'ext.smw.browse.autocomplete' => $moduleTemplate + array(
		'dependencies' => array(
			'ext.smw.browse',
			'ext.smw.autocomplete.article'
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
			'smw-no-data-available',
			'smw-list-count',
			'smw-list-count-from-cache'
		),
		'position' => 'top',
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	// Personal resource
	'ext.smw.personal' => $moduleTemplate + array(
		'scripts' => 'smw/util/ext.smw.personal.js',
		'dependencies' => array(
			'ext.smw.tooltip',
			'mediawiki.api'
		),
		'messages' => array(
			'smw-personal-jobqueue-watchlist'
		),
		'position' => 'top',
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	// TableResultPrinter resource
	'ext.smw.tableprinter' => $moduleTemplate + array(
		'scripts' => array(
			'smw/printer/ext.smw.tableprinter.js'
		),
		'styles'   => array(
			'smw/printer/ext.smw.tableprinter.css',
			'smw/printer/ext.smw.tableprinter.skin.css'
		),
		'dependencies' => array(
			'onoi.dataTables',
			'ext.smw.query'
		),
		'position' => 'top',
		'messages' => array(
			"smw-format-datatable-emptytable",
			"smw-format-datatable-info",
			"smw-format-datatable-infoempty",
			"smw-format-datatable-infofiltered",
			"smw-format-datatable-infothousands",
			"smw-format-datatable-lengthmenu",
			"smw-format-datatable-loadingrecords",
			"smw-format-datatable-processing",
			"smw-format-datatable-search",
			"smw-format-datatable-zerorecords",
			"smw-format-datatable-first",
			"smw-format-datatable-last",
			"smw-format-datatable-next",
			"smw-format-datatable-previous",
			"smw-format-datatable-sortascending",
			"smw-format-datatable-sortdescending",
			"smw-format-datatable-toolbar-export"
		),
		'targets' => array( 'mobile', 'desktop' )
	),

	// Deferred
	'ext.smw.deferred.styles'  => $moduleTemplate + array(
		'position' => 'top',
		'styles'   => array(
			'smw/deferred/ext.smw.deferred.css',
			'smw/deferred/ext.smw.deferred.skin.css'
		),
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	'ext.smw.deferred'  => $moduleTemplate + array(
		'position' => 'top',
		'styles'   => array(
			'smw/deferred/ext.smw.deferred.css',
			'smw/deferred/ext.smw.deferred.skin.css'
		),
		'scripts'  => array( 'smw/deferred/ext.smw.deferred.js' ),
		'dependencies'  => array(
			'mediawiki.api',
			'mediawiki.api.parse',
			'onoi.rangeslider'
		),
		'messages' => array(
			'smw_result_noresults'
		),
		'targets' => array(
			'mobile',
			'desktop'
		)
	),

	// Page styles
	'ext.smw.page.styles' => $moduleTemplate + array(
		'styles' => array(
			'smw/ext.smw.page.css',
			'smw/ext.smw.table.css'
		),
		'position' => 'top',
		'targets' => array(
			'mobile',
			'desktop'
		)
	),


);
