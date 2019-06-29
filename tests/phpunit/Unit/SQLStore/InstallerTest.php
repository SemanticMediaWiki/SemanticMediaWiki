<?php

namespace SMW\Tests\SQLStore;

use Onoi\MessageReporter\MessageReporterFactory;
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
class InstallerTest extends \PHPUnit_Framework_TestCase {

	private $spyMessageReporter;
	private $testEnvironment;
	private $tableSchemaManager;
	private $tableBuilder;
	private $tableBuildExaminer;
	private $SetupFile;

	protected function setUp() {
		parent::setUp();
		$this->testEnvironment = new TestEnvironment();
		$this->spyMessageReporter = MessageReporterFactory::getInstance()->newSpyMessageReporter();

		$this->tableSchemaManager = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\TableSchemaManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->tableBuildExaminer = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\TableBuildExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->setupFile = $this->getMockBuilder( '\SMW\SetupFile' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Installer::class,
			new Installer( $this->tableSchemaManager, $this->tableBuilder, $this->tableBuildExaminer )
		);
	}

	public function testInstall() {

		$table = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\Table' )
			->disableOriginalConstructor()
			->getMock();

		$this->tableSchemaManager->expects( $this->atLeastOnce() )
			->method( 'getTables' )
			->will( $this->returnValue( [ $table ] ) );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\TableBuilder' )
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
			$this->tableBuildExaminer
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setSetupFile( $this->setupFile );

		$this->assertTrue(
			$instance->install()
		);
	}

	public function testInstallWithSupplementJobs() {

		$this->jobQueue->expects( $this->exactly( 2 ) )
			->method( 'push' );

		$table = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\Table' )
			->disableOriginalConstructor()
			->getMock();

		$this->tableSchemaManager->expects( $this->atLeastOnce() )
			->method( 'getTables' )
			->will( $this->returnValue( [ $table ] ) );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\TableBuilder' )
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
			$this->tableBuildExaminer
		);

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

		$this->tableSchemaManager->expects( $this->atLeastOnce() )
			->method( 'getTables' )
			->will( $this->returnValue( [ $table ] ) );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\TableBuilder' )
			->disableOriginalConstructor()
			->setMethods( [ 'create' ] )
			->getMockForAbstractClass();

		$instance = new Installer(
			$this->tableSchemaManager,
			$tableBuilder,
			$this->tableBuildExaminer
		);

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
			->will( $this->returnValue( [ $table ] ) );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\TableBuilder' )
			->disableOriginalConstructor()
			->setMethods( [ 'drop' ] )
			->getMockForAbstractClass();

		$tableBuilder->expects( $this->once() )
			->method( 'drop' );

		$instance = new Installer(
			$this->tableSchemaManager,
			$tableBuilder,
			$this->tableBuildExaminer
		);

		$instance->setMessageReporter( $this->spyMessageReporter );

		$this->assertTrue(
			$instance->uninstall()
		);
	}

	public function testReportMessage() {

		$instance = new Installer(
			$this->tableSchemaManager,
			$this->tableBuilder,
			$this->tableBuildExaminer
		);

		$callback = function() use( $instance ) {
			$instance->reportMessage( 'Foo' );
		};

		$this->assertEquals(
			'Foo',
			$this->testEnvironment->outputFromCallbackExec( $callback )
		);
	}

}
