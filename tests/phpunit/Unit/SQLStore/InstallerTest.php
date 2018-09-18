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
	private $tableIntegrityExaminer;

	protected function setUp() {
		parent::setUp();
		$this->testEnvironment = new TestEnvironment();
		$this->spyMessageReporter = MessageReporterFactory::getInstance()->newSpyMessageReporter();

		$this->tableSchemaManager = $this->getMockBuilder( '\SMW\SQLStore\TableSchemaManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->tableIntegrityExaminer = $this->getMockBuilder( '\SMW\SQLStore\TableIntegrityExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Installer::class,
			new Installer( $this->tableSchemaManager, $this->tableBuilder, $this->tableIntegrityExaminer )
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

		$this->tableIntegrityExaminer->expects( $this->once() )
			->method( 'checkOnPostCreation' );

		$instance = new Installer(
			$this->tableSchemaManager,
			$tableBuilder,
			$this->tableIntegrityExaminer
		);

		$instance->setMessageReporter( $this->spyMessageReporter );

		$instance->setOptions(
			[
				Installer::OPT_SCHEMA_UPDATE => false
			]
		);

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

		$this->tableIntegrityExaminer->expects( $this->once() )
			->method( 'checkOnPostCreation' );

		$instance = new Installer(
			$this->tableSchemaManager,
			$tableBuilder,
			$this->tableIntegrityExaminer
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
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
			$this->tableIntegrityExaminer
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
			$this->tableIntegrityExaminer
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
			$this->tableIntegrityExaminer
		);

		$callback = function() use( $instance ) {
			$instance->reportMessage( 'Foo' );
		};

		$this->assertEquals(
			'Foo',
			$this->testEnvironment->outputFromCallbackExec( $callback )
		);
	}

	public function testIsGoodSchema() {

		$instance = new Installer(
			$this->tableSchemaManager,
			$this->tableBuilder,
			$this->tableIntegrityExaminer
		);

		$this->assertInternalType(
			'boolean',
			$instance->isGoodSchema()
		);
	}

	public function testGetUpgradeKey() {

		$var1 = [
			'smwgUpgradeKey' => '',
			'smwgFixedProperties' => [ 'Foo', 'Bar' ],
			'smwgPageSpecialProperties' => [ 'Foo', 'Bar' ]
		];

		$var2 = [
			'smwgUpgradeKey' => '',
			'smwgFixedProperties' => [ 'Bar', 'Foo' ],
			'smwgPageSpecialProperties' => [ 'Bar', 'Foo' ]
		];

		$this->assertEquals(
			Installer::getUpgradeKey( $var1 ),
			Installer::getUpgradeKey( $var2 )
		);
	}

	public function testGetUpgradeKey_SpecialFixedProperties() {

		$var1 = [
			'smwgUpgradeKey' => '',
			'smwgFixedProperties' => [ 'Foo', 'Bar' ],
			'smwgPageSpecialProperties' => [ 'Foo', 'Bar' ]
		];

		$var2 = [
			'smwgUpgradeKey' => '',
			'smwgFixedProperties' => [ 'Bar', 'Foo' ],
			'smwgPageSpecialProperties' => [ 'Bar', '_MDAT' ]
		];

		$this->assertNotEquals(
			Installer::getUpgradeKey( $var1 ),
			Installer::getUpgradeKey( $var2 )
		);
	}

	public function testSetUpgradeKey() {

		$file = $this->getMockBuilder( '\SMW\Utils\File' )
			->disableOriginalConstructor()
			->getMock();

		$file->expects( $this->once() )
			->method( 'write' );

		$instance = new Installer(
			$this->tableSchemaManager,
			$this->tableBuilder,
			$this->tableIntegrityExaminer
		);

		$vars = [
			'smwgIP' => '',
			'smwgUpgradeKey' => '',
			'smwgFixedProperties' => [],
			'smwgPageSpecialProperties' => []
		];

		$instance->setUpgradeKey( $file, $vars, $this->spyMessageReporter );
	}

}
