<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\DataItemFactory;
use SMW\RequestOptions;
use SMW\SQLStore\Lookup\PropertyLabelSimilarityLookup;

/**
 * @covers \SMW\SQLStore\Lookup\PropertyLabelSimilarityLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class PropertyLabelSimilarityLookupTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $propertyStatisticsStore;
	private $requestOptions;
	private $dataItemFactory;

	protected function setUp() {

		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestOptions = $this->getMockBuilder( '\SMW\RequestOptions' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\PropertyLabelSimilarityLookup',
			new PropertyLabelSimilarityLookup( $this->store )
		);
	}

	public function testGetPropertyMaxCount() {

		$this->store->expects( $this->any() )
			->method( 'getStatistics' )
			->will( $this->returnValue( [ 'TOTALPROPS' => 42 ] ) );

		$propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyLabelSimilarityLookup(
			$this->store,
			$propertySpecificationLookup
		);

		$this->assertEquals(
			42,
			$instance->getPropertyMaxCount()
		);
	}

	public function testCompareAndFindLabels() {

		$row = new \stdClass;
		$row->smw_title = 'Foo';

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( [ $row ] ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyLabelSimilarityLookup(
			$this->store,
			$propertySpecificationLookup
		);

		$requestOptions = new RequestOptions();

		$instance->compareAndFindLabels( $requestOptions );

		$this->assertEquals(
			1,
			$instance->getLookupCount()
		);
	}

	public function testCompareAndFindLabelsWithExemption() {

		$row1 = new \stdClass;
		$row1->smw_title = 'Foo';

		$row2 = new \stdClass;
		$row2->smw_title = 'Foobar';

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( [ $row1, $row2 ] ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$propertySpecificationLookup->expects( $this->any() )
			->method( 'getSpecification' )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIWikiPage( 'Foobar', SMW_NS_PROPERTY ) ] ) );

		$instance = new PropertyLabelSimilarityLookup(
			$this->store,
			$propertySpecificationLookup
		);

		$requestOptions = new RequestOptions();

		$instance->setExemptionProperty( 'Bar' );
		$instance->setThreshold( 10 );

		$this->assertInternalType(
			'array',
			$instance->compareAndFindLabels( $requestOptions )
		);
	}

}
