<?php

namespace SMW\Tests\SQLStore\TableBuilder\Examiner;

use SMW\SQLStore\TableBuilder\Examiner\EntityCollation;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\TableBuilder\Examiner\EntityCollation
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class EntityCollationTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $spyMessageReporter;
	private $store;
	private $setupFile;

	protected function setUp(): void {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->setupFile = $this->getMockBuilder( '\SMW\SetupFile' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			EntityCollation::class,
			new EntityCollation( $this->store )
		);
	}

	public function testCheck_DifferentCollationTriggerIncompleteTask() {
		$this->setupFile->expects( $this->once() )
			->method( 'get' )
			->with( $this->stringContains( 'entity_collation' ) )
			->willReturn( 'foo' );

		$this->setupFile->expects( $this->once() )
			->method( 'addIncompleteTask' );

		$instance = new EntityCollation(
			$this->store
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setSetupFile( $this->setupFile );
		$instance->check();

		$this->assertContains(
			'adding incomplete task for entity collation conversion',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testCheck_Collection() {
		$this->setupFile->expects( $this->once() )
			->method( 'get' )
			->with( $this->stringContains( 'entity_collation' ) )
			->willReturn( 'foo' );

		$instance = new EntityCollation(
			$this->store
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setEntityCollation( 'foo' );

		$instance->setSetupFile( $this->setupFile );
		$instance->check();

		$this->assertContains(
			'Checking entity collation type',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
