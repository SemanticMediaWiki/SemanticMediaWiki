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

		$this->assertInstanceOf(
			'\SMW\SQLStore\Installer',
			new Installer( $tableSchemaManager )
		);
	}

	public function testInstall() {

		$tableSchemaManager = $this->getMockBuilder( '\SMW\SQLStore\TableSchemaManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Installer( $tableSchemaManager );
		$instance->setMessageReporter( $this->spyMessageReporter );

		$this->assertTrue(
			$instance->install()
		);
	}

	public function testInstallNonVerbose() {

		$tableSchemaManager = $this->getMockBuilder( '\SMW\SQLStore\TableSchemaManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Installer( $tableSchemaManager );
		$instance->setMessageReporter( $this->spyMessageReporter );

		$this->assertTrue(
			$instance->install( false )
		);
	}

	public function testUninstall() {

		$tableSchemaManager = $this->getMockBuilder( '\SMW\SQLStore\TableSchemaManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Installer( $tableSchemaManager );
		$instance->setMessageReporter( $this->spyMessageReporter );

		$this->assertTrue(
			$instance->uninstall()
		);
	}

	public function testReportMessage() {

		$tableSchemaManager = $this->getMockBuilder( '\SMW\SQLStore\TableSchemaManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Installer( $tableSchemaManager );

		$callback = function() use( $instance ) {
			$instance->reportMessage( 'Foo' );
		};

		$this->assertEquals(
			'Foo',
			$this->testEnvironment->executeAndFetchOutputBufferContents( $callback )
		);
	}

}
