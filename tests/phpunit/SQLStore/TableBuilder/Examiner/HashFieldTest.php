<?php

namespace SMW\Tests\SQLStore\TableBuilder\Examiner;

use SMW\Maintenance\populateHashField;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\Examiner\HashField;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * @covers \SMW\SQLStore\TableBuilder\Examiner\HashField
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class HashFieldTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $spyMessageReporter;
	private $store;
	private $populateHashField;

	protected function setUp(): void {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->store = $this->createMock( SQLStore::class );

		$this->populateHashField = $this->createMock( populateHashField::class );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			HashField::class,
			new HashField( $this->store )
		);
	}

	public function testCheck_Populate() {
		$resultWrapper = $this->createMock( IResultWrapper::class );

		$resultWrapper->expects( $this->once() )
			->method( 'numRows' )
			->willReturn( HashField::threshold() - 1 );

		$this->populateHashField->expects( $this->atLeastOnce() )
			->method( 'populate' );

		$this->populateHashField->expects( $this->once() )
			->method( 'fetchRows' )
			->willReturn( $resultWrapper );

		$instance = new HashField(
			$this->store,
			$this->populateHashField
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->check();

		$this->assertContains(
			'Checking smw_hash field consistency',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testCheck_Incomplete() {
		$resultWrapper = $this->createMock( IResultWrapper::class );

		$resultWrapper->expects( $this->once() )
			->method( 'numRows' )
			->willReturn( HashField::threshold() + 1 );

		$this->populateHashField->expects( $this->atLeastOnce() )
			->method( 'setComplete' );

		$this->populateHashField->expects( $this->once() )
			->method( 'fetchRows' )
			->willReturn( $resultWrapper );

		$instance = new HashField(
			$this->store,
			$this->populateHashField
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->check();

		$this->assertContains(
			'Checking smw_hash field consistency',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
