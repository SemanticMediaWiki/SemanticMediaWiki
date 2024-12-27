<?php

namespace SMW\Tests\SQLStore\TableBuilder\Examiner;

use SMW\SQLStore\TableBuilder\Examiner\HashField;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

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

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->populateHashField = $this->getMockBuilder( '\SMW\Maintenance\populateHashField' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			HashField::class,
			new HashField( $this->store )
		);
	}

	public function testCheck_Populate() {
		$resultWrapper = $this->getMockBuilder( '\Wikimedia\Rdbms\ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

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
		$resultWrapper = $this->getMockBuilder( '\Wikimedia\Rdbms\ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

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
