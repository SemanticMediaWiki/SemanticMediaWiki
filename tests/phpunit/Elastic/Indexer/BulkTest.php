<?php

namespace SMW\Tests\Elastic\Indexer;

use SMW\Elastic\Indexer\Bulk;

/**
 * @covers \SMW\Elastic\Indexer\Bulk
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class BulkTest extends \PHPUnit\Framework\TestCase {

	private $client;

	protected function setUp(): void {
		$this->client = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Bulk::class,
			new Bulk( $this->client )
		);
	}

	public function testJsonSerialize() {
		$instance = new Bulk( $this->client );

		$this->assertEquals(
			'[]',
			$instance->jsonSerialize()
		);
	}

	public function testIsEmpty() {
		$instance = new Bulk( $this->client );
		$instance->clear();

		$this->assertTrue(
			$instance->isEmpty()
		);
	}

	public function testDelete() {
		$expected = [
			'body' => [ [ 'delete' => [ '_id' => 42, '_head_foo' => '_head_bar' ] ] ]
		];

		$this->client->expects( $this->once() )
			->method( 'bulk' )
			->with( $expected )
			->willReturn( [ 'response' ] );

		$instance = new Bulk( $this->client );

		$instance->head( [ '_head_foo' => '_head_bar' ] );
		$instance->delete( [ '_id' => 42 ] );

		$this->assertFalse(
			$instance->isEmpty()
		);

		$instance->execute();

		$this->assertTrue(
			$instance->isEmpty()
		);

		$this->assertEquals(
			[ 'response' ],
			$instance->getResponse()
		);
	}

	public function testIndex() {
		$expected = [
			'body' => [ [ 'index' => [ '_id' => 42, '_head_foo' => '_head_bar' ] ], [ '_source' ] ]
		];

		$this->client->expects( $this->once() )
			->method( 'bulk' )
			->with( $expected )
			->willReturn( [ 'response' ] );

		$instance = new Bulk( $this->client );

		$instance->head( [ '_head_foo' => '_head_bar' ] );
		$instance->index( [ '_id' => 42 ], [ '_source' ] );

		$this->assertFalse(
			$instance->isEmpty()
		);

		$instance->execute();

		$this->assertTrue(
			$instance->isEmpty()
		);
	}

	public function testUpsert() {
		$expected = [
			'body' => [
				[ 'update' => [ '_id' => 42, '_head_foo' => '_head_bar' ] ],
				[ 'doc' => [ '_doc_foo' ], 'doc_as_upsert' => true ]
			]
		];

		$this->client->expects( $this->once() )
			->method( 'bulk' )
			->with( $expected )
			->willReturn( [ 'response' ] );

		$instance = new Bulk( $this->client );

		$instance->head( [ '_head_foo' => '_head_bar' ] );
		$instance->upsert( [ '_id' => 42 ], [ '_doc_foo' ] );

		$this->assertFalse(
			$instance->isEmpty()
		);

		$instance->execute();

		$this->assertTrue(
			$instance->isEmpty()
		);
	}

	public function testInfuseDocument_Insert() {
		$subDocument = $this->getMockBuilder( '\SMW\Elastic\Indexer\Document' )
			->disableOriginalConstructor()
			->getMock();

		$subDocument->expects( $this->once() )
			->method( 'getPriorityDeleteList' )
			->willReturn( [] );

		$subDocument->expects( $this->once() )
			->method( 'getSubDocuments' )
			->willReturn( [] );

		$document = $this->getMockBuilder( '\SMW\Elastic\Indexer\Document' )
			->disableOriginalConstructor()
			->getMock();

		$document->expects( $this->once() )
			->method( 'getId' )
			->willReturn( 42 );

		$document->expects( $this->once() )
			->method( 'getData' )
			->willReturn( [ '_document_data' => 'abc' ] );

		$document->expects( $this->once() )
			->method( 'getPriorityDeleteList' )
			->willReturn( [] );

		$document->expects( $this->once() )
			->method( 'getSubDocuments' )
			->willReturn( [ $subDocument ] );

		$document->expects( $this->any() )
			->method( 'isType' )
			->withConsecutive( [ 'type/delete' ], [ 'type/upsert' ], [ 'type/insert' ] )
			->willReturnOnConsecutiveCalls( false, false, true );

		$expected = [
			'body' => [
				[ 'index' => [ '_id' => 42, '_head_foo' => '_head_bar' ] ],
				[ '_document_data' => 'abc' ]
			]
		];

		$this->client->expects( $this->once() )
			->method( 'bulk' )
			->with( $expected )
			->willReturn( [ 'response' ] );

		$instance = new Bulk( $this->client );

		$instance->head( [ '_head_foo' => '_head_bar' ] );
		$instance->infuseDocument( $document );

		$instance->execute();
	}

	public function testInfuseDocument_Delete_Index() {
		$subDocument = $this->getMockBuilder( '\SMW\Elastic\Indexer\Document' )
			->disableOriginalConstructor()
			->getMock();

		$subDocument->expects( $this->once() )
			->method( 'getPriorityDeleteList' )
			->willReturn( [] );

		$subDocument->expects( $this->once() )
			->method( 'getSubDocuments' )
			->willReturn( [] );

		$document = $this->getMockBuilder( '\SMW\Elastic\Indexer\Document' )
			->disableOriginalConstructor()
			->getMock();

		$document->expects( $this->any() )
			->method( 'getId' )
			->willReturnOnConsecutiveCalls( 42, 1001 );

		$document->expects( $this->once() )
			->method( 'getData' )
			->willReturn( [ '_document_data' => 'abc' ] );

		$document->expects( $this->once() )
			->method( 'getPriorityDeleteList' )
			->willReturn( [] );

		$document->expects( $this->once() )
			->method( 'getSubDocuments' )
			->willReturn( [ $subDocument ] );

		$document->expects( $this->any() )
			->method( 'isType' )
			->withConsecutive( [ 'type/delete' ], [ 'type/upsert' ], [ 'type/insert' ] )
			->willReturnOnConsecutiveCalls( true, false, true );

		$expected = [
			'body' => [
				[ 'delete' => [ '_id' => 42, '_head_foo' => '_head_bar' ] ],
				[ 'index' => [ '_id' => 1001, '_head_foo' => '_head_bar' ] ],
				[ '_document_data' => 'abc' ]
			]
		];

		$this->client->expects( $this->once() )
			->method( 'bulk' )
			->with( $expected )
			->willReturn( [ 'response' ] );

		$instance = new Bulk( $this->client );

		$instance->head( [ '_head_foo' => '_head_bar' ] );
		$instance->infuseDocument( $document );

		$instance->execute();
	}

	public function testInfuseDocument_Delete_Upsert_Index() {
		$subDocument = $this->getMockBuilder( '\SMW\Elastic\Indexer\Document' )
			->disableOriginalConstructor()
			->getMock();

		$subDocument->expects( $this->once() )
			->method( 'getPriorityDeleteList' )
			->willReturn( [] );

		$subDocument->expects( $this->once() )
			->method( 'getSubDocuments' )
			->willReturn( [] );

		$document = $this->getMockBuilder( '\SMW\Elastic\Indexer\Document' )
			->disableOriginalConstructor()
			->getMock();

		$document->expects( $this->any() )
			->method( 'getId' )
			->willReturnOnConsecutiveCalls( 42, 1001, 9001 );

		$document->expects( $this->any() )
			->method( 'getData' )
			->willReturnOnConsecutiveCalls( [ '_document_data_1' => '_1' ], [ '_document_data_2' => '_2' ] );

		$document->expects( $this->once() )
			->method( 'getPriorityDeleteList' )
			->willReturn( [ 7001, 7002 ] );

		$document->expects( $this->once() )
			->method( 'getSubDocuments' )
			->willReturn( [ $subDocument ] );

		$document->expects( $this->any() )
			->method( 'isType' )
			->withConsecutive( [ 'type/delete' ], [ 'type/upsert' ], [ 'type/insert' ] )
			->willReturnOnConsecutiveCalls( true, true, true );

		$expected = [
			'body' => [
				[ 'delete' => [ '_id' => 42, '_head_foo' => '_head_bar' ] ],
				[ 'delete' => [ '_id' => 7001, '_head_foo' => '_head_bar' ] ],
				[ 'delete' => [ '_id' => 7002, '_head_foo' => '_head_bar' ] ],
				[ 'update' => [ '_id' => 1001, '_head_foo' => '_head_bar' ] ],
				[ 'doc' => [ '_document_data_1' => '_1' ], 'doc_as_upsert' => true ],
				[ 'index' => [ '_id' => 9001, '_head_foo' => '_head_bar' ] ],
				[ '_document_data_2' => '_2' ]
			]
		];

		$this->client->expects( $this->once() )
			->method( 'bulk' )
			->with( $expected )
			->willReturn( [ 'response' ] );

		$instance = new Bulk( $this->client );

		$instance->head( [ '_head_foo' => '_head_bar' ] );
		$instance->infuseDocument( $document );

		$instance->execute();
	}

}
