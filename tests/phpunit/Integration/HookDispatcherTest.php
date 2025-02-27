<?php

namespace SMW\Tests\Integration;

use SMW\MediaWiki\HookDispatcher;
use SMW\Tests\TestEnvironment;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class HookDispatcherTest extends \PHPUnit\Framework\TestCase {

	private $mwHooksHandler;

	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->mwHooksHandler = $this->testEnvironment->getUtilityFactory()->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();
	}

	protected function tearDown(): void {
		$this->mwHooksHandler->restoreListedHooks();
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testOnSettingsBeforeInitializationComplete() {
		$configuration = [];

		$hookDispatcher = new HookDispatcher();

		$this->mwHooksHandler->register( 'SMW::Settings::BeforeInitializationComplete', static function ( &$configuration ) {
			$configuration = [ 'Foo' ];
		} );

		$hookDispatcher->onSettingsBeforeInitializationComplete( $configuration );

		$this->assertEquals(
			[ 'Foo' ],
			$configuration
		);
	}

	public function testOnSetupAfterInitializationComplete() {
		$vars = [];

		$hookDispatcher = new HookDispatcher();

		$this->mwHooksHandler->register( 'SMW::Setup::AfterInitializationComplete', static function ( &$vars ) {
			$vars = [ 'Foo' ];
		} );

		$hookDispatcher->onSetupAfterInitializationComplete( $vars );

		$this->assertEquals(
			[ 'Foo' ],
			$vars
		);
	}

	public function testOnGroupPermissionsBeforeInitializationComplete() {
		$permissions = [];

		$hookDispatcher = new HookDispatcher();

		$this->mwHooksHandler->register( 'SMW::GroupPermissions::BeforeInitializationComplete', static function ( &$permissions ) {
			$permissions = [ 'Foo' ];
		} );

		$hookDispatcher->onGroupPermissionsBeforeInitializationComplete( $permissions );

		$this->assertEquals(
			[ 'Foo' ],
			$permissions
		);
	}

	public function testOnRegisterTaskHandlers() {
		$hookDispatcher = new HookDispatcher();

		$taskHandlerRegistry = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\TaskHandlerRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$taskHandlerRegistry->expects( $this->once() )
			->method( 'registerTaskHandler' );

		$taskHandler = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\TaskHandler' )
			->disableOriginalConstructor()
			->getMock();

		$outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->mwHooksHandler->register( 'SMW::Admin::RegisterTaskHandlers', static function ( $taskHandlerRegistry, $store, $outputFormatter, $user ) use ( $taskHandler ) {
			$taskHandlerRegistry->registerTaskHandler( $taskHandler );
		} );

		$hookDispatcher->onRegisterTaskHandlers( $taskHandlerRegistry, $store, $outputFormatter, $user );
	}

	public function testOnRegisterPropertyChangeListeners() {
		$hookDispatcher = new HookDispatcher();

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$propertyChangeListener = $this->getMockBuilder( '\SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener' )
			->disableOriginalConstructor()
			->getMock();

		$propertyChangeListener->expects( $this->once() )
			->method( 'addListenerCallback' );

		$this->mwHooksHandler->register( 'SMW::Listener::ChangeListener::RegisterPropertyChangeListeners', static function ( $propertyChangeListener ) use ( $property ) {
			$propertyChangeListener->addListenerCallback( $property, static function (){
			} );
		} );

		$hookDispatcher->onRegisterPropertyChangeListeners( $propertyChangeListener );
	}

	public function testOnInitConstraints() {
		$hookDispatcher = new HookDispatcher();

		$constraintRegistry = $this->getMockBuilder( '\SMW\Constraint\ConstraintRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$constraintRegistry->expects( $this->once() )
			->method( 'registerConstraint' );

		$this->mwHooksHandler->register( 'SMW::Constraint::initConstraints', static function ( $constraintRegistry ) {
			$constraintRegistry->registerConstraint( 'foo', 'bar' );
		} );

		$hookDispatcher->onInitConstraints( $constraintRegistry );
	}

	public function testOnRegisterSchemaTypes() {
		$hookDispatcher = new HookDispatcher();

		$schemaTypes = $this->getMockBuilder( '\SMW\Schema\SchemaTypes' )
			->disableOriginalConstructor()
			->getMock();

		$schemaTypes->expects( $this->once() )
			->method( 'registerSchemaType' );

		$this->mwHooksHandler->register( 'SMW::Schema::RegisterSchemaTypes', static function ( $schemaTypes ) {
			$schemaTypes->registerSchemaType( 'Foo', [] );
		} );

		$hookDispatcher->onRegisterSchemaTypes( $schemaTypes );
	}

	public function testOnGetPreferences() {
		$preferences = [];

		$hookDispatcher = new HookDispatcher();

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$this->mwHooksHandler->register( 'SMW::GetPreferences', static function ( $user, &$preferences ) {
			$preferences = [ 'Foo' ];
		} );

		$hookDispatcher->onGetPreferences( $user, $preferences );

		$this->assertEquals(
			[ 'Foo' ],
			$preferences
		);
	}

	public function testOnBeforeMagicWordsFinder() {
		$magicWords = [];

		$hookDispatcher = new HookDispatcher();

		$this->mwHooksHandler->register( 'SMW::Parser::BeforeMagicWordsFinder', static function ( &$magicWords ) {
			$magicWords = [ 'Foo' ];
		} );

		$hookDispatcher->onBeforeMagicWordsFinder( $magicWords );

		$this->assertEquals(
			[ 'Foo' ],
			$magicWords
		);
	}

	public function testOnAfterLinksProcessingComplete() {
		$text = '';

		$hookDispatcher = new HookDispatcher();

		$annotationProcessor = $this->getMockBuilder( '\SMW\Parser\AnnotationProcessor' )
			->disableOriginalConstructor()
			->getMock();

		$this->mwHooksHandler->register( 'SMW::Parser::AfterLinksProcessingComplete', static function ( &$text, $annotationProcessor ) {
			$text = 'Foo';
		} );

		$hookDispatcher->onAfterLinksProcessingComplete( $text, $annotationProcessor );

		$this->assertEquals(
			'Foo',
			$text
		);
	}

	public function testOnParserAfterTidyPropertyAnnotationComplete() {
		$hookDispatcher = new HookDispatcher();

		$propertyAnnotator = $this->getMockBuilder( '\SMW\Property\Annotator' )
			->disableOriginalConstructor()
			->getMock();

		$propertyAnnotator->expects( $this->once() )
			->method( 'addAnnotation' );

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$this->mwHooksHandler->register( 'SMW::Parser::ParserAfterTidyPropertyAnnotationComplete', static function ( $propertyAnnotator, $parserOutput ) {
			$propertyAnnotator->addAnnotation();
		} );

		$hookDispatcher->onParserAfterTidyPropertyAnnotationComplete( $propertyAnnotator, $parserOutput );
	}

	public function testOnAfterUpdateEntityCollationComplete() {
		$hookDispatcher = new HookDispatcher();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$messageReporter = $this->getMockBuilder( '\Onoi\MessageReporter\MessageReporter' )
			->disableOriginalConstructor()
			->getMock();

		$messageReporter->expects( $this->once() )
			->method( 'reportMessage' );

		$this->mwHooksHandler->register( 'SMW::Maintenance::AfterUpdateEntityCollationComplete', static function ( $store, $messageReporter ) {
			$messageReporter->reportMessage( 'foo' );
		} );

		$hookDispatcher->onAfterUpdateEntityCollationComplete( $store, $messageReporter );
	}

	public function testOnRegisterEntityExaminerIndicatorProviders() {
		$hookDispatcher = new HookDispatcher();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$indicatorProviders = [];

		$this->mwHooksHandler->register( 'SMW::Indicator::EntityExaminer::RegisterIndicatorProviders', static function ( $store, &$indicatorProviders ) {
			$indicatorProviders[] = 'Foo';
		} );

		$hookDispatcher->onRegisterEntityExaminerIndicatorProviders( $store, $indicatorProviders );

		$this->assertEquals(
			[ 'Foo' ],
			$indicatorProviders
		);
	}

	public function testOnRegisterEntityExaminerDeferrableIndicatorProviders() {
		$hookDispatcher = new HookDispatcher();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$indicatorProviders = [];

		$this->mwHooksHandler->register( 'SMW::Indicator::EntityExaminer::RegisterDeferrableIndicatorProviders', static function ( $store, &$indicatorProviders ) {
			$indicatorProviders[] = 'Foo';
		} );

		$hookDispatcher->onRegisterEntityExaminerDeferrableIndicatorProviders( $store, $indicatorProviders );

		$this->assertEquals(
			[ 'Foo' ],
			$indicatorProviders
		);
	}

	public function testOnIsApprovedRevision() {
		$hookDispatcher = new HookDispatcher();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->mwHooksHandler->register( 'SMW::RevisionGuard::IsApprovedRevision', static function ( $title, $latestRevID ) {
			return $latestRevID == 9999 ? false : true;
		} );

		$this->assertFalse(
			$hookDispatcher->onIsApprovedRevision( $title, 9999 )
		);
	}

	public function testOnChangeRevisionID() {
		$hookDispatcher = new HookDispatcher();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$latestRevID = 9999;

		$this->mwHooksHandler->register( 'SMW::RevisionGuard::ChangeRevisionID', static function ( $title, &$latestRevID ) {
			$latestRevID = 1001;
		} );

		$hookDispatcher->onChangeRevisionID( $title, $latestRevID );

		$this->assertEquals(
			1001,
			$latestRevID
		);
	}

	public function testOnChangeFile() {
		$hookDispatcher = new HookDispatcher();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$file = $this->getMockBuilder( '\File' )
			->disableOriginalConstructor()
			->getMock();

		$anotherFile = $this->getMockBuilder( '\File' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertNotSame(
			$anotherFile,
			$file
		);

		$this->mwHooksHandler->register( 'SMW::RevisionGuard::ChangeFile', static function ( $title, &$file ) use ( $anotherFile ) {
			$file = $anotherFile;
		} );

		$hookDispatcher->onChangeFile( $title, $file );

		$this->assertSame(
			$anotherFile,
			$file
		);
	}

	public function testOnChangeRevision() {
		$hookDispatcher = new HookDispatcher();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$revision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
			->disableOriginalConstructor()
			->getMock();

		$anotherRevision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertNotSame(
			$revision,
			$anotherRevision
		);

		$this->mwHooksHandler->register( 'SMW::RevisionGuard::ChangeRevision', static function ( $title, &$revision ) use ( $anotherRevision ) {
			$revision = $anotherRevision;
		} );

		$hookDispatcher->onChangeRevision( $title, $revision );

		$this->assertSame(
			$anotherRevision,
			$revision
		);
	}

	public function testOnInstallerBeforeCreateTablesComplete() {
		$hookDispatcher = new HookDispatcher();

		$tables = [];

		$messageReporter = $this->getMockBuilder( '\Onoi\MessageReporter\MessageReporter' )
			->disableOriginalConstructor()
			->getMock();

		$messageReporter->expects( $this->once() )
			->method( 'reportMessage' );

		$this->mwHooksHandler->register( 'SMW::SQLStore::Installer::BeforeCreateTablesComplete', static function ( $tables, $messageReporter ) {
			$messageReporter->reportMessage( 'foo' );
		} );

		$hookDispatcher->onInstallerBeforeCreateTablesComplete( $tables, $messageReporter );
	}

	public function testOnInstallerAfterCreateTablesComplete() {
		$hookDispatcher = new HookDispatcher();

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$options = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$messageReporter = $this->getMockBuilder( '\Onoi\MessageReporter\MessageReporter' )
			->disableOriginalConstructor()
			->getMock();

		$messageReporter->expects( $this->once() )
			->method( 'reportMessage' );

		$this->mwHooksHandler->register( 'SMW::SQLStore::Installer::AfterCreateTablesComplete', static function ( $tableBuilder, $messageReporter, $options ) {
			$messageReporter->reportMessage( 'foo' );
		} );

		$hookDispatcher->onInstallerAfterCreateTablesComplete( $tableBuilder, $messageReporter, $options );
	}

	public function testOnInstallerAfterDropTablesComplete() {
		$hookDispatcher = new HookDispatcher();

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$options = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$messageReporter = $this->getMockBuilder( '\Onoi\MessageReporter\MessageReporter' )
			->disableOriginalConstructor()
			->getMock();

		$messageReporter->expects( $this->once() )
			->method( 'reportMessage' );

		$this->mwHooksHandler->register( 'SMW::SQLStore::Installer::AfterDropTablesComplete', static function ( $tableBuilder, $messageReporter, $options ) {
			$messageReporter->reportMessage( 'foo' );
		} );

		$hookDispatcher->onInstallerAfterDropTablesComplete( $tableBuilder, $messageReporter, $options );
	}

	public function testConfirmAllOnMethodsWereCalled() {
		// Expected class methods to be tested
		$classMethods = get_class_methods( HookDispatcher::class );

		// Match all "testOn" to the expected set of methods
		$testMethods = preg_grep( '/^testOn/', get_class_methods( $this ) );

		$testMethods = array_flip(
			str_replace( 'testOn', 'on', $testMethods )
		);

		foreach ( $classMethods as $name ) {
			$this->assertArrayHasKey(
				$name,
				$testMethods,
				"Failed to find a test for the `$name` listener!"
			);
		}
	}

}
