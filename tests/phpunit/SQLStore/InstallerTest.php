<?php

namespace SMW\Tests\SQLStore;

use Onoi\MessageReporter\MessageReporterFactory;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\HookDispatcher;
use SMW\MediaWiki\JobQueue;
use SMW\SetupFile;
use SMW\SQLStore\Installer;
use SMW\SQLStore\Installer\TableOptimizer;
use SMW\SQLStore\Installer\VersionExaminer;
use SMW\SQLStore\TableBuilder\Table;
use SMW\SQLStore\TableBuilder\TableBuilder;
use SMW\SQLStore\TableBuilder\TableBuildExaminer;
use SMW\SQLStore\TableBuilder\TableSchemaManager;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\Installer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class InstallerTest extends TestCase {

	private $spyMessageReporter;
	private $testEnvironment;
	private $tableSchemaManager;
	private $tableBuilder;
	private $tableBuildExaminer;
	private $versionExaminer;
	private $tableOptimizer;
	private JobQueue $jobQueue;
	private $hookDispatcher;
	private $setupFile;

	protected function setUp(): void {
		parent::setUp();
		$this->testEnvironment = new TestEnvironment();
		$this->spyMessageReporter = MessageReporterFactory::getInstance()->newSpyMessageReporter();

		$this->tableSchemaManager = $this->getMockBuilder( TableSchemaManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->tableBuilder = $this->getMockBuilder( TableBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->tableBuildExaminer = $this->getMockBuilder( TableBuildExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->versionExaminer = $this->getMockBuilder( VersionExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->tableOptimizer = $this->getMockBuilder( TableOptimizer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->hookDispatcher = $this->getMockBuilder( HookDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$this->setupFile = $this->getMockBuilder( SetupFile::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );
	}

	public function testCanConstruct() {
		$instance = new Installer(
			$this->tableSchemaManager,
			$this->tableBuilder,
			$this->tableBuildExaminer,
			$this->versionExaminer,
			$this->tableOptimizer
		);

		$this->assertInstanceOf(
			Installer::class,
			$instance
		);
	}

	public function testInstall() {
		$table = $this->getMockBuilder( Table::class )
			->disableOriginalConstructor()
			->getMock();

		$this->versionExaminer->expects( $this->atLeastOnce() )
			->method( 'meetsVersionMinRequirement' )
			->willReturn( true );

		$this->tableSchemaManager->expects( $this->atLeastOnce() )
			->method( 'getTables' )
			->willReturn( [ $table ] );

		$tableBuilder = $this->getMockBuilder( TableBuilder::class )
			->disableOriginalConstructor()
			->setMethods( [ 'create' ] )
			->getMockForAbstractClass();

		$tableBuilder->expects( $this->once() )
			->method( 'create' );

		$this->tableBuildExaminer->expects( $this->once() )
			->method( 'checkOnPostCreation' );

		$instance = new Installer(
			$this->tableSchemaManager,
			$tableBuilder,
			$this->tableBuildExaminer,
			$this->versionExaminer,
			$this->tableOptimizer
		);

		$instance->setHookDispatcher( $this->hookDispatcher );
		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setSetupFile( $this->setupFile );

		$this->assertTrue(
			$instance->install()
		);
	}

	public function testInstall_FailsMinimumRequirement() {
		$this->versionExaminer->expects( $this->once() )
			->method( 'meetsVersionMinRequirement' )
			->willReturn( false );

		$instance = new Installer(
			$this->tableSchemaManager,
			$this->tableBuilder,
			$this->tableBuildExaminer,
			$this->versionExaminer,
			$this->tableOptimizer
		);

		$instance->setHookDispatcher( $this->hookDispatcher );
		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setSetupFile( $this->setupFile );

		$instance->install();
	}

	public function testInstallWithSupplementJobs() {
		$this->jobQueue->expects( $this->exactly( 2 ) )
			->method( 'push' );

		$table = $this->getMockBuilder( Table::class )
			->disableOriginalConstructor()
			->getMock();

		$this->versionExaminer->expects( $this->atLeastOnce() )
			->method( 'meetsVersionMinRequirement' )
			->willReturn( true );

		$this->tableSchemaManager->expects( $this->atLeastOnce() )
			->method( 'getTables' )
			->willReturn( [ $table ] );

		$tableBuilder = $this->getMockBuilder( TableBuilder::class )
			->disableOriginalConstructor()
			->setMethods( [ 'create' ] )
			->getMockForAbstractClass();

		$tableBuilder->expects( $this->once() )
			->method( 'create' );

		$this->tableBuildExaminer->expects( $this->once() )
			->method( 'checkOnPostCreation' );

		$instance = new Installer(
			$this->tableSchemaManager,
			$tableBuilder,
			$this->tableBuildExaminer,
			$this->versionExaminer,
			$this->tableOptimizer
		);

		$instance->setHookDispatcher( $this->hookDispatcher );
		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setSetupFile( $this->setupFile );

		$instance->setOptions(
			[
				Installer::OPT_SUPPLEMENT_JOBS => true
			]
		);

		$instance->install();
	}

	public function testInstallNonVerbose() {
		$table = $this->getMockBuilder( Table::class )
			->disableOriginalConstructor()
			->getMock();

		$this->versionExaminer->expects( $this->atLeastOnce() )
			->method( 'meetsVersionMinRequirement' )
			->willReturn( true );

		$this->tableSchemaManager->expects( $this->atLeastOnce() )
			->method( 'getTables' )
			->willReturn( [ $table ] );

		$tableBuilder = $this->getMockBuilder( TableBuilder::class )
			->disableOriginalConstructor()
			->setMethods( [ 'create' ] )
			->getMockForAbstractClass();

		$instance = new Installer(
			$this->tableSchemaManager,
			$tableBuilder,
			$this->tableBuildExaminer,
			$this->versionExaminer,
			$this->tableOptimizer
		);

		$instance->setHookDispatcher( $this->hookDispatcher );
		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setSetupFile( $this->setupFile );

		$this->assertTrue(
			$instance->install( false )
		);
	}

	public function testUninstall() {
		$table = $this->getMockBuilder( Table::class )
			->disableOriginalConstructor()
			->getMock();

		$this->tableSchemaManager->expects( $this->once() )
			->method( 'getTables' )
			->willReturn( [ $table ] );

		$tableBuilder = $this->getMockBuilder( TableBuilder::class )
			->disableOriginalConstructor()
			->setMethods( [ 'drop' ] )
			->getMockForAbstractClass();

		$tableBuilder->expects( $this->once() )
			->method( 'drop' );

		$instance = new Installer(
			$this->tableSchemaManager,
			$tableBuilder,
			$this->tableBuildExaminer,
			$this->versionExaminer,
			$this->tableOptimizer
		);

		$instance->setHookDispatcher( $this->hookDispatcher );
		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setSetupFile( $this->setupFile );

		$this->assertTrue(
			$instance->uninstall()
		);
	}

	public function testReportMessage() {
		$instance = new Installer(
			$this->tableSchemaManager,
			$this->tableBuilder,
			$this->tableBuildExaminer,
			$this->versionExaminer,
			$this->tableOptimizer
		);

		$callback = static function () use( $instance ) {
			$instance->reportMessage( 'Foo' );
		};

		$this->assertEquals(
			'Foo',
			$this->testEnvironment->outputFromCallbackExec( $callback )
		);
	}

}
