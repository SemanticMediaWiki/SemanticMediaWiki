<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use SMW\SQLStore\QueryEngine\Fulltext\SearchTable;
use SMW\DataItemFactory;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\SearchTable
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SearchTableTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $dataItemFactory;

	protected function setUp() {

		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\SearchTable',
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
			array( '_TEXT','fo oo' )
		);

		$this->assertEquals(
			array( '_TEXT', 'fo_oo' ),
			$instance->getPropertyExemptionList()
		);
	}

	public function testIsExemptedProperty() {

		$instance = new SearchTable(
			$this->store
		);

		$instance->setPropertyExemptionList(
			array( '_TEXT' )
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

}
