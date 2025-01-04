<?php

namespace SMW\Tests\SQLStore\TableBuilder\Examiner;

use SMW\SQLStore\TableBuilder\Examiner\TouchedField;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\TableBuilder\Examiner\TouchedField
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TouchedFieldTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $spyMessageReporter;
	private $store;

	protected function setUp(): void {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TouchedField::class,
			new TouchedField( $this->store )
		);
	}

	public function testCheck() {
		$row = [
			'count' => 42
		];

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->willReturn( (object)$row );

		$connection->expects( $this->atLeastOnce() )
			->method( 'update' );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new TouchedField(
			$this->store
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->check();

		$this->assertContains(
			'updating 42 rows with',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
