<?php

namespace SMW\Tests\Elastic\Indexer;

use SMW\Elastic\Indexer\Document;
use SMW\DIWikiPage;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Elastic\Indexer\Document
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class DocumentTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Document::class,
			new Document( 42 )
		);
	}

	public function testGetId() {

		$instance = new Document( 42, [] );

		$this->assertEquals(
			42,
			$instance->getId()
		);
	}

	public function testGetSubject() {

		$instance = new Document( 42, [ 'subject' => [ 'serialization' => 'Foo#0##' ] ] );

		$this->assertEquals(
			new DIWikiPage( 'Foo', NS_MAIN ),
			$instance->getSubject()
		);
	}

	public function testPriorityDeleteList() {

		$instance = new Document( 42, [] );
		$instance->setPriorityDeleteList( [ 1001, 1002 ] );

		$this->assertEquals(
			[ 1001, 1002 ],
			$instance->getPriorityDeleteList()
		);
	}

	public function testIsType_Default() {

		$instance = new Document( 42, [] );

		$this->assertTrue(
			$instance->isType( Document::TYPE_INSERT )
		);
	}

	public function testSetField() {

		$instance = new Document( 42, [] );
		$instance->setField( 'foo_field', 'bar_value' );

		$this->assertEquals(
			[ 'foo_field' => 'bar_value' ],
			$instance->getData()
		);
	}

	public function testSubDocument() {

		$subDocument = new Document( 1001 );

		$instance = new Document( 42 );
		$instance->addSubDocument( $subDocument );

		$this->assertFalse(
			$instance->hasSubDocumentById( 42 )
		);

		$this->assertTrue(
			$instance->hasSubDocumentById( 1001 )
		);

		$this->assertEquals(
			$subDocument,
			$instance->getSubDocumentById( 1001 )
		);

		$this->assertEquals(
			[ 1001 => $subDocument ],
			$instance->getSubDocuments()
		);
	}

	public function testTextBody() {

		$instance = new Document( 42 );
		$instance->setTextBody( 'some text' );

		$this->assertEquals(
			[ 'text_raw' => 'some text' ],
			$instance->getData()
		);
	}

	public function testJsonSerialize() {

		$instance = new Document( 42, [ 'subject' => [ 'serialization' => 'Foo#0##' ] ] );

		$this->assertEquals(
			'{"id":42,"type":"type\/insert","data":{"subject":{"serialization":"Foo#0##"}},"sub_docs":[]}',
			$instance->jsonSerialize()
		);
	}

	public function testAddSubDocument_ToArray() {

		$expected = [
			'id'   => 42,
			'type' => 'type/insert',
			'data' => [
				'foo' => 'bar',
				'text_raw' => 'Foobar'
			],
			'sub_docs' => [
				1001 => [
					'id' => 1001,
					'type' => 'type/insert',
					'data' => [ 'bar' => 9001 ],
					'sub_docs' => []
				],
				1002 => [
					'id' => 1002,
					'type' => 'type/insert',
					'data' => [ 'bar' => 9002 ],
					'sub_docs' => []
				]
			]
		];

		$instance = new Document( 42, [ 'foo' => 'bar' ] );
		$instance->setTextBody( 'Foobar' );

		$instance->addSubDocument( new Document( 1001, [ 'bar' => 9001 ] ) );
		$instance->addSubDocument( new Document( 1002, [ 'bar' => 9002 ] ) );

		$this->assertEquals(
			$expected,
			$instance->toArray()
		);
	}
}
