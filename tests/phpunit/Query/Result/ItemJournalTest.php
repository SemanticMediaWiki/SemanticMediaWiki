<?php

namespace SMW\Tests\Query\Result;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Result\ItemJournal;

/**
 * @covers \SMW\Query\Result\ItemJournal
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ItemJournalTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ItemJournal::class,
			new ItemJournal()
		);
	}

	public function testRecordItem() {

		$dataItem = DIWikiPage::newFromText( 'Foo' );
		$instance = new ItemJournal();

		$instance->prune();
		$instance->recordItem( $dataItem );

		$this->assertEquals(
			[ 'Foo#0##' => $dataItem ],
			$instance->getEntityList( 'FOO:123' )
		);

		$instance->prune();

		$this->assertEmpty(
			$instance->getEntityList()
		);
	}

	public function testRecordProperty() {

		$property = new DIProperty( 'Bar' );
		$instance = new ItemJournal();

		$instance->recordProperty( $property );

		$this->assertEquals(
			[
				'Bar' => $property
			],
			$instance->getPropertyList()
		);
	}

}
