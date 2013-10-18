<?php

namespace SMW;

/**
 * Extension setup and registration
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Extension setup and registration
 *
 * The main things this function does are: register all hooks, set up extension
 * credits etc.
 *
 * @ingroup SMW
 */
final class Setup implements ContextAware {

	/** @var array */
	protected $globals;

	/** @var Settings */
	protected $settings;

	/** @var ContextResource */
	protected $context = null;

	/**
	 * @since 1.9
	 *
	 * @param array &$globals
	 * @param ContextResource|null $context
	 */
	public function __construct( &$globals, ContextResource $context = null ) {
		$this->globals =& $globals;
		$this->context = $context;
	}

	/**
	 * Initialisation of the extension
	 *
	 * @since 1.9
	 */
	public function run() {
		Profiler::In();

		$this->init();

		$this->loadSettings();

		// Register messages files
		$this->registerMessageFiles();

		// Register Api modules
		$this->registerApiModules();

		// Register Job classes
		$this->registerJobClasses();

		// Register Special pages
		$this->registerSpecialPages();

		// Rights and groups
		$this->registerRights();

		// ParamDefinitions
		$this->registerParamDefinitions();

		//FooterIcons
		$this->registerFooterIcon();

		// Register hooks (needs to be loaded after settings are initialized)
		$this->registerFunctionHooks();

		// Register parser hooks
		$this->registerParserHooks();

		Profiler::Out();
	}

	/**
	 * Init some globals that are not part of the configuration settings
	 *
	 * @since 1.9
	 */
	protected function init() {

		$this->globals['smwgMasterStore'] = null;
		$this->globals['smwgIQRunningNumber'] = 0;

	}

	/**
	 * @see ContextAware::withContext
	 *
	 * @since 1.9
	 *
	 * @return ContextResource
	 */
	public function withContext() {

		if ( $this->context === null ) {
			$this->context = new BaseContext();
		}

		return $this->context;
	}

	/**
	 * Load Semantic MediaWiki specific settings
	 *
	 * @since 1.9
	 */
	protected function loadSettings() {
		$this->settings = $this->registerSettings( Settings::newFromGlobals( $this->globals ) );
	}

