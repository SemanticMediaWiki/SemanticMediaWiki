<?php

namespace SMW\Tests\Query\Result;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Query\Result\InMemoryEntityProcessList;

/**
 * @covers \SMW\Query\Result\InMemoryEntityProcessList
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class InMemoryEntityProcessListTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			InMemoryEntityProcessList::class,
			new InMemoryEntityProcessList()
		);
	}

	public function testAddDataItem() {

		$dataItem = DIWikiPage::newFromText( 'Foo' );
		$instance = new InMemoryEntityProcessList();

		$instance->prune();
		$instance->addDataItem( $dataItem );

		$this->assertEquals(
			array( 'Foo#0#' => $dataItem ),
			$instance->getEntityList( 'FOO:123' )
		);

		$instance->prune();

		$this->assertEmpty(
			$instance->getEntityList()
		);
	}

	public function testAddProperty() {

		$property = new DIProperty( 'Bar' );
		$instance = new InMemoryEntityProcessList();

		$instance->addProperty( $property );

		$this->assertEquals(
			array(
				'Bar' => $property
			),
			$instance->getPropertyList()
		);
	}

}
