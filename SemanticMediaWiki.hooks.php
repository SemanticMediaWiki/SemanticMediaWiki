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
	 * Hook: Before displaying noarticletext or noarticletext-nopermission messages.
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/BeforeDisplayNoArticleText
	 *
	 * @since 1.9
	 *
	 * @param $article Article
	 *
	 * @return boolean
	 */
	public static function onBeforeDisplayNoArticleText( $article ) {

		// Avoid having "noarticletext" info being generated for predefined
		// properties as we are going to display an introductory text
		if ( $article->getTitle()->getNamespace() === SMW_NS_PROPERTY ) {
			return SMWDIProperty::newFromUserLabel( $article->getTitle()->getText() )->isUserDefined();
		}

		return true;
	}

	/**
	 * Hook: Allows overriding default behaviour for determining if a page exists.
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/TitleIsAlwaysKnown
	 *
	 * @since 1.9
	 *
	 * @param Title $title Title object that is being checked
	 * @param Boolean|null $result whether MediaWiki currently thinks this page is known
	 *
	 * @return boolean
	 */
	public static function onTitleIsAlwaysKnown( Title $title, &$result ) {

		// Two possible ways of going forward:
		//
		// The FIRST seen here is to use the hook to override the known status
		// for predefined properties in order to avoid any edit link
		// which makes no-sense for predefined properties
		//
		// The SECOND approach is to inject SMWWikiPageValue with a setLinkOptions setter
		// that enables to set the custom options 'known' for each invoked linker during
		// getShortHTMLText
		// $linker->link( $this->getTitle(), $caption, $customAttributes, $customQuery, $customOptions )
		//
		// @see also HooksTest::testOnTitleIsAlwaysKnown

		if ( $title->getNamespace() === SMW_NS_PROPERTY ) {
			if ( !SMWDIProperty::newFromUserLabel( $title->getText() )->isUserDefined() ) {
				$result = true;
			}
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
			\SMW\StoreFactory::getStore()->getParserTestTables()
		);

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
		$directoryIterator = new RecursiveDirectoryIterator( __DIR__ . '/tests/phpunit/' );

		/**
		 * @var SplFileInfo $fileInfo
		 */
		foreach ( new RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
			if ( substr( $fileInfo->getFilename(), -8 ) === 'Test.php' ) {
				$files[] = $fileInfo->getPathname();
			}
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
			'remoteExtPath' => '..' . substr( __DIR__, strlen( $GLOBALS['IP'] ) ),
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

}
