<?php

namespace SMW\Tests\Maintenance\DataRebuilder;

use SMW\Maintenance\DataRebuilder\OutdatedDisposer;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\IteratorMockBuilder;

/**
 * @covers \SMW\Maintenance\DataRebuilder\OutdatedDisposer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class OutdatedDisposerTest extends \PHPUnit_Framework_TestCase {

	private $spyMessageReporter;
	private $entityIdDisposerJob;
	private $iteratorFactory;
	private $iteratorMockBuilder;
	private $resultIterator;

	protected function setUp() {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->entityIdDisposerJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\EntityIdDisposerJob' )
			->disableOriginalConstructor()
			->getMock();

		$this->iteratorFactory = $this->getMockBuilder( '\SMW\IteratorFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->iteratorMockBuilder = new IteratorMockBuilder();

		$this->resultIterator = $this->getMockBuilder( '\SMW\Iterators\ResultIterator' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			OutdatedDisposer::class,
			new OutdatedDisposer( $this->entityIdDisposerJob, $this->iteratorFactory )
		);
	}

	public function testDispose_Entities() {

		$row = new \stdClass;
		$row->smw_id = 1001;

		$chunkedIterator = $this->iteratorMockBuilder->setClass( '\SMW\Iterators\ChunkedIterator' )
			->with( [ [ $row ] ] )
			->getMockForIterator();

		$resultIterator = $this->getMockBuilder( '\SMW\Iterators\ResultIterator' )
			->disableOriginalConstructor()
			->getMock();

		$resultIterator->expects( $this->once() )
			->method( 'count' )
			->will( $this->returnValue( 42 ) );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newOutdatedEntitiesResultIterator' )
			->will( $this->returnValue( $resultIterator ) );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newOutdatedQueryLinksResultIterator' )
			->will( $this->returnValue( $this->resultIterator ) );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newUnassignedQueryLinksResultIterator' )
			->will( $this->returnValue( $this->resultIterator ) );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'dispose' );

		$this->iteratorFactory->expects( $this->once() )
			->method( 'newChunkedIterator' )
			->will( $this->returnValue( $chunkedIterator ) );

		$instance = new OutdatedDisposer(
			$this->entityIdDisposerJob,
			$this->iteratorFactory
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->run();

		$messages = $this->spyMessageReporter->getMessagesAsString();

		$this->assertContains(
			'42 IDs removed',
			$messages
		);

		$this->assertContains(
			'1001 (2%)',
			$messages
		);
	}

	public function testDispose_QueryLinks_Invalid() {

		$row = new \stdClass;
		$row->id = 1002;

		$resultIterator = $this->iteratorMockBuilder->setClass( '\SMW\Iterators\ResultIterator' )
			->with( [ $row ] )
			->incrementInvokedCounterBy( 1 )
			->getMockForIterator();

		$resultIterator->expects( $this->once() )
			->method( 'count' )
			->will( $this->returnValue( 9999 ) );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newOutdatedQueryLinksResultIterator' )
			->will( $this->returnValue( $resultIterator ) );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newOutdatedEntitiesResultIterator' )
			->will( $this->returnValue( $this->resultIterator ) );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newUnassignedQueryLinksResultIterator' )
			->will( $this->returnValue( $this->resultIterator ) );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'disposeQueryLinks' );

		$instance = new OutdatedDisposer(
			$this->entityIdDisposerJob,
			$this->iteratorFactory
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->run();

		$messages = $this->spyMessageReporter->getMessagesAsString();

		$this->assertContains(
			'9999 IDs removed',
			$messages
		);

		$this->assertContains(
			'cleaning up query links (invalid)      1002 (0%)',
			$messages
		);
	}

	public function testDispose_QueryLinks_Unassigned() {

		$row = new \stdClass;
		$row->id = 3333;

		$resultIterator = $this->iteratorMockBuilder->setClass( '\SMW\Iterators\ResultIterator' )
			->with( [ $row ] )
			->incrementInvokedCounterBy( 1 )
			->getMockForIterator();

		$resultIterator->expects( $this->once() )
			->method( 'count' )
			->will( $this->returnValue( 10 ) );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newUnassignedQueryLinksResultIterator' )
			->will( $this->returnValue( $resultIterator ) );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newOutdatedEntitiesResultIterator' )
			->will( $this->returnValue( $this->resultIterator ) );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newOutdatedQueryLinksResultIterator' )
			->will( $this->returnValue( $this->resultIterator ) );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'disposeQueryLinks' );

		$instance = new OutdatedDisposer(
			$this->entityIdDisposerJob,
			$this->iteratorFactory
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->run();

		$messages = $this->spyMessageReporter->getMessagesAsString();

		$this->assertContains(
			'10 IDs removed',
			$messages
		);

		$this->assertContains(
			'cleaning up query links (unassigned)   3333 (10%)',
			$messages
		);
	}

}