	/**
	 * Register settings
	 *
	 * @since 1.9
	 */
	protected function registerSettings( Settings $settings ) {
		$this->withContext()->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $settings );
		return $settings;
	}

	/**
	 * Register messages files
	 *
	 * @since 1.9
	 */
	protected function registerMessageFiles() {

		$smwgIP = $this->settings->get( 'smwgIP' );

		$this->globals['wgExtensionMessagesFiles']['SemanticMediaWiki'] = $smwgIP . 'languages/SMW_Messages.php';
		$this->globals['wgExtensionMessagesFiles']['SemanticMediaWikiAlias'] = $smwgIP . 'languages/SMW_Aliases.php';
		$this->globals['wgExtensionMessagesFiles']['SemanticMediaWikiMagic'] = $smwgIP . 'languages/SMW_Magic.php';

	}

	/**
	 * Register Api modules
	 *
	 * @note Associative array mapping module name to class name
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:$wgAPIModules
	 *
	 * @since 1.9
	 */
	protected function registerApiModules() {

		$this->globals['wgAPIModules']['smwinfo'] = '\SMW\ApiInfo';
		$this->globals['wgAPIModules']['ask']     = '\SMW\ApiAsk';
		$this->globals['wgAPIModules']['askargs'] = '\SMW\ApiAskArgs';
		$this->globals['wgAPIModules']['browse']  = '\SMW\ApiBrowse';

	}

	/**
	 * Register Job classes to their handling classes
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:$wgJobClasses
	 *
	 * @since 1.9
	 */
	protected function registerJobClasses() {

		$this->globals['wgJobClasses']['SMW\UpdateJob']           = 'SMW\UpdateJob';
		$this->globals['wgJobClasses']['SMWRefreshJob']           = 'SMWRefreshJob';
		$this->globals['wgJobClasses']['SMW\UpdateDispatcherJob'] = 'SMW\UpdateDispatcherJob';

	}

	/**
	 * Register rights and groups
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:$wgAvailableRights
	 * @see https://www.mediawiki.org/wiki/Manual:$wgGroupPermissions
	 *
	 * @since 1.9
	 */
	protected function registerRights() {

		// Rights
		$this->globals['wgAvailableRights'][] = 'smw-admin';

		// User group rights
		$this->globals['wgGroupPermissions']['sysop']['smw-admin'] = true;
		$this->globals['wgGroupPermissions']['smwadministrator']['smw-admin'] = true;

	}

	/**
	 * Register special pages
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:$wgSpecialPages
	 *
	 * @since 1.9
	 */
	protected function registerSpecialPages() {

		$specials = array(
			'Ask' => array(
				'page' => 'SMWAskPage',
				'group' => 'smw_group'
			),
			'Browse' => array(
				'page' =>  'SMWSpecialBrowse',
				'group' => 'smw_group'
			),
			'PageProperty' => array(
				'page' =>  'SMWPageProperty',
				'group' => 'smw_group'
			),
			'SearchByProperty' => array(
				'page' => 'SMWSearchByProperty',
				'group' => 'smw_group'
			),
			'SMWAdmin' => array(
				'page' => 'SMWAdmin',
				'group' => 'smw_group'
			),
			'SemanticStatistics' => array(
				'page' => 'SMW\SpecialSemanticStatistics',
				'group' => 'wiki'
			),
			'Concepts' => array(
				'page' => 'SMW\SpecialConcepts',
				'group' => 'pages'
			),
			'ExportRDF' => array(
				'page' => 'SMWSpecialOWLExport',
				'group' => 'smw_group'
			),
			'Types' => array(
				'page' => 'SMWSpecialTypes',
				'group' => 'pages'
			),
			'URIResolver' => array(
				'page' => 'SMWURIResolver'
			),
			'Properties' => array(
				'page' => 'SMW\SpecialProperties',
				'group' => 'pages'
			),
			'UnusedProperties' => array(
				'page' => 'SMW\SpecialUnusedProperties',
				'group' => 'maintenance'
			),
			'WantedProperties' => array(
				'page' => 'SMW\SpecialWantedProperties',
				'group' => 'maintenance'
			),
		);

		// Register data
		foreach ( $specials as $special => $page ) {
			$this->globals['wgSpecialPages'][$special] = $page['page'];

			if ( isset( $page['group'] ) ) {
				$this->globals['wgSpecialPageGroups'][$special] = $page['group'];
			}
		}

	}

	/**
	 * Register
	 *
	 * @since 1.9
	 */
	protected function registerParamDefinitions() {

		$this->globals['wgParamDefinitions']['smwformat'] = array(
			'definition'=> 'SMWParamFormat',
		);

		$this->globals['wgParamDefinitions']['smwsource'] = array(
			'definition' => 'SMWParamSource',
		);

	}

	/**
	 * Register poweredby footer icon
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:$wgFooterIcons
	 *
	 * @since 1.9
	 */
	protected function registerFooterIcon() {

		$this->globals['wgFooterIcons']['poweredby']['semanticmediawiki'] = array(
			'src' => $this->settings->get( 'smwgScriptPath' ) . '/resources/images/smw_button.png',
			'url' => 'https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki',
			'alt' => 'Powered by Semantic MediaWiki',
		);
	}

	/**
	 * Register function hooks
	 *
	 * @note $this->globals['wgHooks'] contains a list of hooks which specifies for every event an
	 * array of functions to be called.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:$this->globals['wgHooks']
	 *
	 * @since 1.9
	 */
	protected function registerFunctionHooks() {

		$hookRegistry = $this->withContext()->getDependencyBuilder()->newObject( 'FunctionHookRegistry' );

		/**
		 * Hook: Called by BaseTemplate when building the toolbox array and
		 * returning it for the skin to output.
		 *
		 * @see http://www.mediawiki.org/wiki/Manual:Hooks/BaseTemplateToolbox
		 *
		 * @since  1.9
		 */
		$this->globals['wgHooks']['BaseTemplateToolbox'][] = function ( $skinTemplate, &$toolbox ) use ( $hookRegistry ) {
			return $hookRegistry->load( new BaseTemplateToolbox( $skinTemplate, $toolbox ) )->process();
		};

		// Old-style registration

		$this->globals['wgHooks']['LoadExtensionSchemaUpdates'][] = 'SMWHooks::onSchemaUpdate';
		$this->globals['wgHooks']['ParserTestTables'][]    = 'SMWHooks::onParserTestTables';
		$this->globals['wgHooks']['AdminLinks'][]          = 'SMWHooks::addToAdminLinks';
		$this->globals['wgHooks']['PageSchemasRegisterHandlers'][] = 'SMWHooks::onPageSchemasRegistration';
		$this->globals['wgHooks']['ArticlePurge'][] = 'SMWHooks::onArticlePurge';
		$this->globals['wgHooks']['ParserAfterTidy'][] = 'SMWHooks::onParserAfterTidy';
		$this->globals['wgHooks']['LinksUpdateConstructed'][] = 'SMWHooks::onLinksUpdateConstructed';
		$this->globals['wgHooks']['ArticleDelete'][] = 'SMWHooks::onArticleDelete';
		$this->globals['wgHooks']['TitleMoveComplete'][] = 'SMWHooks::onTitleMoveComplete';
		$this->globals['wgHooks']['NewRevisionFromEditComplete'][] = 'SMWHooks::onNewRevisionFromEditComplete';
		$this->globals['wgHooks']['InternalParseBeforeLinks'][] = 'SMWHooks::onInternalParseBeforeLinks';
		$this->globals['wgHooks']['OutputPageParserOutput'][] = 'SMWHooks::onOutputPageParserOutput';
		$this->globals['wgHooks']['ArticleFromTitle'][] = 'SMWHooks::onArticleFromTitle';
		$this->globals['wgHooks']['SkinTemplateNavigation'][] = 'SMWHooks::onSkinTemplateNavigation';
		$this->globals['wgHooks']['UnitTestsList'][] = 'SMWHooks::registerUnitTests';
		$this->globals['wgHooks']['ResourceLoaderTestModules'][] = 'SMWHooks::registerQUnitTests';
		$this->globals['wgHooks']['SpecialStatsAddExtra'][] = 'SMWHooks::onSpecialStatsAddExtra';
		$this->globals['wgHooks']['GetPreferences'][] = 'SMWHooks::onGetPreferences';
		$this->globals['wgHooks']['BeforePageDisplay'][] = 'SMWHooks::onBeforePageDisplay';
		$this->globals['wgHooks']['TitleIsAlwaysKnown'][] = 'SMWHooks::onTitleIsAlwaysKnown';
		$this->globals['wgHooks']['BeforeDisplayNoArticleText'][] = 'SMWHooks::onBeforeDisplayNoArticleText';
		$this->globals['wgHooks']['ResourceLoaderGetConfigVars'][] = 'SMWHooks::onResourceLoaderGetConfigVars';
		$this->globals['wgHooks']['SkinAfterContent'][] = 'SMWHooks::onSkinAfterContent';
		$this->globals['wgHooks']['ExtensionTypes'][] = 'SMWHooks::addSemanticExtensionType';

	}

	/**
	 * Register parser hooks
	 *
	 * @since 1.9
	 */
	protected function registerParserHooks() {

		$settings = $this->settings;
		$objectBuilder = $this->withContext()->getDependencyBuilder();

		/**
		 * Called when the parser initialises for the first time
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
		 *
		 * @since  1.9
		 */
		$this->globals['wgHooks']['ParserFirstCallInit'][] = function ( \Parser &$parser ) use ( $objectBuilder, $settings ) {

			/**
			 * {{#ask}}
			 *
			 * @since  1.9
			 */
			$parser->setFunctionHook( 'ask', function( $parser ) use ( $objectBuilder, $settings ) {
				$ask = $objectBuilder->newObject( 'AskParserFunction', array( 'Parser' => $parser ) );
				return $settings->get( 'smwgQEnabled' ) ? $ask->parse( func_get_args() ) : $ask->disabled();
			} );

			/**
			 * {{#show}}
			 *
			 * @since  1.9
			 */
			$parser->setFunctionHook( 'show', function( $parser ) use ( $objectBuilder, $settings ) {
				$show = $objectBuilder->newObject( 'ShowParserFunction', array( 'Parser' => $parser ) );
				return $settings->get( 'smwgQEnabled' ) ? $show->parse( func_get_args() ) : $show->disabled();
			} );

			return true;
		};

		$this->globals['wgHooks']['ParserFirstCallInit'][] = 'SMW\DocumentationParserFunction::staticInit';
		$this->globals['wgHooks']['ParserFirstCallInit'][] = 'SMW\InfoParserFunction::staticInit';
		$this->globals['wgHooks']['ParserFirstCallInit'][] = 'SMWHooks::onParserFirstCallInit';

	}

}
