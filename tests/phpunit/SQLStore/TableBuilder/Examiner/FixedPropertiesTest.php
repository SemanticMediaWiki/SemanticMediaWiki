<?php

namespace SMW\Tests\SQLStore\TableBuilder\Examiner;

use SMW\SQLStore\TableBuilder\Examiner\FixedProperties;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\TableBuilder\Examiner\FixedProperties
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class FixedPropertiesTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $spyMessageReporter;
	private $store;
	private $connection;

	protected function setUp(): void {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FixedProperties::class,
			new FixedProperties( $this->store )
		);
	}

	public function testCheck() {
		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'selectRow' )
			->willReturnOnConsecutiveCalls(
				(object)[ 'smw_id' => 99999 ],
				(object)[ 'smw_id' => 11111 ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$instance = new FixedProperties(
			$this->store
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setFixedProperties( [ '_FOO' => 51, '_BAR' => 52 ] );
		$instance->setProperties( [ '_FOO', '_BAR' ] );

		$instance->check();

		$expected = $this->spyMessageReporter->getMessagesAsString();

		$this->assertContains(
			'Checking selected fixed properties IDs',
			$expected
		);

		$this->assertContains(
			'moving from 99999 to 51',
			$expected
		);

		$this->assertContains(
			'moving from 11111 to 52',
			$expected
		);
	}

}
