<?php

namespace SMW;

use SMW\MediaWiki\Hooks\LinksUpdateConstructed;
use SMW\MediaWiki\Hooks\ArticlePurge;
use SMW\MediaWiki\Hooks\TitleMoveComplete;
use SMW\MediaWiki\Hooks\BaseTemplateToolbox;
use SMW\MediaWiki\Hooks\ArticleDelete;
use SMW\MediaWiki\Hooks\SpecialStatsAddExtra;
use SMW\MediaWiki\Hooks\InternalParseBeforeLinks;
use SMW\MediaWiki\Hooks\SkinAfterContent;
use SMW\MediaWiki\Hooks\OutputPageParserOutput;
use SMW\MediaWiki\Hooks\BeforePageDisplay;
use SMW\MediaWiki\Hooks\FileUpload;
use SMW\MediaWiki\Hooks\NewRevisionFromEditComplete;
use SMW\MediaWiki\Hooks\ParserAfterTidy;
use SMW\MediaWiki\Hooks\ResourceLoaderGetConfigVars;
use SMW\MediaWiki\Hooks\GetPreferences;
use SMW\MediaWiki\Hooks\SkinTemplateNavigation;
use SMW\MediaWiki\Hooks\ExtensionSchemaUpdates;
use SMW\MediaWiki\Hooks\ResourceLoaderTestModules;
use SMW\MediaWiki\Hooks\ExtensionTypes;
use SMW\MediaWiki\Hooks\TitleIsAlwaysKnown;
use SMW\MediaWiki\Hooks\BeforeDisplayNoArticleText;
use SMW\MediaWiki\Hooks\ArticleFromTitle;

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
		$this->registerFunctionHooks();
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
	protected function registerFunctionHooks() {

		$settings = $this->settings;
		$globals  = $this->globals;
		$basePath = $this->directory;
		$installPath = $this->globals['IP'];

		/**
		 * Hook: Called by BaseTemplate when building the toolbox array and
		 * returning it for the skin to output.
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BaseTemplateToolbox
		 *
		 * @since  1.9
		 */
		$this->globals['wgHooks']['BaseTemplateToolbox'][] = function ( $skinTemplate, &$toolbox ) {
			$baseTemplateToolbox = new BaseTemplateToolbox( $skinTemplate, $toolbox );
			return $baseTemplateToolbox->process();
		};

		/**
		 * Hook: Allows extensions to add text after the page content and article
		 * metadata.
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinAfterContent
		 *
		 * @since  1.9
		 */
		$this->globals['wgHooks']['SkinAfterContent'][] = function ( &$data, $skin = null ) {
			$skinAfterContent = new SkinAfterContent( $data, $skin );
			return $skinAfterContent->process();
		};

		/**
		 * Hook: Called after parse, before the HTML is added to the output
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageParserOutput
		 *
		 * @since  1.9
		 */
		$this->globals['wgHooks']['OutputPageParserOutput'][] = function ( &$outputPage, $parserOutput ) {
			$outputPageParserOutput = new OutputPageParserOutput( $outputPage, $parserOutput );
			return $outputPageParserOutput->process();
		};

		/**
		 * Hook: Add changes to the output page, e.g. adding of CSS or JavaScript
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['BeforePageDisplay'][] = function ( &$outputPage, &$skin ) {
			$beforePageDisplay = new BeforePageDisplay( $outputPage, $skin );
			return $beforePageDisplay->process();
		};

		/**
		 * Hook: InternalParseBeforeLinks is used to process the expanded wiki
		 * code after <nowiki>, HTML-comments, and templates have been treated.
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/InternalParseBeforeLinks
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['InternalParseBeforeLinks'][] = function ( &$parser, &$text ) {
			$internalParseBeforeLinks = new InternalParseBeforeLinks( $parser, $text );
			return $internalParseBeforeLinks->process();
		};

		/**
		 * Hook: NewRevisionFromEditComplete called when a revision was inserted
		 * due to an edit
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/NewRevisionFromEditComplete
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['NewRevisionFromEditComplete'][] = function ( $wikiPage, $revision, $baseId, $user ) {
			$newRevisionFromEditComplete = new NewRevisionFromEditComplete( $wikiPage, $revision, $baseId, $user );
			return $newRevisionFromEditComplete->process();
		};

		/**
		 * Hook: TitleMoveComplete occurs whenever a request to move an article
		 * is completed
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['TitleMoveComplete'][] = function ( &$oldTitle, &$newTitle, &$user, $oldId, $newId ) {
			$titleMoveComplete = new TitleMoveComplete( $oldTitle, $newTitle, $user, $oldId, $newId );
			return $titleMoveComplete->process();
		};

		/**
		 * Hook: ArticlePurge executes before running "&action=purge"
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticlePurge
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['ArticlePurge'][] = function ( &$wikiPage ) {
			$articlePurge = new ArticlePurge( $wikiPage );
			return $articlePurge->process();
		};

		/**
		 * Hook: ArticleDelete occurs whenever the software receives a request
		 * to delete an article
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDelete
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['ArticleDelete'][] = function ( &$wikiPage, &$user, &$reason, &$error ) {
			$articleDelete = new ArticleDelete( $wikiPage, $user, $reason, $error );
			return $articleDelete->process();
		};

		/**
		 * Hook: LinksUpdateConstructed called at the end of LinksUpdate() construction
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateConstructed
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['LinksUpdateConstructed'][] = function ( $linksUpdate ) {
			$linksUpdateConstructed = new LinksUpdateConstructed( $linksUpdate );
			return $linksUpdateConstructed->process();
		};

		/**
		 * Hook: ParserAfterTidy to add some final processing to the fully-rendered page output
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['ParserAfterTidy'][] = function ( &$parser, &$text ) {
			$parserAfterTidy = new ParserAfterTidy( $parser, $text );
			return $parserAfterTidy->process();
		};

		/**
		 * Hook: Add extra statistic at the end of Special:Statistics
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialStatsAddExtra
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['SpecialStatsAddExtra'][] = function ( &$extraStats ) use ( $globals ) {
			$specialStatsAddExtra = new SpecialStatsAddExtra( $extraStats, $globals['wgVersion'], $globals['wgLang'] );
			return $specialStatsAddExtra->process();
		};

		/**
		 * Hook: For extensions adding their own namespaces or altering the defaults
		 *
		 * @Bug 34383
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/CanonicalNamespaces
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['CanonicalNamespaces'][] = function ( &$list ) {
			$list = $list + NamespaceManager::getCanonicalNames();
			return true;
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUpload
		 *
		 * @since 1.9.1
		 */
		$this->globals['wgHooks']['FileUpload'][] = function ( $file, $reupload ) {
			$fileUpload = new FileUpload( $file, $reupload );
			return $fileUpload->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderGetConfigVars
		 */
		$this->globals['wgHooks']['ResourceLoaderGetConfigVars'][] = function ( &$vars ) {
			$resourceLoaderGetConfigVars = new ResourceLoaderGetConfigVars( $vars );
			return $resourceLoaderGetConfigVars->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
		 */
		$this->globals['wgHooks']['GetPreferences'][] = function ( $user, &$preferences ) {
			$getPreferences = new GetPreferences( $user, $preferences );
			return $getPreferences->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation
		 */
		$this->globals['wgHooks']['SkinTemplateNavigation'][] = function ( &$skinTemplate, &$links ) {
			$skinTemplateNavigation = new SkinTemplateNavigation( $skinTemplate, $links );
			return $skinTemplateNavigation->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
		 */
		$this->globals['wgHooks']['LoadExtensionSchemaUpdates'][] = function ( $databaseUpdater ) {
			$extensionSchemaUpdates = new ExtensionSchemaUpdates( $databaseUpdater );
			return $extensionSchemaUpdates->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
		 */
		$this->globals['wgHooks']['ResourceLoaderTestModules'][] = function ( &$testModules, &$resourceLoader ) use ( $basePath, $installPath ) {

			$resourceLoaderTestModules = new ResourceLoaderTestModules(
				$resourceLoader,
				$testModules,
				$basePath,
				$installPath
			);

			return $resourceLoaderTestModules->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ExtensionTypes
		 */
		$this->globals['wgHooks']['ExtensionTypes'][] = function ( &$extTypes ) {
			$extensionTypes = new ExtensionTypes( $extTypes );
			return $extensionTypes->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleIsAlwaysKnown
		 */
		$this->globals['wgHooks']['TitleIsAlwaysKnown'][] = function ( $title, &$result  ) {
			$titleIsAlwaysKnown = new TitleIsAlwaysKnown( $title, $result );
			return $titleIsAlwaysKnown->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforeDisplayNoArticleText
		 */
		$this->globals['wgHooks']['BeforeDisplayNoArticleText'][] = function ( $article  ) {
			$beforeDisplayNoArticleText = new BeforeDisplayNoArticleText( $article );
			return $beforeDisplayNoArticleText->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleFromTitle
		 */
		$this->globals['wgHooks']['ArticleFromTitle'][] = function ( &$title, &$article ) {
			$articleFromTitle = new ArticleFromTitle( $title, $article );
			return $articleFromTitle->process();
		};

		// Old-style registration

		$this->globals['wgHooks']['AdminLinks'][]          = 'SMWHooks::addToAdminLinks';
		$this->globals['wgHooks']['PageSchemasRegisterHandlers'][] = 'SMWHooks::onPageSchemasRegistration';
	}

	/**
	 * @since 1.9
	 */
	protected function registerParserHooks() {

		$settings = $this->settings;
		$builder  = $this->withContext()->getDependencyBuilder();

		/**
		 * Called when the parser initialises for the first time
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
		 *
		 * @since  1.9
		 */
		$this->globals['wgHooks']['ParserFirstCallInit'][] = function ( \Parser &$parser ) use ( $builder, $settings ) {

			/**
			 * {{#ask}}
			 *
			 * @since  1.9
			 */
			$parser->setFunctionHook( 'ask', function( $parser ) use ( $builder, $settings ) {
				$ask = $builder->newObject( 'AskParserFunction', array( 'Parser' => $parser ) );
				return $settings->get( 'smwgQEnabled' ) ? $ask->parse( func_get_args() ) : $ask->isQueryDisabled();
			} );

			/**
			 * {{#show}}
			 *
			 * @since  1.9
			 */
			$parser->setFunctionHook( 'show', function( $parser ) use ( $builder, $settings ) {
				$show = $builder->newObject( 'ShowParserFunction', array( 'Parser' => $parser ) );
				return $settings->get( 'smwgQEnabled' ) ? $show->parse( func_get_args() ) : $show->isQueryDisabled();
			} );

			/**
			 * {{#subobject}}
			 *
			 * @since  1.9
			 */
			$parser->setFunctionHook( 'subobject', function( $parser ) use ( $builder ) {
				$instance = $builder->newObject( 'SubobjectParserFunction', array( 'Parser' => $parser ) );
				return $instance->parse( ParameterFormatterFactory::newFromArray( func_get_args() ) );
			} );

			$parser->setFunctionHook( 'concept', array( 'SMW\ConceptParserFunction', 'render' ) );
			$parser->setFunctionHook( 'set', array( 'SMW\SetParserFunction', 'render' ) );
			$parser->setFunctionHook( 'set_recurring_event', array( 'SMW\RecurringEventsParserFunction', 'render' ) );
			$parser->setFunctionHook( 'declare', array( 'SMW\DeclareParserFunction', 'render' ), SFH_OBJECT_ARGS );

			return true;
		};

		$this->globals['wgHooks']['ParserFirstCallInit'][] = 'SMW\DocumentationParserFunction::staticInit';
		$this->globals['wgHooks']['ParserFirstCallInit'][] = 'SMW\InfoParserFunction::staticInit';

	}

}
