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

$moduleTemplate = [
	'localBasePath' => __DIR__,
	'remoteExtPath' => implode( '/', array_slice( $pathParts, -2 ) ),
	'group' => 'ext.smw'
];

return [
	// SMW core class
	'ext.smw' => $moduleTemplate + [
		'scripts' => 'smw/ext.smw.js',
		'dependencies' => 'ext.jquery.async',
		'targets' => [ 'mobile', 'desktop' ]
	],

	// Resource is loaded at the top otherwise the stylesheet will only
	// become active after all content is loaded with icons appearing with a
	// delay due to missing stylesheet definitions at the time of the display
	'ext.smw.style' => $moduleTemplate + [
		'styles' => [
			'smw/ext.smw.css',
			'smw/ext.smw.skin.css',
			'smw/ext.smw.dropdown.css',
			'smw/ext.smw.table.css',
			'smw/ext.smw.tabs.css',
			'smw/factbox/smw.factbox.css',
			'smw/smw.indicators.css'
		],
		'position' => 'top',
		'targets' => [ 'mobile', 'desktop' ]
	],

	'ext.smw.special.styles' => $moduleTemplate + [
		'styles' => [
			'smw/special/smw.special.preferences.css'
		],
		'targets' => [ 'mobile', 'desktop' ]
	],

	'smw.ui' => $moduleTemplate + [
		'scripts' => 'smw/smw.ui.js',
		'dependencies' => [ 'ext.smw', 'jquery.selectmenu' ],
		'targets' => [ 'mobile', 'desktop' ]
	],

	'smw.ui.styles' => $moduleTemplate + [
		'styles' => [
			'jquery/jquery.selectmenu.css',
			'smw/smw.selectmenu.css'
		],
		'position' => 'top',
		'targets' => [ 'mobile', 'desktop' ]
	],

	'smw.summarytable' => $moduleTemplate + [
		'styles' => [
			'smw/smw.summarytable.css'
		],
		'position' => 'top',
		'targets' => [ 'mobile', 'desktop' ]
	],

	'ext.smw.special.style' => $moduleTemplate + [
		'styles' => [
			'smw/special/ext.smw.special.css'
		],
		'position' => 'top',
		'targets' => [ 'mobile', 'desktop' ]
	],

	// https://github.com/TerryZ/SelectMenu
	'jquery.selectmenu' => $moduleTemplate + [
		'scripts' => 'jquery/jquery.selectmenu.js',
		'dependencies' => [
		'jquery.selectmenu.styles'
		],
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'jquery.selectmenu.styles' => $moduleTemplate + [
		'styles' => [
			'jquery/jquery.selectmenu.css',
			'smw/smw.selectmenu.css'
		],
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'jquery.jsonview' => $moduleTemplate + [
		'scripts' => 'jquery/jquery.jsonview.js',
		'styles' => 'jquery/jquery.jsonview.css',
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// Load the module explicitly, otherwise mobile will complain with
	// "Uncaught Error: Unknown dependency: jquery.async"
	'ext.jquery.async' => $moduleTemplate + [
		'scripts' => 'jquery/jquery.async.js',
		'targets' => [ 'mobile', 'desktop' ]
	],

	// Avoid "Warning: Use of the json module is deprecated since MediaWiki 1.29"
	// jStorage was added in MW 1.20
	'ext.jquery.jStorage' => $moduleTemplate + [
		'scripts' => 'jquery/jquery.jstorage.js',
		'dependencies' => version_compare( MW_VERSION, '1.29', '<' ) ? 'json' : [],
	],

	// md5 hash key generator
	'ext.jquery.md5' => $moduleTemplate + [
		'scripts' => 'jquery/jquery.md5.js'
	],

	// dataItem representation
	'ext.smw.dataItem' => $moduleTemplate + [
		'scripts' => [
			'smw/data/ext.smw.dataItem.wikiPage.js',
			'smw/data/ext.smw.dataItem.uri.js',
			'smw/data/ext.smw.dataItem.time.js',
			'smw/data/ext.smw.dataItem.property.js',
			'smw/data/ext.smw.dataItem.unknown.js',
			'smw/data/ext.smw.dataItem.number.js',
			'smw/data/ext.smw.dataItem.text.js',
			'smw/data/ext.smw.dataItem.geo.js',
		],
		'dependencies' => [
			'ext.smw',
			'mediawiki.Title',
			'mediawiki.Uri'
		]
	],

	// dataValue representation
	'ext.smw.dataValue' => $moduleTemplate + [
		'scripts' => [
			'smw/data/ext.smw.dataValue.quantity.js',
		],
		'dependencies' => 'ext.smw.dataItem'
	],

	// dataItem representation
	'ext.smw.data' => $moduleTemplate + [
		'scripts' => 'smw/data/ext.smw.data.js',
		'dependencies' => [
			'ext.smw.dataItem',
			'ext.smw.dataValue'
		]
	],

	// Query
	'ext.smw.query' => $moduleTemplate + [
		'scripts' => 'smw/query/ext.smw.query.js',
		'dependencies' => [
			'ext.smw',
			'mediawiki.util'
		],
		'targets' => [ 'mobile', 'desktop' ]
	],

	// API
	'ext.smw.api' => $moduleTemplate + [
		'scripts' => 'smw/api/ext.smw.api.js',
		'dependencies' => [
			'mediawiki.util',
			'ext.smw.data',
			'ext.smw.query',
			'ext.jquery.jStorage',
			'ext.jquery.md5'
		]
	],

	// https://github.com/devbridge/jQuery-Autocomplete
	'ext.jquery.autocomplete' => $moduleTemplate + [
		'scripts' => 'jquery/jquery.autocomplete.js',
		'targets' => [ 'mobile', 'desktop' ]
	],

	// Tooltip qtip2 resources
	'ext.jquery.qtip.styles' => $moduleTemplate + [
		'styles' => 'jquery/jquery.qtip.css',
		'targets' => [ 'mobile', 'desktop' ]
	],

	// Tooltip qtip2 resources
	'ext.jquery.qtip' => $moduleTemplate + [
		'scripts' => 'jquery/jquery.qtip.js',
		'targets' => [ 'mobile', 'desktop' ]
	],

	// Tooltip
	'ext.smw.tooltip.styles' => $moduleTemplate + [
		'styles' => [
			'smw/util/ext.smw.util.tooltip.css'
		],
		'position' => 'top',
		'targets' => [ 'mobile', 'desktop' ]
	],

	// Tooltip
	'ext.smw.tooltip.old' => $moduleTemplate + [
		'scripts' => 'smw/util/ext.smw.util.tooltip.js',
		'dependencies' => [
			'ext.smw.tooltip.styles',
			'ext.smw',
			'ext.jquery.qtip'
		],
		'messages' => [
			'smw-ui-tooltip-title-property',
			'smw-ui-tooltip-title-quantity',
			'smw-ui-tooltip-title-info',
			'smw-ui-tooltip-title-service',
			'smw-ui-tooltip-title-warning',
			'smw-ui-tooltip-title-parameter',
			'smw-ui-tooltip-title-event',
		],
		'targets' => [ 'mobile', 'desktop' ]
	],

	// Tooltip
	'ext.smw.tooltip' => $moduleTemplate + [
		'dependencies' => [
			'ext.smw.tooltip.styles',
			'smw.tippy'
		],
		'targets' => [ 'mobile', 'desktop' ]
	],

	'ext.smw.tooltips' => $moduleTemplate + [
		'dependencies' => [
			'ext.smw.style',
			'smw.tippy'
		],
		'targets' => [ 'mobile', 'desktop' ]
	],

	// Autocomplete resources
	'ext.smw.autocomplete' => $moduleTemplate + [
		'scripts' => 'smw/util/ext.smw.util.autocomplete.js',
		'dependencies' => 'jquery.ui.autocomplete',
		'targets' => [ 'mobile', 'desktop' ]
	],

	// Purge resources
	'ext.smw.purge' => $moduleTemplate + [
		'scripts' => 'smw/util/ext.smw.util.purge.js',
		'messages' => [
			'smw-purge-failed',
			'smw-purge-update-dependencies'
		],
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// VTabs
	'ext.smw.vtabs.styles' => $moduleTemplate + [
		'styles' => [
			'smw/util/ext.smw.vertical.tabs.css'
		],
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// VTabs
	'ext.smw.vtabs' => $moduleTemplate + [
		'styles' => [
			'smw/util/ext.smw.vertical.tabs.css'
		],
		'scripts' => [
			'smw/util/ext.smw.vertical.tabs.js'
		],
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// Modal
	'ext.smw.modal.styles' => $moduleTemplate + [
		'styles' => [
			'smw/util/ext.smw.modal.css'
		],
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// Modal
	'ext.smw.modal' => $moduleTemplate + [
		'styles' => [
			'smw/util/ext.smw.modal.css'
		],
		'scripts' => [
			'smw/util/ext.smw.modal.js'
		],
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// Special:Search
	'smw.special.search.styles' => $moduleTemplate + [
		'styles' => 'smw/special.search/search.css',
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'smw.special.search' => $moduleTemplate + [
		'scripts' => [
			'smw/special.search/search.namespace.js',
			'smw/special.search/search.input.js',
			'smw/special.search/search.form.js'
		],
		'styles' => 'smw/special.search/search.css',
		'position' => 'top',
		'dependencies' => [
			'ext.smw',
			'smw.ui'
		],
		'targets' => [
			'mobile',
			'desktop'
		],
		'messages' => [
			'smw-search-hide',
			'smw-search-show',
		],
	],

	// Postproc resources
	'ext.smw.postproc' => $moduleTemplate + [
		'scripts' => 'smw/util/ext.smw.util.postproc.js',
		'position' => 'top',
		'messages' => [
			'smw-postproc-queryref'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// https://github.com/ichord/Caret.js
	'ext.jquery.caret' => $moduleTemplate + [
		'scripts' => 'jquery/jquery.caret.js',
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// https://github.com/ichord/At.js
	'ext.jquery.atwho' => $moduleTemplate + [
		'scripts' => 'jquery/jquery.atwho.js',
		'styles' => 'jquery/jquery.atwho.css',
		'position' => 'top',
		'dependencies' => [
			'ext.jquery.caret'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'ext.smw.suggester' => $moduleTemplate + [
		'scripts' => 'smw/suggester/ext.smw.suggester.js',
		'position' => 'top',
		'dependencies' => [
			'ext.smw',
			'ext.jquery.atwho'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'ext.smw.suggester.textInput' => $moduleTemplate + [
		'scripts' => 'smw/suggester/ext.smw.suggester.textInput.js',
		'position' => 'top',
		'dependencies' => [
			'ext.smw',
			'ext.smw.suggester'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'ext.smw.autocomplete.page' => $moduleTemplate + [
		'scripts' => 'smw/util/ext.smw.util.autocomplete.page.js',
		'dependencies' => [
			'mediawiki.util',
			'ext.jquery.autocomplete'
		],
		'position' => 'bottom',
		'targets' => [ 'mobile', 'desktop' ]
	],

	'ext.smw.autocomplete.property' => $moduleTemplate + [
		'scripts' => [
			'smw/util/ext.smw.util.autocomplete.property.js',
			'smw/util/ext.smw.util.autocomplete.propertyvalue.js',
			'smw/util/ext.smw.util.autocomplete.propertysubject.js'
		],
		'dependencies' => [
			'mediawiki.util',
			'ext.jquery.autocomplete'
		],
		'position' => 'bottom',
		'targets' => [ 'mobile', 'desktop' ]
	],

	// Special:Ask
	'ext.smw.ask.styles' => $moduleTemplate + [
		'styles' => 'smw/special/ext.smw.special.ask.css',
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// Special:Ask
	'ext.smw.ask' => $moduleTemplate + [
		'scripts' => 'smw/special/ext.smw.special.ask.js',
		'dependencies' => [
			'ext.smw.tooltip',
			'ext.smw.style',
			'ext.smw.ask.styles',
			'ext.smw.suggester'
		],
		'messages' => [
			'smw-ask-delete',
			'smw-ask-format-selection-help',
			'smw-ask-condition-change-info',
			'smw-ask-format-change-info',
			'smw-ask-format-export-info',
			'smw-ask-format-help-link',
			'smw-section-expand',
			'smw-section-collapse'
		],
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// Table styles
	'ext.smw.table.styles' => $moduleTemplate + [
		'styles' => [
			'smw/ext.smw.table.css'
		],
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// Facts and browse
	'ext.smw.browse.styles' => $moduleTemplate + [
		'styles' => [
			'smw/ext.smw.table.css',
			'smw/special/ext.smw.special.browse.css',
			'smw/special/ext.smw.special.browse.skin.css',
		],
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'ext.smw.browse' => $moduleTemplate + [
		'scripts' => 'smw/special/ext.smw.special.browse.js',
		'dependencies' => [
			'mediawiki.api',
			'ext.smw.style'
		],
		'position' => 'top',
		'messages' => [
			'smw-browse-api-subject-serialization-invalid'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'ext.smw.browse.autocomplete' => $moduleTemplate + [
		'dependencies' => [
			'ext.smw.browse',
			'ext.smw.autocomplete.page'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// Special:Admin/SemanticMediaWiki
	'ext.smw.admin' => $moduleTemplate + [
		'scripts' => 'smw/special/ext.smw.special.admin.js',
		'dependencies' => [
			'mediawiki.api',
			'smw.jsonview'
		],
		'messages' => [
			'smw-no-data-available',
			'smw-list-count'
		],
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// Personal resource
	'ext.smw.personal' => $moduleTemplate + [
		'scripts' => 'smw/util/ext.smw.personal.js',
		'dependencies' => [
			'ext.smw.tooltip',
			'mediawiki.api'
		],
		'messages' => [
			'smw-personal-jobqueue-watchlist',
			'smw-personal-jobqueue-watchlist-explain',
			'brackets'
		],
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// TableResultPrinter resource
	'smw.tableprinter.datatable' => $moduleTemplate + [
		'scripts' => [
			'smw/printer/ext.smw.tableprinter.js'
		],
		'styles'   => [
			'smw/printer/ext.smw.tableprinter.css',
			'smw/printer/ext.smw.tableprinter.skin.css'
		],
		'dependencies' => [
			'onoi.dataTables',
			'ext.smw.query'
		],
		'position' => 'top',
		'messages' => [
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
		],
		'targets' => [ 'mobile', 'desktop' ]
	],

	'smw.tableprinter.datatable.styles' => $moduleTemplate + [
		'styles'   => [
			'smw/printer/ext.smw.tableprinter.css',
			'smw/printer/ext.smw.tableprinter.skin.css'
		],
		'position' => 'top',
		'targets' => [ 'mobile', 'desktop' ]
	],

	// Deferred
	'ext.smw.deferred.styles'  => $moduleTemplate + [
		'position' => 'top',
		'styles'   => [
			'smw/deferred/ext.smw.deferred.css',
			'smw/deferred/ext.smw.deferred.skin.css'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'ext.smw.deferred'  => $moduleTemplate + [
		'position' => 'top',
		'styles'   => [
			'smw/deferred/ext.smw.deferred.css',
			'smw/deferred/ext.smw.deferred.skin.css'
		],
		'scripts'  => [ 'smw/deferred/ext.smw.deferred.js' ],
		'dependencies'  => [
			'mediawiki.api',
			'mediawiki.api.parse',
			'onoi.rangeslider'
		],
		'messages' => [
			'smw_result_noresults'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// Page styles
	'ext.smw.page.styles' => $moduleTemplate + [
		'styles' => [
			'smw/ext.smw.page.css',
			'smw/ext.smw.table.css'
		],
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'smw.property.page'  => $moduleTemplate + [
		'position' => 'top',
		'scripts'  => [ 'smw/util/smw.property.page.js' ],
		'dependencies'  => [
			'mediawiki.api',
			'mediawiki.api.parse',
			'ext.smw.tooltip',
		],
		'messages' => [
			'smw_result_noresults'
		],
	],

	// Schema content styles
	'smw.content.schema' => $moduleTemplate + [
		'styles' => [
			'smw/content/smw.schema.css',
			'smw/ext.smw.table.css'
		],
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'smw.factbox'  => $moduleTemplate + [
		'scripts'  => [
			'libs/tinysort/tinysort.min.js',
			'smw/factbox/smw.factbox.js'
		]
	],

	'smw.content.schemaview' => $moduleTemplate + [
		'scripts' => [
			'smw/content/smw.schemaview.js'
		],
		'dependencies'  => [
			'smw.jsonview'
		],
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'smw.jsonview' => $moduleTemplate + [
		'scripts' => [
			'smw/smw.jsonview.js'
		],
		'styles' => [
			'smw/smw.jsonview.css'
		],
		'messages' => [
			'smw-expand',
			'smw-collapse',
			'smw-copy',
			'smw-copy-clipboard-title',
			'smw-jsonview-expand-title',
			'smw-jsonview-collapse-title'
		],
		'dependencies'  => [
			'jquery.jsonview',
			'ext.smw'
		],
		'position' => 'top',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'ext.libs.tippy'  => $moduleTemplate + [
		'position' => 'top',
		'styles' => [
			'libs/tippy/tippy.css',
			'libs/tippy/light-border.css',
			'libs/tippy/light.css'
		],
		'scripts'  => [
			'libs/tippy/popper.min.js',
			'libs/tippy/tippy.js'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'smw.tippy'  => $moduleTemplate + [
		'position' => 'top',
		'styles' => [
			'smw/util/smw.tippy.css'
		],
		'scripts'  => [
			'smw/util/smw.tippy.js'
		],
		'dependencies'  => [
			'ext.smw',
			'mediawiki.api',
			'ext.libs.tippy',
		],
		'messages' => [
			'smw-ui-tooltip-title-property',
			'smw-ui-tooltip-title-quantity',
			'smw-ui-tooltip-title-info',
			'smw-ui-tooltip-title-service',
			'smw-ui-tooltip-title-warning',
			'smw-ui-tooltip-title-parameter',
			'smw-ui-tooltip-title-event',
			'smw-ui-tooltip-title-error',
			'smw-ui-tooltip-title-note',
			'smw-ui-tooltip-title-legend',
			'smw-ui-tooltip-title-reference'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'smw.check.replication'  => $moduleTemplate + [
		'position' => 'top',
		'scripts'  => [
			'smw/util/smw.check.replication.js'
		],
		'dependencies'  => [
			'mediawiki.api',
			'smw.tippy',
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

];
