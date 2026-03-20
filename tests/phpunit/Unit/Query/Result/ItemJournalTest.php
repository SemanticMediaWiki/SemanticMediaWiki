<?php

namespace SMW\Tests\Unit\Query\Result;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Query\Result\ItemJournal;

/**
 * @covers \SMW\Query\Result\ItemJournal
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class ItemJournalTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ItemJournal::class,
			new ItemJournal()
		);
	}

	public function testRecordItem() {
		$dataItem = WikiPage::newFromText( 'Foo' );
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
		$property = new Property( 'Bar' );
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
