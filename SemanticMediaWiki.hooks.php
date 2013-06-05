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
 * @author mwjames
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
		// @codeCoverageIgnoreStart
		$updater->addExtensionUpdate( array( 'SMWStore::setupStore' ) );

		return true;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * TODO
	 *
	 * @since 1.7
	 *
	 * @return boolean
	 */
	public static function onPageSchemasRegistration() {
		// @codeCoverageIgnoreStart
		$GLOBALS['wgPageSchemasHandlerClasses'][] = 'SMWPageSchemas';

		return true;
		// @codeCoverageIgnoreEnd
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
		// @codeCoverageIgnoreStart
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
		// @codeCoverageIgnoreEnd
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
			$article = new SMW\ConceptPage( $title );
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
		$parser->setFunctionHook( 'ask', array( 'SMW\AskParserFunction', 'render' ) );
		$parser->setFunctionHook( 'show', array( 'SMW\ShowParserFunction', 'render' ) );
		$parser->setFunctionHook( 'subobject', array( 'SMW\SubobjectParserFunction', 'render' ) );
		$parser->setFunctionHook( 'concept', array( 'SMW\ConceptParserFunction', 'render' ) );
		$parser->setFunctionHook( 'set', array( 'SMW\SetParserFunction', 'render' ) );
		$parser->setFunctionHook( 'set_recurring_event', array( 'SMW\RecurringEventsParserFunction', 'render' ) );
		$parser->setFunctionHook( 'declare', array( 'SMW\DeclareParserFunction', 'render' ), SFH_OBJECT_ARGS );

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
		// @codeCoverageIgnoreStart
		$aExtensionTypes = array_merge( array( 'semantic' => wfMessage( 'version-semantic' )->text() ), $aExtensionTypes );
		return true;
		// @codeCoverageIgnoreEnd
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
		// @codeCoverageIgnoreStart
		$tables = array_merge(
			$tables,
			smwfGetStore()->getParserTestTables()
		);

		return true;
		// @codeCoverageIgnoreEnd
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
		// @codeCoverageIgnoreStart
		if ( $skintemplate->data['isarticle'] ) {
			$browselink = SMWInfolink::newBrowsingLink( wfMessage( 'smw_browselink' )->text(),
							$skintemplate->data['titleprefixeddbkey'], false );
			echo '<li id="t-smwbrowselink">' . $browselink->getHTML() . '</li>';
		}

		return true;
		// @codeCoverageIgnoreEnd
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
	public static function onSkinTemplateNavigation( SkinTemplate &$skinTemplate, array &$links ) {
		// @codeCoverageIgnoreStart
		if ( $skinTemplate->getUser()->isAllowed( 'purge' ) ) {
			$links['actions']['purge'] = array(
				'class' => false,
				'text' => $skinTemplate->msg( 'smw_purge' )->text(),
				'href' => $skinTemplate->getTitle()->getLocalUrl( array( 'action' => 'purge' ) )
			);
		}

		return true;
		// @codeCoverageIgnoreEnd
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
			'ParserData',
			'Subobject',
			'RecurringEvents',
			'Infolink',
			'Hooks',
			'DataValueFactory',
			'Settings',
			'ParserTextProcessor',
			'Factbox',

			'formatters/ParserParameterFormatter',

			'handlers/CacheHandler',

			'api/ApiSMWInfo',
			'api/ApiAsk',

			'query/QueryProcessor',

			'dataitems/DI_Blob',
			'dataitems/DI_Bool',
			'dataitems/DI_Number',
			'dataitems/DI_GeoCoord',
			'dataitems/DISerializer',
			'dataitems/DIConcept',

			'export/SMWExpElement',

			'parserhooks/SubobjectParserFunction',
			'parserhooks/RecurringEventsParserFunction',
			'parserhooks/AskParserFunction',
			'parserhooks/ShowParserFunction',
			'parserhooks/ConceptParserFunction',
			'parserhooks/DeclareParserFunction',
			'parserhooks/DocumentationParserFunction',
			'parserhooks/InfoParserFunction',
			'parserhooks/SetParserFunction',

			'query/QueryData',

			'printers/ResultPrinters',
			'printers/AggregatablePrinter',

			'resources/Resources',

			'specials/Specials',
			'specials/WantedPropertiesPage',

			// Keep store tests near the end, since they are slower due to database access.
			'storage/StoreFactory',
			'storage/Store',

			'storage/sqlstore/PropertyStatisticsTable',
			'storage/sqlstore/DIHandlerWikiPage'
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
				'tests/qunit/smw/ext.smw.test.js',
				'tests/qunit/smw/util/ext.smw.util.tooltip.test.js',

				// dataItem tests
				'tests/qunit/smw/data/ext.smw.dataItem.wikiPage.test.js',
				'tests/qunit/smw/data/ext.smw.dataItem.uri.test.js',
				'tests/qunit/smw/data/ext.smw.dataItem.time.test.js',
				'tests/qunit/smw/data/ext.smw.dataItem.property.test.js',
				'tests/qunit/smw/data/ext.smw.dataItem.unknown.test.js',
				'tests/qunit/smw/data/ext.smw.dataItem.number.test.js',
				'tests/qunit/smw/data/ext.smw.dataItem.text.test.js',

				// dataValues
				'tests/qunit/smw/data/ext.smw.dataValue.quantity.test.js',

				// Api / Query
				'tests/qunit/smw/data/ext.smw.data.test.js',
				'tests/qunit/smw/api/ext.smw.api.test.js',
				'tests/qunit/smw/query/ext.smw.query.test.js',
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
	 * ResourceLoaderStartUpModule::getConfig and exports static configuration
	 * variables to JavaScript. Things that depend on the current
	 * page/request state should use MakeGlobalVariablesScript instead
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
		$vars['smw-config'] = array(
			'version' => SMW_VERSION,
			'settings' => array(
				'smwgQMaxLimit' => $GLOBALS['smwgQMaxLimit'],
				'smwgQMaxInlineLimit' => $GLOBALS['smwgQMaxInlineLimit'],
			)
		);

		// Available semantic namespaces
		foreach ( array_keys( $GLOBALS['smwgNamespacesWithSemanticLinks'] ) as $ns ) {
			$name = MWNamespace::getCanonicalName( $ns );
			$vars['smw-config']['settings']['namespace'][$name] = $ns;
		}

		foreach ( array_keys( $GLOBALS['smwgResultFormats'] ) as $format ) {
			// Special formats "count" and "debug" currently not supported.
			if ( $format != 'broadtable' && $format != 'count' && $format != 'debug' ) {
				$printer = SMWQueryProcessor::getResultPrinter( $format, SMWQueryProcessor::SPECIAL_PAGE );
				$vars['smw-config']['formats'][$format] = $printer->getName();
			}
		}

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
			$extraStats[wfMessage( 'smw-statistics-subobject-count' )->text()]  = $wgLang->formatNum( $semanticStatistics['SUBOBJECTS'] );
			$extraStats[wfMessage( 'smw-statistics-query-inline' )->text()]  = $wgLang->formatNum( $semanticStatistics['QUERY'] );
			$extraStats[wfMessage( 'smw-statistics-concept-count' )->text()]  = $wgLang->formatNum( $semanticStatistics['CONCEPTS'] );
		} else {
			$extraStats['smw-statistics'] = array();
			$extraStats['smw-statistics']['smw-statistics-property-instance'] = $semanticStatistics['PROPUSES'];
			$extraStats['smw-statistics']['smw-statistics-property-total'] = $semanticStatistics['USEDPROPS'];
			$extraStats['smw-statistics']['smw-statistics-property-page'] = $semanticStatistics['OWNPAGE'];
			$extraStats['smw-statistics']['smw-statistics-property-type'] = $semanticStatistics['DECLPROPS'];
			$extraStats['smw-statistics']['smw-statistics-subobject-count'] = $semanticStatistics['SUBOBJECTS'];
			$extraStats['smw-statistics']['smw-statistics-datatype-count'] = count( SMWDataValueFactory::getKnownTypeLabels() );
			$extraStats['smw-statistics']['smw-statistics-query-inline'] = $semanticStatistics['QUERY'];
			$extraStats['smw-statistics']['smw-statistics-concept-count'] = $semanticStatistics['CONCEPTS'];
		}

		return true;
	}

	/**
	 * Hook: ParserAfterTidy to add some final processing to the
	 * fully-rendered page output
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
	 *
	 * @since  1.9
	 *
	 * @param $parser Parser object
	 * @param $text Represents the text for page
	 *
	 * @return true
	 */
	public static function onParserAfterTidy( &$parser, &$text ) {
		$cache  = \SMW\CacheHandler::newFromId()
			->key( 'autorefresh', $parser->getTitle()->getArticleID() );

		// Separate globals from local state
		// FIXME Do a new SMW\Settings( $GLOBALS );
		$options = array(
			'smwgUseCategoryHierarchy' => $GLOBALS['smwgUseCategoryHierarchy'],
			'smwgCategoriesAsInstances' => $GLOBALS['smwgCategoriesAsInstances'],
		);

		$parserData = new SMW\ParserData( $parser->getTitle(), $parser->getOutput(), $options );
		$parserData->addCategories( $parser->getOutput()->getCategoryLinks() );
		$parserData->addDefaultSort( $parser->getDefaultSort() );
		$parserData->updateOutput();

		// If an article was was manually purged/moved ensure that the store is
		// updated as well for all other cases onLinksUpdateConstructed will
		// initiate the store update
		if( $cache->get() ) {
			$parserData->updateStore();
		}
		$cache->delete();

		return true;
	}

	/**
	 * Hook: LinksUpdateConstructed called at the end of LinksUpdate() construction
	 *
	 * Hook where the storage of data is triggered. This happens when
	 * saving an article but possibly also when running update jobs.
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateConstructed
	 *
	 * @since  1.9
	 *
	 * @param $linksUpdate the LinksUpdate object
	 *
	 * @return true
	 */
	public static function onLinksUpdateConstructed( $linksUpdate ) {
		$parserData = new SMW\ParserData( $linksUpdate->getTitle(), $linksUpdate->getParserOutput() );
		$parserData->updateStore();

		return true;
	}

	/**
	 * Hook: ArticleDelete occurs whenever the software receives a request
	 * to delete an article
	 *
	 * This method will be called whenever an article is deleted so that
	 * semantic properties are cleared appropriately.
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ArticleDelete
	 *
	 * @since  1.9
	 *
	 * @param WikiPage $article the article/WikiPage that was delete
	 * @param User $user the user (object) deleting the article
	 * @param $reason the reason (string) the article is being deleted
	 * @param $error if the requested article deletion was prohibited
	 *
	 * @return true
	 */
	public static function onArticleDelete( &$wikiPage, &$user, &$reason, &$error ) {
		smwfGetStore()->deleteSubject( $wikiPage->getTitle() );

		return true;
	}

	/**
	 * Hook: ArticlePurge executes before running "&action=purge"
	 *
	 * @note Temporary store the article in the main cache (static variable
	 * didn't work) in order for onParserAfterTidy to identify which article
	 * was manually purged and update the store accordingly
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ArticlePurge
	 *
	 * @since  1.9
	 *
	 * @param WikiPage $wikiPage article being purged
	 *
	 * @return true
	 */
	public static function onArticlePurge( &$wikiPage ) {
		\SMW\CacheHandler::newFromId()
			->key( 'autorefresh', $wikiPage->getTitle()->getArticleID() )
			->set( $GLOBALS['smwgAutoRefreshOnPurge'] );

		return true;
	}

	/**
	 * Hook: TitleMoveComplete occurs whenever a request to move an article
	 * is completed
	 *
	 * This method will be called whenever an article is moved so that
	 * semantic properties are moved accordingly.
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
	 *
	 * @since  1.9
	 *
	 * @param Title $oldTitle old title
	 * @param Title $newTitle: new title
	 * @param Use $user user who did the move
	 * @param $oldId database ID of the page that's been moved
	 * @param $newId database ID of the created redirect
	 *
	 * @return true
	 */
	public static function onTitleMoveComplete( &$oldTitle, &$newTitle, &$user, $oldId, $newId ) {
		\SMW\CacheHandler::newFromId()
			->key( 'autorefresh', $newTitle->getArticleID() )
			->set( $GLOBALS['smwgAutoRefreshOnPageMove'] );

		smwfGetStore()->changeTitle( $oldTitle, $newTitle, $oldId, $newId );

		return true;
	}

	/**
	 * Hook: NewRevisionFromEditComplete called when a revision was inserted
	 * due to an edit
	 *
	 * Fetch additional information that is related to the saving that has just happened,
	 * e.g. regarding the last edit date. In runs where this hook is not triggered, the
	 * last DB entry (of MW) will be used to fill such properties.
	 *
	 * Called from LocalFile.php, SpecialImport.php, Article.php, Title.php
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/NewRevisionFromEditComplete
	 *
	 * @since  1.9
	 *
	 * @param WikiPage $article the article edited
	 * @param Revision $rev the new revision. Revision object
	 * @param $baseId the revision ID this was based off, if any
	 * @param User $user the revision author. User object
	 *
	 * @return true
	 */
	public static function onNewRevisionFromEditComplete( $wikiPage, $revision, $baseId, $user ) {
		$parserOutput = $wikiPage->getParserOutput(
			$wikiPage->makeParserOptions( $user ),
			$revision->getId()
		);

		if ( !( $parserOutput instanceof ParserOutput ) ) {
			return true;
		}

		// FIXME Do a new SMW\Settings( $GLOBALS );
		$options = array(
			'smwgPageSpecialProperties' => $GLOBALS['smwgPageSpecialProperties']
		);

		$parserData = new SMW\ParserData( $wikiPage->getTitle(), $parserOutput, $options );
		$parserData->addSpecialProperties( $wikiPage, $revision, $user );

		return true;
	}

	/**
	 * Hook: InternalParseBeforeLinks is used to process the expanded wiki
	 * code after <nowiki>, HTML-comments, and templates have been treated.
	 *
	 * This method will be called before an article is displayed or previewed.
	 * For display and preview we strip out the semantic properties and append them
	 * at the end of the article.
	 *
	 * @note MW 1.20+ see InternalParseBeforeSanitize
	 *
	 * @see Parser
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/InternalParseBeforeLinks
	 *
	 * @since  1.9
	 *
	 * @param Parser $parser
	 * @param string $text
	 *
	 * @return true
	 */
	public static function onInternalParseBeforeLinks( Parser &$parser, &$text ) {

		$settings = SMW\Settings::newFromArray( array(
			'smwgNamespacesWithSemanticLinks' => $GLOBALS['smwgNamespacesWithSemanticLinks'],
			'smwgLinksInValues' => $GLOBALS['smwgLinksInValues'],
			'smwgInlineErrors'  => $GLOBALS['smwgInlineErrors']
		) );

		$processor = new SMW\ParserTextProcessor(
			new SMW\ParserData( $parser->getTitle(), $parser->getOutput() ),
			$settings
		);
		$processor->parse( $text );

		return true;
	}

	/**
	 * Hook: Add changes to the output page, e.g. adding of CSS or JavaScript
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 *
	 * @since 1.9
	 *
	 * @param OutputPage $outputPage
	 * @param Skin $skin
	 *
	 * @return boolean
	 */
	public static function onBeforePageDisplay( OutputPage &$outputPage, Skin &$skin ) {
		$title = $outputPage->getTitle();

		// Add style resources to avoid unstyled content
		$outputPage->addModules( array( 'ext.smw.style' ) );

		// Add export link to the head
		if ( $title instanceof Title && !$title->isSpecialPage() ) {
			$linkarr['rel'] = 'ExportRDF';
			$linkarr['type'] = 'application/rdf+xml';
			$linkarr['title'] = $title->getPrefixedText();
			$linkarr['href'] = SpecialPage::getTitleFor( 'ExportRDF', $title->getPrefixedText() )->getLocalUrl( 'xmlmime=rdf' );
			$outputPage->addLink( $linkarr );
		}

		return true;
	}
}
