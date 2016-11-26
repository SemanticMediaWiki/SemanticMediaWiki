<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\Installer;
use Onoi\MessageReporter\MessageReporterFactory;
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

	protected function setUp() {
		parent::setUp();
		$this->testEnvironment = new TestEnvironment();
		$this->spyMessageReporter = MessageReporterFactory::getInstance()->newSpyMessageReporter();
	}

	public function testCanConstruct() {

		$tableSchemaManager = $this->getMockBuilder( '\SMW\SQLStore\TableSchemaManager' )
			->disableOriginalConstructor()
			->getMock();

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$tableIntegrityExaminer = $this->getMockBuilder( '\SMW\SQLStore\TableIntegrityExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\Installer',
			new Installer( $tableSchemaManager, $tableBuilder, $tableIntegrityExaminer )
		);
	}

	public function testInstall() {

		$table = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\Table' )
			->disableOriginalConstructor()
			->getMock();

		$tableSchemaManager = $this->getMockBuilder( '\SMW\SQLStore\TableSchemaManager' )
			->disableOriginalConstructor()
			->getMock();

		$tableSchemaManager->expects( $this->once() )
			->method( 'getTables' )
			->will( $this->returnValue( array( $table ) ) );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$tableBuilder->expects( $this->once() )
			->method( 'create' );

		$tableIntegrityExaminer = $this->getMockBuilder( '\SMW\SQLStore\TableIntegrityExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$tableIntegrityExaminer->expects( $this->once() )
			->method( 'checkOnPostCreation' );

		$instance = new Installer(
			$tableSchemaManager,
			$tableBuilder,
			$tableIntegrityExaminer
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->isFromExtensionSchemaUpdate( true );

		$this->assertTrue(
			$instance->install()
		);
	}

	public function testInstallNonVerbose() {

		$table = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\Table' )
			->disableOriginalConstructor()
			->getMock();

		$tableSchemaManager = $this->getMockBuilder( '\SMW\SQLStore\TableSchemaManager' )
			->disableOriginalConstructor()
			->getMock();

		$tableSchemaManager->expects( $this->once() )
			->method( 'getTables' )
			->will( $this->returnValue( array( $table ) ) );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$tableIntegrityExaminer = $this->getMockBuilder( '\SMW\SQLStore\TableIntegrityExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Installer(
			$tableSchemaManager,
			$tableBuilder,
			$tableIntegrityExaminer
		);

		$instance->setMessageReporter( $this->spyMessageReporter );

		$this->assertTrue(
			$instance->install( false )
		);
	}

	public function testUninstall() {

		$table = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\Table' )
			->disableOriginalConstructor()
			->getMock();

		$tableSchemaManager = $this->getMockBuilder( '\SMW\SQLStore\TableSchemaManager' )
			->disableOriginalConstructor()
			->getMock();

		$tableSchemaManager->expects( $this->once() )
			->method( 'getTables' )
			->will( $this->returnValue( array( $table ) ) );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$tableBuilder->expects( $this->once() )
			->method( 'drop' );

		$tableIntegrityExaminer = $this->getMockBuilder( '\SMW\SQLStore\TableIntegrityExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Installer(
			$tableSchemaManager,
			$tableBuilder,
			$tableIntegrityExaminer
		);

		$instance->setMessageReporter( $this->spyMessageReporter );

		$this->assertTrue(
			$instance->uninstall()
		);
	}

	public function testReportMessage() {

		$tableSchemaManager = $this->getMockBuilder( '\SMW\SQLStore\TableSchemaManager' )
			->disableOriginalConstructor()
			->getMock();

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$tableIntegrityExaminer = $this->getMockBuilder( '\SMW\SQLStore\TableIntegrityExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Installer(
			$tableSchemaManager,
			$tableBuilder,
			$tableIntegrityExaminer
		);

		$callback = function() use( $instance ) {
			$instance->reportMessage( 'Foo' );
		};

		$this->assertEquals(
			'Foo',
			$this->testEnvironment->executeAndFetchOutputBufferContents( $callback )
		);
	}

}
