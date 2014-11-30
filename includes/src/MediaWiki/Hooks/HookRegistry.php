<?php

namespace SMW\MediaWiki\Hooks;

use SMW\NamespaceManager;
use SMW\ApplicationFactory;
use SMW\ParameterFormatterFactory;

use Parser;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class HookRegistry {

	/**
	 * @var array
	 */
	private $globalVars;

	/**
	 * @var string
	 */
	private $directory;

	/**
	 * @since 2.1
	 *
	 * @param array &$globalVars
	 * @param string $directory
	 */
	public function __construct( &$globalVars = array(), $directory = '' ) {
		$this->globalVars =& $globalVars;
		$this->directory = $directory;
	}

	/**
	 * @since 2.1
	 *
	 * @return array
	 */
	public function getListOfRegisteredFunctionHooks() {
		return array_keys( $this->getListOfFunctionHookDefinitions() );
	}

	/**
	 * @since 2.1
	 *
	 * @return array
	 */
	public function getListOfRegisteredParserFunctions() {
		return array_keys( $this->getListOfParserFunctionDefinitions() );
	}

	/**
	 * @since 2.1
	 *
	 * @param string $name
	 *
	 * @return Closure
	 */
	public function getDefinition( $name ) {

		$listOfDefinitions = array_merge(
			$this->getListOfFunctionHookDefinitions(),
			$this->getListOfParserFunctionDefinitions()
		);

		if ( isset( $listOfDefinitions[ $name ] ) ) {
			return $listOfDefinitions[ $name ];
		}

		throw new RuntimeException( "$name is unknown or not registered" );
	}

	/**
	 * @since 2.1
	 */
	public function register() {
		foreach ( $this->getListOfFunctionHookDefinitions() as $hook => $definition ) {
			$this->globalVars['wgHooks'][ $hook ][] = $definition;
		}
	}

	private function getListOfParserFunctionDefinitions() {

		$parserFunctionDefinition = array();

		/**
		 * {{#ask}}
		 *
		 * @since 2.1
		 */
		$parserFunctionDefinition['ask'] = function( $parser ) {

			$parserFunctionFactory = ApplicationFactory::getInstance()->newParserFunctionFactory( $parser );
			$instance = $parserFunctionFactory->newAskParserFunction();

			if ( ApplicationFactory::getInstance()->getSettings()->get( 'smwgQEnabled' ) ) {
				return $instance->parse( func_get_args() );
			}

			return $instance->isQueryDisabled();
		};

		/**
		 * {{#show}}
		 *
		 * @since 2.1
		 */
		$parserFunctionDefinition['show'] = function( $parser ) {

			$parserFunctionFactory = ApplicationFactory::getInstance()->newParserFunctionFactory( $parser );
			$instance = $parserFunctionFactory->newShowParserFunction();

			if ( ApplicationFactory::getInstance()->getSettings()->get( 'smwgQEnabled' ) ) {
				return $instance->parse( func_get_args() );
			}

			return $instance->isQueryDisabled();
		};

		/**
		 * {{#subobject}}
		 *
		 * @since 2.1
		 */
		$parserFunctionDefinition['subobject'] = function( $parser ) {

			$parserFunctionFactory = ApplicationFactory::getInstance()->newParserFunctionFactory( $parser );
			$instance = $parserFunctionFactory->newSubobjectParserFunction();

			return $instance->parse( ParameterFormatterFactory::newFromArray( func_get_args() ) );
		};

		/**
		 * {{#set_recurring_event}}
		 *
		 * @since 2.1
		 */
		$parserFunctionDefinition['set_recurring_event'] = function( $parser ) {

			$parserFunctionFactory = ApplicationFactory::getInstance()->newParserFunctionFactory( $parser );
			$instance = $parserFunctionFactory->newRecurringEventsParserFunction();

			return $instance->parse( ParameterFormatterFactory::newFromArray( func_get_args() ) );
		};

		/**
		 * {{#set}}
		 *
		 * @since 2.1
		 */
		$parserFunctionDefinition['set'] = function( $parser ) {

			$parserFunctionFactory = ApplicationFactory::getInstance()->newParserFunctionFactory( $parser );
			$instance = $parserFunctionFactory->newSetParserFunction();

			return $instance->parse( ParameterFormatterFactory::newFromArray( func_get_args() ) );
		};

		/**
		 * {{#concept}}
		 *
		 * @since 2.1
		 */
		$parserFunctionDefinition['concept'] = function( $parser ) {

			$parserFunctionFactory = ApplicationFactory::getInstance()->newParserFunctionFactory( $parser );
			$instance = $parserFunctionFactory->newConceptParserFunction();

			return $instance->parse( func_get_args() );
		};

		/**
		 * {{#declare}}
		 *
		 * @since 2.1
		 */
		$parserFunctionDefinition['declare'] = function( $parser, $frame, $args ) {

			$parserFunctionFactory = ApplicationFactory::getInstance()->newParserFunctionFactory( $parser );
			$instance = $parserFunctionFactory->newDeclareParserFunction();

			return $instance->parse( $frame, $args );
		};

		return $parserFunctionDefinition;
	}

	private function getListOfFunctionHookDefinitions() {

		$functionHookDefinition = array();

		$globalVars = $this->globalVars;
		$basePath   = $this->directory;

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
		 */
		$listOfParserFunctions = $this->getListOfParserFunctionDefinitions();

		$functionHookDefinition['ParserFirstCallInit'] = function ( Parser &$parser ) use ( $listOfParserFunctions ) {

			foreach ( $listOfParserFunctions as $parserFunctionName => $parserDefinition ) {

				$parserflag = $parserFunctionName === 'declare' ? SFH_OBJECT_ARGS : 0;

				$parser->setFunctionHook(
					$parserFunctionName,
					$parserDefinition,
					$parserflag
				);
			}

			return true;
		};

		/**
		 * Hook: ParserAfterTidy to add some final processing to the fully-rendered page output
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
		 */
		$functionHookDefinition['ParserAfterTidy'] = function ( &$parser, &$text ) {
			$parserAfterTidy = new ParserAfterTidy( $parser, $text );
			return $parserAfterTidy->process();
		};

		/**
		 * Hook: Called by BaseTemplate when building the toolbox array and
		 * returning it for the skin to output.
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BaseTemplateToolbox
		 */
		$functionHookDefinition['BaseTemplateToolbox'] = function ( $skinTemplate, &$toolbox ) {
			$baseTemplateToolbox = new BaseTemplateToolbox( $skinTemplate, $toolbox );
			return $baseTemplateToolbox->process();
		};

		/**
		 * Hook: Allows extensions to add text after the page content and article
		 * metadata.
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinAfterContent
		 */
		$functionHookDefinition['SkinAfterContent'] = function ( &$data, $skin = null ) {
			$skinAfterContent = new SkinAfterContent( $data, $skin );
			return $skinAfterContent->process();
		};

		/**
		 * Hook: Called after parse, before the HTML is added to the output
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageParserOutput
		 */
		$functionHookDefinition['OutputPageParserOutput'] = function ( &$outputPage, $parserOutput ) {
			$outputPageParserOutput = new OutputPageParserOutput( $outputPage, $parserOutput );
			return $outputPageParserOutput->process();
		};

		/**
		 * Hook: Add changes to the output page, e.g. adding of CSS or JavaScript
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
		 */
		$functionHookDefinition['BeforePageDisplay'] = function ( &$outputPage, &$skin ) {
			$beforePageDisplay = new BeforePageDisplay( $outputPage, $skin );
			return $beforePageDisplay->process();
		};

		/**
		 * Hook: InternalParseBeforeLinks is used to process the expanded wiki
		 * code after <nowiki>, HTML-comments, and templates have been treated.
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/InternalParseBeforeLinks
		 */
		$functionHookDefinition['InternalParseBeforeLinks'] = function ( &$parser, &$text ) {
			$internalParseBeforeLinks = new InternalParseBeforeLinks( $parser, $text );
			return $internalParseBeforeLinks->process();
		};

		/**
		 * Hook: NewRevisionFromEditComplete called when a revision was inserted
		 * due to an edit
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/NewRevisionFromEditComplete
		 */
		$functionHookDefinition['NewRevisionFromEditComplete'] = function ( $wikiPage, $revision, $baseId, $user ) {
			$newRevisionFromEditComplete = new NewRevisionFromEditComplete( $wikiPage, $revision, $baseId, $user );
			return $newRevisionFromEditComplete->process();
		};

		/**
		 * Hook: TitleMoveComplete occurs whenever a request to move an article
		 * is completed
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
		 */
		$functionHookDefinition['TitleMoveComplete'] = function ( &$oldTitle, &$newTitle, &$user, $oldId, $newId ) {
			$titleMoveComplete = new TitleMoveComplete( $oldTitle, $newTitle, $user, $oldId, $newId );
			return $titleMoveComplete->process();
		};

		/**
		 * Hook: ArticlePurge executes before running "&action=purge"
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticlePurge
		 */
		$functionHookDefinition['ArticlePurge']= function ( &$wikiPage ) {
			$articlePurge = new ArticlePurge( $wikiPage );
			return $articlePurge->process();
		};

		/**
		 * Hook: ArticleDelete occurs whenever the software receives a request
		 * to delete an article
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDelete
		 */
		$functionHookDefinition['ArticleDelete'] = function ( &$wikiPage, &$user, &$reason, &$error ) {
			$articleDelete = new ArticleDelete( $wikiPage, $user, $reason, $error );
			return $articleDelete->process();
		};

		/**
		 * Hook: LinksUpdateConstructed called at the end of LinksUpdate() construction
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateConstructed
		 */
		$functionHookDefinition['LinksUpdateConstructed'] = function ( $linksUpdate ) {
			$linksUpdateConstructed = new LinksUpdateConstructed( $linksUpdate );
			return $linksUpdateConstructed->process();
		};

		/**
		 * Hook: Add extra statistic at the end of Special:Statistics
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialStatsAddExtra
		 */
		$functionHookDefinition['SpecialStatsAddExtra'] = function ( &$extraStats ) use ( $globalVars ) {
			$specialStatsAddExtra = new SpecialStatsAddExtra( $extraStats, $globalVars['wgVersion'], $globalVars['wgLang'] );
			return $specialStatsAddExtra->process();
		};

		/**
		 * Hook: For extensions adding their own namespaces or altering the defaults
		 *
		 * @Bug 34383
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/CanonicalNamespaces
		 */
		$functionHookDefinition['CanonicalNamespaces'] = function ( &$list ) {
			$list = $list + NamespaceManager::getCanonicalNames();
			return true;
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUpload
		 *
		 * @since 1.9.1
		 */
		$functionHookDefinition['FileUpload'] = function ( $file, $reupload ) {
			$fileUpload = new FileUpload( $file, $reupload );
			return $fileUpload->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderGetConfigVars
		 */
		$functionHookDefinition['ResourceLoaderGetConfigVars'] = function ( &$vars ) {
			$resourceLoaderGetConfigVars = new ResourceLoaderGetConfigVars( $vars );
			return $resourceLoaderGetConfigVars->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
		 */
		$functionHookDefinition['GetPreferences'] = function ( $user, &$preferences ) {
			$getPreferences = new GetPreferences( $user, $preferences );
			return $getPreferences->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation
		 */
		$functionHookDefinition['SkinTemplateNavigation'] = function ( &$skinTemplate, &$links ) {
			$skinTemplateNavigation = new SkinTemplateNavigation( $skinTemplate, $links );
			return $skinTemplateNavigation->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
		 */
		$functionHookDefinition['LoadExtensionSchemaUpdates'] = function ( $databaseUpdater ) {
			$extensionSchemaUpdates = new ExtensionSchemaUpdates( $databaseUpdater );
			return $extensionSchemaUpdates->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
		 */
		$functionHookDefinition['ResourceLoaderTestModules'] = function ( &$testModules, &$resourceLoader ) use ( $basePath, $globalVars ) {

			$installPath = $globalVars['IP'];

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
		$functionHookDefinition['ExtensionTypes'] = function ( &$extTypes ) {
			$extensionTypes = new ExtensionTypes( $extTypes );
			return $extensionTypes->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleIsAlwaysKnown
		 */
		$functionHookDefinition['TitleIsAlwaysKnown'] = function ( $title, &$result ) {
			$titleIsAlwaysKnown = new TitleIsAlwaysKnown( $title, $result );
			return $titleIsAlwaysKnown->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforeDisplayNoArticleText
		 */
		$functionHookDefinition['BeforeDisplayNoArticleText'] = function ( $article ) {
			$beforeDisplayNoArticleText = new BeforeDisplayNoArticleText( $article );
			return $beforeDisplayNoArticleText->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleFromTitle
		 */
		$functionHookDefinition['ArticleFromTitle'] = function ( &$title, &$article ) {
			$articleFromTitle = new ArticleFromTitle( $title, $article );
			return $articleFromTitle->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleIsMovable
		 */
		$functionHookDefinition['TitleIsMovable'] = function ( $title, &$isMovable ) {
			$titleIsMovable = new TitleIsMovable( $title, $isMovable );
			return $titleIsMovable->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EditPage::showEditForm:initial
		 */
		$functionHookDefinition['EditPage::showEditForm:initial'] = function ( $editPage, $output ) {

			$mwCollaboratorFactory = ApplicationFactory::getInstance()->newMwCollaboratorFactory();

			$htmlFormBuilder = $mwCollaboratorFactory->newHtmlFormBuilder(
				$editPage->getTitle(),
				$output->getLanguage()
			);

			$editPageForm = new EditPageForm( $editPage, $htmlFormBuilder );
			return $editPageForm->process();
		};

		return $functionHookDefinition;
	}

}
