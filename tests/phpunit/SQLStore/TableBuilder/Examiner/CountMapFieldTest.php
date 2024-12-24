<?php

namespace SMW\Tests\SQLStore\TableBuilder\Examiner;

use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\TableBuilder\Examiner\CountMapField;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\TableBuilder\Examiner\CountMapField
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CountMapFieldTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $spyMessageReporter;
	private Database $connection;
	private $store;
	private $setupFile;

	protected function setUp(): void {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->setupFile = $this->getMockBuilder( '\SMW\SetupFile' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CountMapField::class,
			new CountMapField( $this->store )
		);
	}

	public function testCheck_NewFieldTriggerIncompleteTask() {
		$this->connection->expects( $this->once() )
			->method( 'tableName' )
			->willReturn( 'smw_objects_aux' );

		$instance = new CountMapField(
			$this->store
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setSetupFile( $this->setupFile );
		$instance->check( [ 'smw_objects_aux' => [ 'smw_countmap' => 'field.new' ] ] );

		$this->assertContains(
			'adding incomplete task for `smw_countmap` conversion',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testCheckOk() {
		$instance = new CountMapField(
			$this->store
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setSetupFile( $this->setupFile );
		$instance->check();

		$this->assertContains(
			'Checking smw_countmap field consistency',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
