<?php

/**
 * Static class for hooks handled by the Semantic MediaWiki extension.
 *
 * @since 1.7
 *
 * @file SemanticMediaWiki.hooks.php
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class SMWHooks {

	/**
	 * Schema update to set up the needed database tables.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @since 1.7
	 *
	 * @param DatabaseUpdater $updater|null
	 *
	 * @return boolean
	 */
	public static function onSchemaUpdate( DatabaseUpdater $updater = null ) {
		$updater->addExtensionUpdate( array( 'SMWStore::setupStore' ) );

		return true;
	}

	/**
	 * TODO
	 *
	 * @since 1.7
	 *
	 * @return boolean
	 */
	public static function onPageSchemasRegistration() {
		$GLOBALS['wgPageSchemasHandlerClasses'][] = 'SMWPageSchemas';
		return true;
	}

	/**
	 * Adds links to Admin Links page.
	 *
	 * @since 1.7
	 *
	 * @param ALTree $admin_links_tree
	 *
	 * @return boolean
	 */
	public static function addToAdminLinks( ALTree &$admin_links_tree ) {
		$data_structure_section = new ALSection( wfMessage( 'smw_adminlinks_datastructure' )->text() );

		$smw_row = new ALRow( 'smw' );
		$smw_row->addItem( ALItem::newFromSpecialPage( 'Categories' ) );
		$smw_row->addItem( ALItem::newFromSpecialPage( 'Properties' ) );
		$smw_row->addItem( ALItem::newFromSpecialPage( 'UnusedProperties' ) );
		$smw_row->addItem( ALItem::newFromSpecialPage( 'SemanticStatistics' ) );

		$data_structure_section->addRow( $smw_row );
		$smw_admin_row = new ALRow( 'smw_admin' );
		$smw_admin_row->addItem( ALItem::newFromSpecialPage( 'SMWAdmin' ) );

		$data_structure_section->addRow( $smw_admin_row );
		$smw_docu_row = new ALRow( 'smw_docu' );
		$smw_name = wfMessage( 'specialpages-group-smw_group' )->text();
		$smw_docu_label = wfMessage( 'adminlinks_documentation', $smw_name )->text();
		$smw_docu_row->addItem( AlItem::newFromExternalLink( 'http://semantic-mediawiki.org/wiki/Help:User_manual', $smw_docu_label ) );

		$data_structure_section->addRow( $smw_docu_row );
		$admin_links_tree->addSection( $data_structure_section, wfMessage( 'adminlinks_browsesearch' )->text() );
		$smw_row = new ALRow( 'smw' );
		$displaying_data_section = new ALSection( wfMessage( 'smw_adminlinks_displayingdata' )->text() );
		$smw_row->addItem( AlItem::newFromExternalLink(
			'http://semantic-mediawiki.org/wiki/Help:Inline_queries',
			wfMessage( 'smw_adminlinks_inlinequerieshelp' )->text()
		) );

		$displaying_data_section->addRow( $smw_row );
		$admin_links_tree->addSection( $displaying_data_section, wfMessage( 'adminlinks_browsesearch' )->text() );
		$browse_search_section = $admin_links_tree->getSection( wfMessage( 'adminlinks_browsesearch' )->text() );

		$smw_row = new ALRow( 'smw' );
		$smw_row->addItem( ALItem::newFromSpecialPage( 'Browse' ) );
		$smw_row->addItem( ALItem::newFromSpecialPage( 'Ask' ) );
		$smw_row->addItem( ALItem::newFromSpecialPage( 'SearchByProperty' ) );
		$browse_search_section->addRow( $smw_row );

		return true;
	}


	/**
	 * Register special classes for displaying semantic content on Property and
	 * Concept pages.
	 *
	 * @since 1.7
	 *
	 * @param $title Title
	 * @param $article Article or null
	 *
	 * @return boolean
	 */
	public static function onArticleFromTitle( Title &$title, /* Article */ &$article ) {
		if ( $title->getNamespace() == SMW_NS_PROPERTY ) {
			$article = new SMWPropertyPage( $title );
		} elseif ( $title->getNamespace() == SMW_NS_CONCEPT ) {
			$article = new SMWConceptPage( $title );
		}

		return true;
	}

	/**
	 * This hook registers parser functions and hooks to the given parser. It is
	 * called during SMW initialisation. Note that parser hooks are something different
	 * than MW hooks in general, which explains the two-level registration.
	 *
	 * @since 1.7
	 *
	 * @param Parser $parser
	 *
	 * @return boolean
	 */
	public static function onParserFirstCallInit( Parser &$parser ) {
		$parser->setFunctionHook( 'ask', array( 'SMWAsk', 'render' ) );
		$parser->setFunctionHook( 'show', array( 'SMWShow', 'render' ) );
		$parser->setFunctionHook( 'subobject', array( 'SMW\SubobjectParserFunction', 'render' ) );
		$parser->setFunctionHook( 'concept', array( 'SMWConcept', 'render' ) );
		$parser->setFunctionHook( 'set', array( 'SMW\SetParserFunction', 'render' ) );
		$parser->setFunctionHook( 'set_recurring_event', array( 'SMW\RecurringEventsHandler', 'render' ) );
		$parser->setFunctionHook( 'declare', array( 'SMWDeclare', 'render' ), SFH_OBJECT_ARGS );

		return true;
	}

	/**
	 * Adds the 'Powered by Semantic MediaWiki' button right next to the default
	 * 'Powered by MediaWiki' button at the bottom of every page. This works
	 * only with MediaWiki 1.17+.
	 * It might make sense to make this configurable via a variable, if some
	 * admins don't want it.
	 *
	 * @since 1.7
	 *
	 * @param string $text
	 * @param Skin $skin
	 *
	 * @return boolean
	 */
	public static function addPoweredBySMW( &$text, $skin ) {
		global $smwgScriptPath;
		$url = htmlspecialchars( "$smwgScriptPath/resources/images/smw_button.png" );
		$text .= ' <a href="http://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki"><img src="' . $url . '" alt="Powered by Semantic MediaWiki" /></a>';

		return true;
	}

	/**
	 * Adds the 'semantic' extension type to the type list.
	 *
	 * @since 1.7.1
	 *
	 * @param $aExtensionTypes Array
	 *
	 * @return boolean
	 */
	public static function addSemanticExtensionType( array &$aExtensionTypes ) {
		$aExtensionTypes = array_merge( array( 'semantic' => wfMessage( 'version-semantic' )->text() ), $aExtensionTypes );
		return true;
	}

	/**
	 * Register tables to be added to temporary tables for parser tests.
	 *
	 * @since 1.7.1
	 *
	 * @param array $tables
	 *
	 * @return boolean
	 */
	public static function onParserTestTables( array &$tables ) {
		$tables = array_merge(
			$tables,
			smwfGetStore()->getParserTestTables()
		);

		return true;
	}

	/**
	 * Add a link to the toolbox to view the properties of the current page in
	 * Special:Browse. The links has the CSS id "t-smwbrowselink" so that it can be
	 * skinned or hidden with all standard mechanisms (also by individual users
	 * with custom CSS).
	 *
	 * @since 1.7.1
	 *
	 * @param $skintemplate
	 *
	 * @return boolean
	 */
	public static function showBrowseLink( $skintemplate ) {
		if ( $skintemplate->data['isarticle'] ) {
			$browselink = SMWInfolink::newBrowsingLink( wfMessage( 'smw_browselink' )->text(),
							$skintemplate->data['titleprefixeddbkey'], false );
			echo '<li id="t-smwbrowselink">' . $browselink->getHTML() . '</li>';
		}
		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateTabs
	 * This is here for compatibility with MediaWiki 1.17. Once we can require 1.18, we can ditch this code :)
	 *
	 * @since 0.1
	 *
	 * @param SkinTemplate $skinTemplate
	 * @param array $contentActions
	 *
	 * @return boolean
	 */
	public static function addRefreshTab( SkinTemplate $skinTemplate, array &$contentActions ) {
		global $wgUser;

		if ( $wgUser->isAllowed( 'purge' ) ) {
			$contentActions['purge'] = array(
				'class' => false,
				'text' => wfMessage( 'smw_purge' )->text(),
				'href' => $skinTemplate->getTitle()->getLocalUrl( array( 'action' => 'purge' ) )
			);
		}

		return true;
	}

	/**
	 * Alter the structured navigation links in SkinTemplates.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation
	 *
	 * @since 1.8
	 *
	 * @param SkinTemplate $skinTemplate
	 * @param array $links
	 *
	 * @return boolean
	 */
	public static function addStructuredRefreshTab( SkinTemplate &$skinTemplate, array &$links ) {
		$actions = $links['actions'];
		self::addRefreshTab( $skinTemplate, $actions );
		$links['actions'] = $actions;

		return true;
	}

	/**
	* Hook to add PHPUnit test cases.
	* @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	*
	* @since 1.8
	 *
	* @param array $files
	*
	* @return boolean
	*/
	public static function registerUnitTests( array &$files ) {
		$testFiles = array(
			'Defines',
			'GlobalFunctions',
			'FormatFactory',
			'Highlighter',
			'ObservableMessageReporter',
			'QueryProcessor',

			'dataitems/DI_Blob',
			'dataitems/DI_Bool',
			'dataitems/DI_Number',
			'dataitems/DI_GeoCoord',
			'dataitems/DISerializer',

			'export/SMWExpElement',

			'parserhooks/ParserParameterFormatter',
			'parserhooks/Subobject',
			'parserhooks/SubobjectParserFunction',
			'parserhooks/RecurringEvents',

			'printers/ResultPrinters',

			'resources/Resources',

			// Keep store tests near the end, since they are slower due to database access.
			'storage/Store',

			'storage/sqlstore/PropertyStatisticsTable',
		);

		foreach ( $testFiles as $file ) {
			$files[] = dirname( __FILE__ ) . '/tests/phpunit/includes/' . $file . 'Test.php';
		}

		return true;
	}

	/**
	 * Add new JavaScript/QUnit testing modules
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
	 *
	 * @since 1.9
	 *
	 * @param  array $testModules array of JavaScript testing modules
	 * @param  ResourceLoader $resourceLoader object
	 *
	 * @return boolean
	 */
	public static function registerQUnitTests( array &$testModules, ResourceLoader &$resourceLoader ) {
		$testModules['qunit']['ext.smw.tests'] = array(
			'scripts' => array(
				'tests/qunit/ext.smw.test.js',
				'tests/qunit/ext.smw.util.tooltip.test.js',

				// dataItem tests
				'tests/qunit/smw.data/ext.smw.dataItem.wikiPage.test.js',
				'tests/qunit/smw.data/ext.smw.dataItem.uri.test.js',
				'tests/qunit/smw.data/ext.smw.dataItem.time.test.js',
				'tests/qunit/smw.data/ext.smw.dataItem.property.test.js',
				'tests/qunit/smw.data/ext.smw.dataItem.unknown.test.js',
				'tests/qunit/smw.data/ext.smw.dataItem.number.test.js',
				'tests/qunit/smw.data/ext.smw.dataItem.text.test.js',

				// dataValues
				'tests/qunit/smw.data/ext.smw.dataValue.quantity.test.js',

				// Api / Query
				'tests/qunit/smw.data/ext.smw.data.test.js',
				'tests/qunit/smw.api/ext.smw.api.test.js',
				'tests/qunit/smw.query/ext.smw.query.test.js',
			),
			'dependencies' => array(
				'ext.smw',
				'ext.smw.tooltip',
				'ext.smw.query',
				'ext.smw.data',
				'ext.smw.api'
			),
			'position' => 'top',
			'localBasePath' => __DIR__,
			'remoteExtPath' => 'SemanticMediaWiki',
		);

		return true;
	}

	/**
	 * Hook: GetPreferences adds user preference
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 *
	 * @param User $user
	 * @param array $preferences
	 *
	 * @return true
	 */
	public static function onGetPreferences( $user, &$preferences ) {

		// Intro text
		$preferences['smw-prefs-intro'] =
			array(
				'type' => 'info',
				'label' => '&#160;',
				'default' => Xml::tags( 'tr', array(),
					Xml::tags( 'td', array( 'colspan' => 2 ),
						wfMessage(  'smw-prefs-intro-text' )->parseAsBlock() ) ),
				'section' => 'smw',
				'raw' => 1,
				'rawrow' => 1,
			);

		// Option to enable tooltip info
		$preferences['smw-prefs-ask-options-tooltip-display'] = array(
			'type' => 'toggle',
			'label-message' => 'smw-prefs-ask-options-tooltip-display',
			'section' => 'smw/ask-options',
		);

		// Preference to set option box be collapsed by default
		$preferences['smw-prefs-ask-options-collapsed-default'] = array(
			'type' => 'toggle',
			'label-message' => 'smw-prefs-ask-options-collapsed-default',
			'section' => 'smw/ask-options',
		);

		return true;
	}

	/**
	 * Hook: ResourceLoaderGetConfigVars called right before
	 * ResourceLoaderStartUpModule::getConfig returns
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderGetConfigVars
	 *
	 * @since  1.9
	 *
	 * @param &$vars Array of variables to be added into the output of the startup module.
	 *
	 * @return true
	 */
	public static function onResourceLoaderGetConfigVars( &$vars ) {
		$vars['smw'] = array(
			'version' => SMW_VERSION,
			'options' => array(
				'QMaxLimit' => $GLOBALS['smwgQMaxLimit'],
				'QMaxInlineLimit' => $GLOBALS['smwgQMaxInlineLimit'],
			)
		);

		return true;
	}

	/**
	* Add extra statistic at the end of Special:Statistics.
	* @see http://www.mediawiki.org/wiki/Manual:Hooks/SpecialStatsAddExtra
	*
	* @since 1.9
	*
	* @param $extraStats
	 *
	* @return boolean
	*/
	public static function onSpecialStatsAddExtra( array &$extraStats ) {
		global $wgVersion, $wgLang;

		$semanticStatistics = smwfGetStore()->getStatistics();

		if ( version_compare( $wgVersion, '1.21', '<' ) ) {
			// Legacy approach to display statistical items
			$extraStats[wfMessage( 'smw-statistics-property-instance' )->text()] = $wgLang->formatNum( $semanticStatistics['PROPUSES'] );
			$extraStats[wfMessage( 'smw-statistics-property-total-legacy' )->text()] = $wgLang->formatNum( $semanticStatistics['USEDPROPS'] );
			$extraStats[wfMessage( 'smw-statistics-property-page' )->text()] = $wgLang->formatNum( $semanticStatistics['OWNPAGE'] );
			$extraStats[wfMessage( 'smw-statistics-property-type' )->text()] = $wgLang->formatNum( $semanticStatistics['DECLPROPS'] );
			$extraStats[wfMessage( 'smw-statistics-query-inline' )->text()]  = $wgLang->formatNum( $semanticStatistics['QUERY'] );
		} else {
			$extraStats['smw-statistics'] = array();
			$extraStats['smw-statistics']['smw-statistics-property-instance'] = $semanticStatistics['PROPUSES'];
			$extraStats['smw-statistics']['smw-statistics-property-total'] = $semanticStatistics['USEDPROPS'];
			$extraStats['smw-statistics']['smw-statistics-property-page'] = $semanticStatistics['OWNPAGE'];
			$extraStats['smw-statistics']['smw-statistics-property-type'] = $semanticStatistics['DECLPROPS'];
			$extraStats['smw-statistics']['smw-statistics-query-inline'] = $semanticStatistics['QUERY'];
		}

		return true;
	}
}
