<?php

namespace SMW\Tests\Unit\SQLStore\TableBuilder\Examiner;

use PHPUnit\Framework\TestCase;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\Examiner\HashField;
use SMW\Tests\TestEnvironment;
use Wikimedia\Rdbms\ResultWrapper;

/**
 * @covers \SMW\SQLStore\TableBuilder\Examiner\HashField
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class HashFieldTest extends TestCase {

	private $spyMessageReporter;
	private $store;
	private $populateHashField;

	protected function setUp(): void {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->store = $this->getMockBuilder( SQLStore::class )
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
		$resultWrapper = $this->getMockBuilder( ResultWrapper::class )
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

		$this->assertStringContainsString(
			'Checking smw_hash field consistency',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testCheck_Incomplete() {
		$resultWrapper = $this->getMockBuilder( ResultWrapper::class )
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

		$this->assertStringContainsString(
			'Checking smw_hash field consistency',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
