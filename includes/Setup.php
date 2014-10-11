<?php

namespace SMW;

use SMW\MediaWiki\Hooks\HookRegistry;

/**
 * Extension setup and registration
 *
 * Register all hooks, set up extension credits etc.
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
final class Setup implements ContextAware {

	/** @var array */
	protected $globals;

	/** @var string */
	protected $directory;

	/** @var Settings */
	protected $settings;

	/** @var ContextResource */
	protected $context = null;

	/**
	 * @since 1.9
	 *
	 * @param array &$globals
	 * @param string $directory
	 * @param ContextResource|null $context
	 */
	public function __construct( &$globals, $directory, ContextResource $context = null ) {
		$this->globals =& $globals;
		$this->directory = $directory;
		$this->context = $context;
	}

	/**
	 * @since 1.9
	 */
	public function run() {
		Profiler::In();

		$this->init();
		$this->loadSettings();

		$this->registerI18n();
		$this->registerWebApi();
		$this->registerJobClasses();
		$this->registerSpecialPages();
		$this->registerPermissions();

		$this->registerParamDefinitions();
		$this->registerFooterIcon();
		$this->registerHooks();

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

		if ( !isset( $this->globals['smwgNamespace'] ) ) {
			$this->globals['smwgNamespace'] = parse_url( $this->globals['wgServer'], PHP_URL_HOST );
		}

		if ( !isset( $this->globals['smwgScriptPath'] ) ) {
			$this->globals['smwgScriptPath'] = ( $this->globals['wgExtensionAssetsPath'] === false ? $this->globals['wgScriptPath'] . '/extensions' : $this->globals['wgExtensionAssetsPath'] ) . '/SemanticMediaWiki';
		}

		if ( is_file( $this->directory . "/resources/Resources.php" ) ) {
			$this->globals['wgResourceModules'] = array_merge( $this->globals['wgResourceModules'], include( $this->directory . "/resources/Resources.php" ) );
		}

	}

	/**
	 * @see ContextAware::withContext
	 *
	 * @since 1.9
	 *
	 * @return ContextResource
	 */
	public function withContext() {
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
	 * @since 1.9
	 */
	protected function registerSettings( Settings $settings ) {
		$this->withContext()->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $settings );
		Application::getInstance()->registerObject( 'Settings', $settings );
		return $settings;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgExtensionMessagesFiles
	 *
	 * @since 1.9
	 */
	protected function registerI18n() {

		$smwgIP = $this->settings->get( 'smwgIP' );

		$this->globals['wgMessagesDirs']['SemanticMediaWiki'] = $smwgIP . 'i18n';
		$this->globals['wgExtensionMessagesFiles']['SemanticMediaWiki'] = $smwgIP . 'languages/SMW_Messages.php';
		$this->globals['wgExtensionMessagesFiles']['SemanticMediaWikiAlias'] = $smwgIP . 'languages/SMW_Aliases.php';
		$this->globals['wgExtensionMessagesFiles']['SemanticMediaWikiMagic'] = $smwgIP . 'languages/SMW_Magic.php';
		$this->globals['wgExtensionMessagesFiles']['SemanticMediaWikiNamespaces'] = $smwgIP . 'languages/SemanticMediaWiki.namespaces.php';

	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgAPIModules
	 *
	 * @since 1.9
	 */
	protected function registerWebApi() {

		$this->globals['wgAPIModules']['smwinfo'] = '\SMW\MediaWiki\Api\Info';
		$this->globals['wgAPIModules']['ask']     = '\SMW\MediaWiki\Api\Ask';
		$this->globals['wgAPIModules']['askargs'] = '\SMW\MediaWiki\Api\AskArgs';
		$this->globals['wgAPIModules']['browsebysubject']  = '\SMW\MediaWiki\Api\BrowseBySubject';

	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgJobClasses
	 *
	 * @since 1.9
	 */
	protected function registerJobClasses() {

		$this->globals['wgJobClasses']['SMW\UpdateJob']  = 'SMW\MediaWiki\Jobs\UpdateJob';
		$this->globals['wgJobClasses']['SMW\RefreshJob'] = 'SMW\MediaWiki\Jobs\RefreshJob';
		$this->globals['wgJobClasses']['SMW\UpdateDispatcherJob'] = 'SMW\MediaWiki\Jobs\UpdateDispatcherJob';
		$this->globals['wgJobClasses']['SMW\DeleteSubjectJob'] = 'SMW\MediaWiki\Jobs\DeleteSubjectJob';

		// Legacy definition to be removed with 1.10
		$this->globals['wgJobClasses']['SMWUpdateJob']  = 'SMW\MediaWiki\Jobs\UpdateJob';
		$this->globals['wgJobClasses']['SMWRefreshJob'] = 'SMW\MediaWiki\Jobs\RefreshJob';

	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgAvailableRights
	 * @see https://www.mediawiki.org/wiki/Manual:$wgGroupPermissions
	 *
	 * @since 1.9
	 */
	protected function registerPermissions() {

		// Rights
		$this->globals['wgAvailableRights'][] = 'smw-admin';

		// User group rights
		$this->globals['wgGroupPermissions']['sysop']['smw-admin'] = true;
		$this->globals['wgGroupPermissions']['smwadministrator']['smw-admin'] = true;

	}

	/**
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
				'page' => 'SMW\MediaWiki\Specials\SpecialSearchByProperty',
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
	 * @since 1.9
	 */
	protected function registerParamDefinitions() {
		$this->globals['wgParamDefinitions']['smwformat'] = array(
			'definition'=> 'SMWParamFormat',
		);
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgFooterIcons
	 *
	 * @since 1.9
	 */
	protected function registerFooterIcon() {
		$this->globals['wgFooterIcons']['poweredby']['semanticmediawiki'] = array(
			'src' => $this->globals['wgScriptPath'] . '/extensions/'
				. end( ( explode( '/extensions/', str_replace( DIRECTORY_SEPARATOR, '/', __DIR__), 2 ) ) )
				. '/../resources/images/smw_button.png',
			'url' => 'https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki',
			'alt' => 'Powered by Semantic MediaWiki',
		);
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$this->globals['wgHooks']
	 *
	 * @note $this->globals['wgHooks'] contains a list of hooks which specifies for every event an
	 * array of functions to be called.
	 *
	 * @since 1.9
	 */
	protected function registerHooks() {

		$hookRegistry = new HookRegistry( $this->globals, $this->directory );
		$hookRegistry->register();

		// Old-style registration
		$this->globals['wgHooks']['AdminLinks'][] = 'SMWHooks::addToAdminLinks';
		$this->globals['wgHooks']['PageSchemasRegisterHandlers'][] = 'SMWHooks::onPageSchemasRegistration';

		$this->globals['wgHooks']['ParserFirstCallInit'][] = 'SMW\DocumentationParserFunction::staticInit';
		$this->globals['wgHooks']['ParserFirstCallInit'][] = 'SMW\InfoParserFunction::staticInit';
	}

}
