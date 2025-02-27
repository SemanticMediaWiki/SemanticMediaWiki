<?php

/**
 * Collection of resource module definitions
 *
 * @license GNU GPL v2
 * @since 1.8
 *
 * @author mwjames
 */

// #1466 (Make sure to work on both Win and Ux)
$pathParts = explode( '/', str_replace( DIRECTORY_SEPARATOR, '/', __DIR__ ) );

$moduleTemplate = [
	'localBasePath' => __DIR__,
	'remoteExtPath' => implode( '/', array_slice( $pathParts, -2 ) )
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
	'ext.smw.styles' => $moduleTemplate + [
		'styles' => [
			'smw/ext.smw.css',
			'smw/ext.smw.dropdown.css',
			'smw/ext.smw.table.css',
			'smw/ext.smw.tabs.less',
			'smw/smw.indicators.css',
			'smw/smw.jsonview.css'
		],
		'skinStyles' => [
			'chameleon' => [ 'smw/ext.smw.skin-chameleon.css' ],
			'foreground' => [ 'smw/ext.smw.skin-foreground.css' ],
			'vector' => [ 'smw/ext.smw.skin-vector.css' ]
		],
		'targets' => [ 'mobile', 'desktop' ]
	],

	// https://github.com/TerryZ/SelectMenu
	'smw.ui' => $moduleTemplate + [
		'scripts' => [
			'jquery/jquery.selectmenu.js',
			'smw/smw.ui.js'
		],
		'dependencies' => [
			'ext.smw',
			'smw.ui.styles'
		],
		'targets' => [ 'mobile', 'desktop' ]
	],

	'smw.ui.styles' => $moduleTemplate + [
		'styles' => [
			'jquery/jquery.selectmenu.css',
			'smw/smw.selectmenu.css'
		],
		'targets' => [ 'mobile', 'desktop' ]
	],

	'smw.summarytable' => $moduleTemplate + [
		'styles' => [
			'smw/smw.summarytable.css'
		],
		'targets' => [ 'mobile', 'desktop' ]
	],

	'ext.smw.special.styles' => $moduleTemplate + [
		'styles' => [
			'smw/special/ext.smw.special.less',
			'smw/special/ext.smw.special.preferences.css'
		],
		'targets' => [ 'mobile', 'desktop' ]
	],

	// Load the module explicitly, otherwise mobile will complain with
	// "Uncaught Error: Unknown dependency: jquery.async"
	'ext.jquery.async' => $moduleTemplate + [
		'scripts' => 'jquery/jquery.async.js',
		'targets' => [ 'mobile', 'desktop' ]
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
		'scripts' => [
			'smw/data/ext.smw.dataItem.wikiPage.js',
			'smw/data/ext.smw.dataItem.uri.js',
			'smw/data/ext.smw.dataItem.time.js',
			'smw/data/ext.smw.dataItem.property.js',
			'smw/data/ext.smw.dataItem.unknown.js',
			'smw/data/ext.smw.dataItem.number.js',
			'smw/data/ext.smw.dataItem.text.js',
			'smw/data/ext.smw.dataItem.geo.js',
			'smw/data/ext.smw.dataValue.quantity.js',
			'smw/data/ext.smw.data.js',
			'smw/api/ext.smw.api.js'
		],
		'dependencies' => [
			'mediawiki.Title',
			'mediawiki.storage',
			'mediawiki.util',
			'ext.smw',
			'ext.smw.query'
		]
	],

	// https://github.com/devbridge/jQuery-Autocomplete
	'ext.jquery.autocomplete' => $moduleTemplate + [
		'scripts' => 'jquery/jquery.autocomplete.js',
		'targets' => [ 'mobile', 'desktop' ]
	],

	// Critical CSS for ext.smw.tooltip
	'ext.smw.tooltip.styles' => $moduleTemplate + [
		'styles' => [
			'smw/util/ext.smw.tooltip.less'
		],
		'position' => 'top',
		'targets' => [ 'mobile', 'desktop' ]
	],

	'ext.smw.tooltip'  => $moduleTemplate + [
		'position' => 'top',
		'styles' => [
			'libs/tippy/light-border.css',
			'libs/tippy/scale.css',
			'smw/util/ext.smw.tooltip.tippy.less'
		],
		'scripts'  => [
			'libs/tippy/popper.min.js',
			'libs/tippy/tippy.min.js',
			'smw/util/ext.smw.tooltip.tippy.js'
		],
		'dependencies'  => [
			'ext.smw',
			'ext.smw.tooltip.styles',
			'mediawiki.api'
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
		'dependencies' => [
			'mediawiki.api'
		],
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
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// Special:Search
	'smw.special.search.styles' => $moduleTemplate + [
		'styles' => 'smw/special.search/search.css',
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
		'dependencies' => [
			'ext.smw.styles',
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
		'messages' => [
			'smw-postproc-queryref'
		],
		'dependencies' => [
			'mediawiki.api'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// https://github.com/ichord/Caret.js
	// https://github.com/ichord/At.js
	'ext.smw.suggester' => $moduleTemplate + [
		'scripts' => [
			'jquery/jquery.caret.js',
			'jquery/jquery.atwho.js',
			'smw/suggester/ext.smw.suggester.js'
		],
		'styles' => [
			'jquery/jquery.atwho.css'
		],
		'dependencies' => [
			'ext.smw'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'ext.smw.suggester.textInput' => $moduleTemplate + [
		'scripts' => 'smw/suggester/ext.smw.suggester.textInput.js',
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
		'targets' => [ 'mobile', 'desktop' ]
	],

	// Special:Ask
	'ext.smw.ask.styles' => $moduleTemplate + [
		'styles' => 'smw/special/ext.smw.special.ask.less',
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
			'ext.smw.styles',
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
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// Factbox styles
	'ext.smw.factbox.styles' => $moduleTemplate + [
		'styles' => [
			'smw/factbox.less'
		],
		'skinStyles' => [
			'vector-2022' => [ 'smw/factbox-vector-2022.less' ]
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// Load sortable script for attachment table
	'ext.smw.factbox' => $moduleTemplate + [
		'packagedFiles' => [
			'smw/ext.smw.factbox.js'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// Special:Browse
	'ext.smw.browse.styles' => $moduleTemplate + [
		'styles' => [
			'smw/special/ext.smw.special.browse.less'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'ext.smw.browse' => $moduleTemplate + [
		'scripts' => 'smw/special/ext.smw.special.browse.js',
		'dependencies' => [
			'mediawiki.api',
			'ext.smw.styles'
		],
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
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// Special:FactedSearch
	'smw.special.facetedsearch.styles' => $moduleTemplate + [
		'styles' => 'smw/special/smw.special.facetedsearch.css',
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'smw.special.facetedsearch' => $moduleTemplate + [
		'scripts' => 'smw/special/smw.special.facetedsearch.js',
		'dependencies' => [
			'smw.special.facetedsearch.styles',
			'onoi.rangeslider',
			// 'vue'
		],
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
			'smw/printer/ext.smw.tableprinter.css'
		],
		'skinStyles' => [
			'chameleon' => [ 'smw/printer/ext.smw.tableprinter.skin-chameleon.css' ]
		],
		'dependencies' => [
			'onoi.dataTables',
			'ext.smw.query'
		],
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
			'smw/printer/ext.smw.tableprinter.css'
		],
		'skinStyles' => [
			'chameleon' => [ 'smw/printer/ext.smw.tableprinter.skin-chameleon.css' ]
		],
		'targets' => [ 'mobile', 'desktop' ]
	],

	// Deferred
	'ext.smw.deferred.styles'  => $moduleTemplate + [
		'styles'   => [
			'smw/deferred/ext.smw.deferred.css'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'ext.smw.deferred'  => $moduleTemplate + [
		'styles'   => [
			'smw/deferred/ext.smw.deferred.css'
		],
		'scripts'  => [ 'smw/deferred/ext.smw.deferred.js' ],
		'dependencies'  => [
			'mediawiki.api',
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
			'smw/ext.smw.table.css',
			'smw/smw.jsonview.css'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'smw.property.page'  => $moduleTemplate + [
		'scripts'  => [ 'smw/util/smw.property.page.js' ],
		'dependencies'  => [
			'mediawiki.api',
			'ext.smw.tooltip',
			'smw.jsonview'
		],
		'messages' => [
			'smw_result_noresults'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	// Schema content styles
	'smw.content.schema' => $moduleTemplate + [
		'styles' => [
			'smw/content/smw.schema.less',
			'smw/ext.smw.table.css'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'smw.content.schemaview' => $moduleTemplate + [
		'scripts' => [
			'smw/content/smw.schemaview.js'
		],
		'dependencies'  => [
			'smw.jsonview'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'smw.jsonview.styles' => $moduleTemplate + [
		'styles' => [
			'smw/smw.jsonview.css'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'smw.jsonview' => $moduleTemplate + [
		'scripts' => [
			'jquery/jquery.jsonview.js',
			'jquery/jquery.mark.js',
			'smw/smw.jsonview.js'
		],
		'styles' => [
			'jquery/jquery.jsonview.css',
			'smw/smw.jsonview.css'
		],
		'messages' => [
			'smw-expand',
			'smw-collapse',
			'smw-copy',
			'smw-copy-clipboard-title',
			'smw-jsonview-expand-title',
			'smw-jsonview-collapse-title',
			'smw-jsonview-search-label'
		],
		'dependencies'  => [
			'ext.smw'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'smw.entityexaminer'  => $moduleTemplate + [
		'styles' => [
			'smw/util/smw.entityexaminer.css'
		],
		'scripts'  => [
			'smw/util/smw.entityexaminer.js'
		],
		'dependencies'  => [
			'ext.smw',
			'mediawiki.api'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'onoi.rangeslider' => $moduleTemplate + [
		'styles' => [
			'onoi/jquery.rangeSlider/ion.rangeSlider.css',
			'onoi/jquery.rangeSlider/ion.rangeSlider.skinFlat.css'
		],
		'scripts' => [
			'onoi/jquery.rangeSlider/ion.rangeSlider.js'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'onoi.blobstore' => $moduleTemplate + [
		'scripts' => [
			'onoi/localForage/localforage.min.js',
			'onoi/onoi.blobstore.js'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'onoi.clipboard' => $moduleTemplate + [
		'scripts' => [
			'onoi/clipboard/clipboard.js',
			'onoi/onoi.clipboard.js'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],

	'onoi.dataTables' => $moduleTemplate + [
		'styles' => [
			'onoi/jquery.dataTables/dataTables.searchHighlight.css',
			'onoi/jquery.dataTables/datatables.min.css'
		],
		'scripts' => [
			'onoi/jquery.highlight/jquery.highlight.js',
			'onoi/jquery.dataTables/dataTables.searchHighlight.js',
			'onoi/jquery.dataTables/datatables.min.js',
			'onoi/jquery.dataTables/dataTables.search.js'
		],
		'targets' => [
			'mobile',
			'desktop'
		]
	],
];
