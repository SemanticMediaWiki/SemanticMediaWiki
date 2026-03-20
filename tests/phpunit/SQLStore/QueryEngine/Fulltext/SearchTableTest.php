<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataItems\DataItem;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTable;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\SearchTable
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SearchTableTest extends TestCase {

	private $store;
	private $dataItemFactory;

	protected function setUp(): void {
		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SearchTable::class,
			new SearchTable( $this->store )
		);
	}

	public function testIsEnabled() {
		$instance = new SearchTable(
			$this->store
		);

		$instance->setEnabled( true );

		$this->assertTrue(
			$instance->isEnabled()
		);
	}

	public function testGetPropertyExemptionList() {
		$instance = new SearchTable(
			$this->store
		);

		$instance->setPropertyExemptionList(
			[ '_TEXT', 'fo oo' ]
		);

		$this->assertEquals(
			[ '_TEXT', 'fo_oo' ],
			$instance->getPropertyExemptionList()
		);
	}

	public function testIsExemptedProperty() {
		$instance = new SearchTable(
			$this->store
		);

		$instance->setIndexableDataTypes(
			SMW_FT_BLOB | SMW_FT_URI
		);

		$instance->setPropertyExemptionList(
			[ '_TEXT' ]
		);

		$property = $this->dataItemFactory->newDIProperty( '_TEXT' );

		$this->assertTrue(
			$instance->isExemptedProperty( $property )
		);

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_uri' );

		$this->assertFalse(
			$instance->isExemptedProperty( $property )
		);
	}

	public function testIsValidType() {
		$instance = new SearchTable(
			$this->store
		);

		$instance->setIndexableDataTypes(
			SMW_FT_BLOB | SMW_FT_URI
		);

		$this->assertTrue(
			$instance->isValidByType( DataItem::TYPE_BLOB )
		);

		$this->assertFalse(
			$instance->isValidByType( DataItem::TYPE_WIKIPAGE )
		);
	}

	public function testHasMinTokenLength() {
		$instance = new SearchTable(
			$this->store
		);

		$instance->setMinTokenSize( 4 );

		$this->assertFalse(
			$instance->hasMinTokenLength( 'bar' )
		);

		$this->assertFalse(
			$instance->hasMinTokenLength( 'テスト' )
		);

		$this->assertTrue(
			$instance->hasMinTokenLength( 'test' )
		);
	}

}
