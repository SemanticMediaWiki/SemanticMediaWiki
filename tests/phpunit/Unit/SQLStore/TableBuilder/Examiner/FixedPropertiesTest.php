<?php

namespace SMW\Tests\Unit\SQLStore\TableBuilder\Examiner;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\Examiner\FixedProperties;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\TableBuilder\Examiner\FixedProperties
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class FixedPropertiesTest extends TestCase {

	private $spyMessageReporter;
	private $store;
	private $connection;

	protected function setUp(): void {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( Database::class )
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
		$idTable = $this->getMockBuilder( EntityIdManager::class )
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

		$this->assertStringContainsString(
			'Checking selected fixed properties IDs',
			$expected
		);

		$this->assertStringContainsString(
			'moving from 99999 to 51',
			$expected
		);

		$this->assertStringContainsString(
			'moving from 11111 to 52',
			$expected
		);
	}

}
