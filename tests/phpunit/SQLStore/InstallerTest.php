<?php

namespace SMW\Tests\SQLStore;

use Onoi\MessageReporter\MessageReporterFactory;
use SMW\MediaWiki\JobQueue;
use SMW\SQLStore\Installer;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\Installer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class InstallerTest extends \PHPUnit\Framework\TestCase {

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

		$this->tableSchemaManager = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\TableSchemaManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->tableBuildExaminer = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\TableBuildExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->versionExaminer = $this->getMockBuilder( '\SMW\SQLStore\Installer\VersionExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->tableOptimizer = $this->getMockBuilder( '\SMW\SQLStore\Installer\TableOptimizer' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->setupFile = $this->getMockBuilder( '\SMW\SetupFile' )
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
		$table = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\Table' )
			->disableOriginalConstructor()
			->getMock();

		$this->versionExaminer->expects( $this->atLeastOnce() )
			->method( 'meetsVersionMinRequirement' )
			->willReturn( true );

		$this->tableSchemaManager->expects( $this->atLeastOnce() )
			->method( 'getTables' )
			->willReturn( [ $table ] );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\TableBuilder' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'create' ] )
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

		$table = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\Table' )
			->disableOriginalConstructor()
			->getMock();

		$this->versionExaminer->expects( $this->atLeastOnce() )
			->method( 'meetsVersionMinRequirement' )
			->willReturn( true );

		$this->tableSchemaManager->expects( $this->atLeastOnce() )
			->method( 'getTables' )
			->willReturn( [ $table ] );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\TableBuilder' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'create' ] )
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
		$table = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\Table' )
			->disableOriginalConstructor()
			->getMock();

		$this->versionExaminer->expects( $this->atLeastOnce() )
			->method( 'meetsVersionMinRequirement' )
			->willReturn( true );

		$this->tableSchemaManager->expects( $this->atLeastOnce() )
			->method( 'getTables' )
			->willReturn( [ $table ] );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\TableBuilder' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'create' ] )
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
		$table = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\Table' )
			->disableOriginalConstructor()
			->getMock();

		$this->tableSchemaManager->expects( $this->once() )
			->method( 'getTables' )
			->willReturn( [ $table ] );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\TableBuilder' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'drop' ] )
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

		$callback = function () use( $instance ) {
			$instance->reportMessage( 'Foo' );
		};

		$this->assertEquals(
			'Foo',
			$this->testEnvironment->outputFromCallbackExec( $callback )
		);
	}

}
