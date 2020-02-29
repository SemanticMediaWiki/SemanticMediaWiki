<?php

namespace SMW\MediaWiki;

use Hooks;
use SMW\Store;
use SMW\Parser\AnnotationProcessor;
use SMW\Property\Annotator as PropertyAnnotator;
use Onoi\MessageReporter\MessageReporter;
use SMW\Schema\SchemaTypes;
use SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener;

/**
 * @private
 *
 * This class is to provide a single point of entry for hooks defined by Semantic
 * MediaWiki. The `HookDispatcherAwareTrait` is deployed to simplify the removal
 * of the `Hooks` static caller from a class and allows the injection of the
 * `HookDispatcher` instead to invoke the appropriate method.
 *
 * The removal of the `Hooks` static caller follows mainly the problem of removing
 * global state from a class which would persists during testing and hereby can
 * alter results in a manner unpredictable based on hooks enabled by the time of
 * the test run.
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class HookDispatcher {

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.listener.registerpropertychangelisteners.md
	 * @since 3.2
	 *
	 * @param PropertyChangeListener $propertyChangeListener
	 */
	public function onRegisterPropertyChangeListeners( PropertyChangeListener $propertyChangeListener ) {
		Hooks::run( 'SMW::Listener::ChangeListener::RegisterPropertyChangeListeners', [ $propertyChangeListener ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.schema.registerschematypes.md
	 * @since 3.2
	 *
	 * @param SchemaTypes $schemaTypes
	 */
	public function onRegisterSchemaTypes( SchemaTypes $schemaTypes ) {
		Hooks::run( 'SMW::Schema::RegisterSchemaTypes', [ $schemaTypes ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.getpreferences.md
	 * @since 3.2
	 *
	 * @param array &$configuration
	 */
	public function onSettingsBeforeInitializationComplete( array &$configuration ) {

		// Deprecated since 3.1
		\Hooks::run( 'SMW::Config::BeforeCompletion', [ &$configuration ] );


		Hooks::run( 'SMW::Settings::BeforeInitializationComplete', [ &$configuration ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.getpreferences.md
	 * @since 3.2
	 *
	 * @param User $user
	 * @param array &$preferences
	 */
	public function onGetPreferences( \User $user, array &$preferences ) {
		Hooks::run( 'SMW::GetPreferences', [ $user, &$preferences ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.parser.beforemagicwordsfinder.md
	 * @since 3.2
	 *
	 * @param array &$magicWords
	 */
	public function onBeforeMagicWordsFinder( array &$magicWords ) {
		Hooks::run( 'SMW::Parser::BeforeMagicWordsFinder', [ &$magicWords ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.parser.afterlinksprocessingcomplete.md
	 * @since 3.2
	 *
	 * @param string &$text
	 * @param AnnotationProcessor $annotationProcessor
	 */
	public function onAfterLinksProcessingComplete( string &$text, AnnotationProcessor $annotationProcessor ) {
		Hooks::run( 'SMW::Parser::AfterLinksProcessingComplete', [ &$text, $annotationProcessor ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.parser.parseraftertidypropertyannotationcomplete.md
	 * @since 3.2
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param ParserOutput $parserOutput
	 */
	public function onParserAfterTidyPropertyAnnotationComplete( PropertyAnnotator $propertyAnnotator, \ParserOutput $parserOutput ) {
		\Hooks::run( 'SMW::Parser::ParserAfterTidyPropertyAnnotationComplete', [ $propertyAnnotator, $parserOutput ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.maintenance.afterupdateentitycollationcomplete.md
	 * @since 3.2
	 *
	 * @param Store $store
	 * @param MessageReporter $messageReporter
	 */
	public function onAfterUpdateEntityCollationComplete( Store $store, MessageReporter $messageReporter ) {
		\Hooks::run( 'SMW::Maintenance::AfterUpdateEntityCollationComplete', [ $store, $messageReporter ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.indicator.entityexaminerregisterindicatorproviders.md
	 * @since 3.2
	 *
	 * @param Store $store
	 * @param array &$indicatorProviders
	 */
	public function onRegisterEntityExaminerIndicatorProviders( Store $store, array &$indicatorProviders ) {
		Hooks::run( 'SMW::Indicator::EntityExaminer::RegisterIndicatorProviders', [ $store, &$indicatorProviders ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.indicator.entityexaminerregisterdeferrableindicatorproviders.md
	 * @since 3.2
	 *
	 * @param Store $store
	 * @param array &$indicatorProviders
	 */
	public function onRegisterEntityExaminerDeferrableIndicatorProviders( Store $store, array &$indicatorProviders ) {
		Hooks::run( 'SMW::Indicator::EntityExaminer::RegisterDeferrableIndicatorProviders', [ $store, &$indicatorProviders ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.revisionguard.isapprovedrevision.md
	 *
	 * Hooks to define whether the latest used revision is approved or not, and
	 * when it is not approved the hook should return `false`.
	 *
	 * @note This hook is only to be called from the `RevisionGuard` class.
	 *
	 * @since 3.2
	 *
	 * @param Title $title
	 * @param int $latestRevID
	 *
	 * @return bool
	 */
	public function onIsApprovedRevision( \Title $title, int $latestRevID ) : bool {
		return Hooks::run( 'SMW::RevisionGuard::IsApprovedRevision', [ $title, $latestRevID ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.revisionguard.changerevisionid.md
	 *
	 * @note This hook is only to be called from the `RevisionGuard` class.
	 *
	 * @since 3.2
	 *
	 * @param Title $title
	 * @param int &$latestRevID
	 */
	public function onChangeRevisionID( \Title $title, int &$latestRevID ) {
		Hooks::run( 'SMW::RevisionGuard::ChangeRevisionID', [ $title, &$latestRevID ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.revisionguard.changeFile.md
	 *
	 * @note This hook is only to be called from the `RevisionGuard` class.
	 *
	 * @since 3.2
	 *
	 * @param Title $title
	 * @param File|null $file
	 */
	public function onChangeFile( \Title $title, &$file ) {
		Hooks::run( 'SMW::RevisionGuard::ChangeFile', [ $title, &$file ] );
	}

}
