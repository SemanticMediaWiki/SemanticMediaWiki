<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SQLStore\EntityStore\PrefetchItemLookup;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\EntityStore\PrefetchItemLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class PrefetchItemLookupTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $semanticDataLookup;
	private $propertySubjectsLookup;
	private $requestOptions;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->semanticDataLookup = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\CachingSemanticDataLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertySubjectsLookup = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\PropertySubjectsLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestOptions = $this->getMockBuilder( '\SMW\RequestOptions' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			PrefetchItemLookup::class,
			new PrefetchItemLookup( $this->store, $this->semanticDataLookup, $this->propertySubjectsLookup )
		);
	}

	public function testGetPropertyValues() {

		$subjects = [
			DIWikiPage::newFromText( __METHOD__ ),
		];

		$entityIdManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->atLeastOnce() )
			->method( 'getSequenceMap' )
			->will( $this->returnValue( [] ) );

		$diHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMock();

		$diHandler->expects( $this->atLeastOnce() )
			->method( 'newFromDBKeys' )
			->will( $this->returnValue( DIWikiPage::newFromText( 'Bar' ) ) );

		$propertyTableDef = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ 'smw_foo' => $propertyTableDef ] ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $diHandler ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'smw_foo' ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $entityIdManager ) );

		$this->semanticDataLookup->expects( $this->atLeastOnce() )
			->method( 'prefetchDataFromTable' )
			->will( $this->returnValue( [ 42 => [ 'Bar#0##' ] ] ) );

		$instance = new PrefetchItemLookup(
			$this->store,
			$this->semanticDataLookup,
			$this->propertySubjectsLookup
		);

		$instance->getPropertyValues( $subjects, new DIProperty( 'Foo' ), $this->requestOptions );
	}

	public function testGetPropertyValues_InvertedProperty() {

		$subjects = [
			DIWikiPage::newFromText( __METHOD__ ),
		];

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$propertyTableDef = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ 'smw_foo' => $propertyTableDef ] ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'smw_foo' ) );

		$this->propertySubjectsLookup->expects( $this->atLeastOnce() )
			->method( 'prefetchFromTable' )
			->will( $this->returnValue( [ 42 => [ 'Bar#0##' ] ] ) );

		$instance = new PrefetchItemLookup(
			$this->store,
			$this->semanticDataLookup,
			$this->propertySubjectsLookup
		);

		$instance->getPropertyValues( $subjects, new DIProperty( 'Foo', true ), $this->requestOptions );
	}

}
