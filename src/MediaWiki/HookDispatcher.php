<?php

namespace SMW\MediaWiki;

use Hooks;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use SMW\Store;
use SMW\SQLStore\TableBuilder;
use SMW\Options;
use SMW\Parser\AnnotationProcessor;
use SMW\Property\Annotator as PropertyAnnotator;
use Onoi\MessageReporter\MessageReporter;
use SMW\Schema\SchemaTypes;
use SMW\Constraint\ConstraintRegistry;
use SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\TaskHandlerRegistry;
use Title;
use User;

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

	private $hookContainer;

	private function getHookContiner(): HookContainer {
		if ( $this->hookContainer ) {
			return $this->hookContainer;
		}

		$this->hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		return $this->hookContainer;
	}
	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.admin.registertaskhandlers.md
	 * @since 3.2
	 *
	 * @param TaskHandlerRegistry $taskHandlerRegistry
	 * @param Store $store
	 * @param OutputFormatter $outputFormatter
	 * @param User $user
	 */
	public function onRegisterTaskHandlers( TaskHandlerRegistry $taskHandlerRegistry, Store $store, OutputFormatter $outputFormatter, User $user ) {
		$this->getHookContiner()
			->run( 'SMW::Admin::RegisterTaskHandlers', [ $taskHandlerRegistry, $store, $outputFormatter, $user ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.listener.registerpropertychangelisteners.md
	 * @since 3.2
	 *
	 * @param PropertyChangeListener $propertyChangeListener
	 */
	public function onRegisterPropertyChangeListeners( PropertyChangeListener $propertyChangeListener ) {
		$this->getHookContiner()
			->run( 'SMW::Listener::ChangeListener::RegisterPropertyChangeListeners', [ $propertyChangeListener ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.constraint.initconstraints.md
	 * @since 3.2
	 *
	 * @param ConstraintRegistry $constraintRegistry
	 */
	public function onInitConstraints( ConstraintRegistry $constraintRegistry ) {
		$this->getHookContiner()
			->run( 'SMW::Constraint::initConstraints', [ $constraintRegistry ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.schema.registerschematypes.md
	 * @since 3.2
	 *
	 * @param SchemaTypes $schemaTypes
	 */
	public function onRegisterSchemaTypes( SchemaTypes $schemaTypes ) {
		$this->getHookContiner()
			->run( 'SMW::Schema::RegisterSchemaTypes', [ $schemaTypes ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.settings.beforeinitializationcomplete.md
	 * @since 3.2
	 *
	 * @param array &$configuration
	 */
	public function onSettingsBeforeInitializationComplete( array &$configuration ) {

		// Deprecated since 3.1
		$this->getHookContiner()
			->run( 'SMW::Config::BeforeCompletion', [ &$configuration ] );

		$this->getHookContiner()
			->run( 'SMW::Settings::BeforeInitializationComplete', [ &$configuration ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hookshook.setup.afterinitializationcomplete.md
	 * @since 3.2
	 *
	 * @param array &$vars
	 */
	public function onSetupAfterInitializationComplete( array &$vars ) {
		$this->getHookContiner()
			->run( 'SMW::Setup::AfterInitializationComplete', [ &$vars ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.grouppermissions.beforeinitializationcomplete.md
	 * @since 3.2
	 *
	 * @param array &$grouppermissions
	 */
	public function onGroupPermissionsBeforeInitializationComplete( array &$grouppermissions ) {
		$this->getHookContiner()
			->run( 'SMW::GroupPermissions::BeforeInitializationComplete', [ &$grouppermissions ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.getpreferences.md
	 * @since 3.2
	 *
	 * @param User $user
	 * @param array &$preferences
	 */
	public function onGetPreferences( \User $user, array &$preferences ) {
		$this->getHookContiner()
			->run( 'SMW::GetPreferences', [ $user, &$preferences ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.parser.beforemagicwordsfinder.md
	 * @since 3.2
	 *
	 * @param array &$magicWords
	 */
	public function onBeforeMagicWordsFinder( array &$magicWords ) {
		$this->getHookContiner()
			->run( 'SMW::Parser::BeforeMagicWordsFinder', [ &$magicWords ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.parser.afterlinksprocessingcomplete.md
	 * @since 3.2
	 *
	 * @param string &$text
	 * @param AnnotationProcessor $annotationProcessor
	 */
	public function onAfterLinksProcessingComplete( string &$text, AnnotationProcessor $annotationProcessor ) {
		$this->getHookContiner()
			->run( 'SMW::Parser::AfterLinksProcessingComplete', [ &$text, $annotationProcessor ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.parser.parseraftertidypropertyannotationcomplete.md
	 * @since 3.2
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param ParserOutput $parserOutput
	 */
	public function onParserAfterTidyPropertyAnnotationComplete( PropertyAnnotator $propertyAnnotator, \ParserOutput $parserOutput ) {
		$this->getHookContiner()
			->run( 'SMW::Parser::ParserAfterTidyPropertyAnnotationComplete', [ $propertyAnnotator, $parserOutput ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.maintenance.afterupdateentitycollationcomplete.md
	 * @since 3.2
	 *
	 * @param Store $store
	 * @param MessageReporter $messageReporter
	 */
	public function onAfterUpdateEntityCollationComplete( Store $store, MessageReporter $messageReporter ) {
		$this->getHookContiner()
			->run( 'SMW::Maintenance::AfterUpdateEntityCollationComplete', [ $store, $messageReporter ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.indicator.entityexaminerregisterindicatorproviders.md
	 * @since 3.2
	 *
	 * @param Store $store
	 * @param array &$indicatorProviders
	 */
	public function onRegisterEntityExaminerIndicatorProviders( Store $store, array &$indicatorProviders ) {
		$this->getHookContiner()
			->run( 'SMW::Indicator::EntityExaminer::RegisterIndicatorProviders', [ $store, &$indicatorProviders ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.indicator.entityexaminerregisterdeferrableindicatorproviders.md
	 * @since 3.2
	 *
	 * @param Store $store
	 * @param array &$indicatorProviders
	 */
	public function onRegisterEntityExaminerDeferrableIndicatorProviders( Store $store, array &$indicatorProviders ) {
		$this->getHookContiner()
			->run( 'SMW::Indicator::EntityExaminer::RegisterDeferrableIndicatorProviders', [ $store, &$indicatorProviders ] );
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
	public function onIsApprovedRevision( Title $title, int $latestRevID ) : bool {
		return $this->getHookContiner()
			->run( 'SMW::RevisionGuard::IsApprovedRevision', [ $title, $latestRevID ] );
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
	public function onChangeRevisionID( Title $title, int &$latestRevID ) {
		$this->getHookContiner()
			->run( 'SMW::RevisionGuard::ChangeRevisionID', [ $title, &$latestRevID ] );
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
	public function onChangeFile( Title $title, &$file ) {
		$this->getHookContiner()
			->run( 'SMW::RevisionGuard::ChangeFile', [ $title, &$file ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.revisionguard.changerevision.md
	 *
	 * @note This hook is only to be called from the `RevisionGuard` class.
	 *
	 * @since 3.2
	 *
	 * @param Title $title
	 * @param RevisionRecord|null $revision
	 */
	public function onChangeRevision( Title $title, ?RevisionRecord &$revision ) {
		$this->getHookContiner()
			->run( 'SMW::RevisionGuard::ChangeRevision', [ $title, &$revision ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.installer.beforecreatetablescomplete.md
	 * @since 3.2
	 *
	 * @param array $tables
	 * @param MessageReporter $messageReporter
	 */
	public function onInstallerBeforeCreateTablesComplete( array $tables, MessageReporter $messageReporter ) {
		$this->getHookContiner()
			->run( 'SMW::SQLStore::Installer::BeforeCreateTablesComplete', [ $tables, $messageReporter ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.installer.aftercreatetablescomplete.md
	 * @since 3.2
	 *
	 * @param TableBuilder $tableBuilder
	 * @param MessageReporter $messageReporter
	 * @param Options $options
	 */
	public function onInstallerAfterCreateTablesComplete( TableBuilder $tableBuilder, MessageReporter $messageReporter, Options $options ) {
		$this->getHookContiner()
			->run( 'SMW::SQLStore::Installer::AfterCreateTablesComplete', [ $tableBuilder, $messageReporter, $options ] );
	}

	/**
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.installer.afterdroptablescomplete.md
	 * @since 3.2
	 *
	 * @param TableBuilder $tableBuilder
	 * @param MessageReporter $messageReporter
	 * @param Options $options
	 */
	public function onInstallerAfterDropTablesComplete( TableBuilder $tableBuilder, MessageReporter $messageReporter, Options $options ) {
		$this->getHookContiner()
			->run( 'SMW::SQLStore::Installer::AfterDropTablesComplete', [ $tableBuilder, $messageReporter, $options ] );
	}

}
