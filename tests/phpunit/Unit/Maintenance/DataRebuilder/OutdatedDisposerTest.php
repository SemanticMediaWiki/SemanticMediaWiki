<?php

namespace SMW\Tests\Unit\Maintenance\DataRebuilder;

use PHPUnit\Framework\TestCase;
use SMW\IteratorFactory;
use SMW\Iterators\ChunkedIterator;
use SMW\Iterators\ResultIterator;
use SMW\Maintenance\DataRebuilder\OutdatedDisposer;
use SMW\MediaWiki\Jobs\EntityIdDisposerJob;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\IteratorMockBuilder;
use stdClass;

/**
 * @covers \SMW\Maintenance\DataRebuilder\OutdatedDisposer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class OutdatedDisposerTest extends TestCase {

	private $spyMessageReporter;
	private $entityIdDisposerJob;
	private $iteratorFactory;
	private $iteratorMockBuilder;
	private $resultIterator;

	protected function setUp(): void {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->entityIdDisposerJob = $this->getMockBuilder( EntityIdDisposerJob::class )
			->disableOriginalConstructor()
			->getMock();

		$this->iteratorFactory = $this->getMockBuilder( IteratorFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->iteratorMockBuilder = new IteratorMockBuilder();

		$this->resultIterator = $this->getMockBuilder( ResultIterator::class )
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
		$row = new stdClass;
		$row->smw_id = 1001;

		$chunkedIterator = $this->iteratorMockBuilder->setClass( ChunkedIterator::class )
			->with( [ [ $row ] ] )
			->getMockForIterator();

		$resultIterator = $this->getMockBuilder( ResultIterator::class )
			->disableOriginalConstructor()
			->getMock();

		$resultIterator->expects( $this->exactly( 2 ) )
			->method( 'count' )
			->willReturn( 42 );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newOutdatedEntitiesResultIterator' )
			->willReturn( $resultIterator );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newByNamespaceInvalidEntitiesResultIterator' )
			->willReturn( $resultIterator );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newOutdatedQueryLinksResultIterator' )
			->willReturn( $this->resultIterator );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newUnassignedQueryLinksResultIterator' )
			->willReturn( $this->resultIterator );

		$this->entityIdDisposerJob->expects( $this->exactly( 2 ) )
			->method( 'dispose' );

		$this->iteratorFactory->expects( $this->exactly( 2 ) )
			->method( 'newChunkedIterator' )
			->willReturn( $chunkedIterator );

		$instance = new OutdatedDisposer(
			$this->entityIdDisposerJob,
			$this->iteratorFactory
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->run();

		$messages = $this->spyMessageReporter->getMessagesAsString();

		$this->assertStringContainsString(
			'removed (IDs)',
			$messages
		);

		$this->assertStringContainsString(
			'42',
			$messages
		);

		$this->assertStringContainsString(
			'1001 (2%)',
			$messages
		);
	}

	public function testDispose_QueryLinks_Invalid() {
		$row = new stdClass;
		$row->id = 1002;

		$resultIterator = $this->iteratorMockBuilder->setClass( ResultIterator::class )
			->with( [ $row ] )
			->incrementInvokedCounterBy( 1 )
			->getMockForIterator();

		$resultIterator->expects( $this->once() )
			->method( 'count' )
			->willReturn( 9999 );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newOutdatedQueryLinksResultIterator' )
			->willReturn( $resultIterator );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newOutdatedEntitiesResultIterator' )
			->willReturn( $this->resultIterator );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newByNamespaceInvalidEntitiesResultIterator' )
			->willReturn( $this->resultIterator );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newUnassignedQueryLinksResultIterator' )
			->willReturn( $this->resultIterator );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'disposeQueryLinks' );

		$instance = new OutdatedDisposer(
			$this->entityIdDisposerJob,
			$this->iteratorFactory
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->run();

		$messages = $this->spyMessageReporter->getMessagesAsString();

		$this->assertStringContainsString(
			'removed (IDs)',
			$messages
		);

		$this->assertStringContainsString(
			'9999',
			$messages
		);

		$this->assertStringContainsString(
			'cleaning up query links (invalid)',
			$messages
		);

		$this->assertStringContainsString(
			'1002 (0%)',
			$messages
		);
	}

	public function testDispose_QueryLinks_Unassigned() {
		$row = new stdClass;
		$row->id = 3333;

		$resultIterator = $this->iteratorMockBuilder->setClass( ResultIterator::class )
			->with( [ $row ] )
			->incrementInvokedCounterBy( 1 )
			->getMockForIterator();

		$resultIterator->expects( $this->once() )
			->method( 'count' )
			->willReturn( 10 );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newUnassignedQueryLinksResultIterator' )
			->willReturn( $resultIterator );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newOutdatedEntitiesResultIterator' )
			->willReturn( $this->resultIterator );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newByNamespaceInvalidEntitiesResultIterator' )
			->willReturn( $this->resultIterator );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'newOutdatedQueryLinksResultIterator' )
			->willReturn( $this->resultIterator );

		$this->entityIdDisposerJob->expects( $this->once() )
			->method( 'disposeQueryLinks' );

		$instance = new OutdatedDisposer(
			$this->entityIdDisposerJob,
			$this->iteratorFactory
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->run();

		$messages = $this->spyMessageReporter->getMessagesAsString();

		$this->assertStringContainsString(
			'removed (IDs)',
			$messages
		);

		$this->assertStringContainsString(
			'10',
			$messages
		);

		$this->assertStringContainsString(
			'cleaning up query links (unassigned)',
			$messages
		);

		$this->assertStringContainsString(
			'3333 (10%)',
			$messages
		);
	}

}
