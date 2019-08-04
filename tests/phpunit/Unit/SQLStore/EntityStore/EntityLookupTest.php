<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SQLStore\EntityStore\EntityLookup;

/**
 * @covers \SMW\SQLStore\EntityStore\EntityLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class EntityLookupTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $factory;
	private $traversalPropertyLookup;
	private $propertySubjectsLookup;
	private $propertiesLookup;
	private $semanticDataLookup;

	protected function setUp() {

		$this->idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->traversalPropertyLookup = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\TraversalPropertyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertySubjectsLookup = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\PropertySubjectsLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertiesLookup = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\PropertiesLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->semanticDataLookup = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\CachingSemanticDataLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $this->idTable ) );

		$this->factory = $this->getMockBuilder( '\SMW\SQLStore\SQLStoreFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->factory->expects( $this->any() )
			->method( 'newTraversalPropertyLookup' )
			->will( $this->returnValue( $this->traversalPropertyLookup ) );

		$this->factory->expects( $this->any() )
			->method( 'newPropertySubjectsLookup' )
			->will( $this->returnValue( $this->propertySubjectsLookup ) );

		$this->factory->expects( $this->any() )
			->method( 'newPropertiesLookup' )
			->will( $this->returnValue( $this->propertiesLookup ) );

		$this->factory->expects( $this->any() )
			->method( 'newSemanticDataLookup' )
			->will( $this->returnValue( $this->semanticDataLookup ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			EntityLookup::class,
			new EntityLookup( $this->store, $this->factory )
		);
	}

	public function testGetSemanticData() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$semanticData = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\StubSemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$this->idTable->expects( $this->once() )
			->method( 'getSMWPageIDandSort' )
			->will( $this->returnValue( 42 ) );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'getSemanticDataById' )
			->with( $this->equalTo( 42 ) )
			->will( $this->returnValue( $semanticData ) );

		$instance = new EntityLookup(
			$this->store,
			$this->factory
		);

		$instance->getSemanticData( $subject );
	}

	public function testGetProperties() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$propTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propTable->expects( $this->once() )
			->method( 'getName' )
			->will( $this->returnValue( '_foo' ) );

		$this->idTable->expects( $this->once() )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 42 ) );

		$this->idTable->expects( $this->once() )
			->method( 'getPropertyTableHashes' )
			->will( $this->returnValue( [ '_foo' => '...' ] ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ '_foo' => $propTable ] ) );

		$this->propertiesLookup->expects( $this->once() )
			->method( 'fetchFromTable' )
			->will( $this->returnValue( [] ) );

		$instance = new EntityLookup(
			$this->store,
			$this->factory
		);

		$instance->getProperties( $subject );
	}

	public function testGetPropertyValues() {

		$property = new DIProperty( 'Bar' );
		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$semanticData = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\StubSemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [] ) );

		$propTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$this->idTable->expects( $this->once() )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 1001 ) );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( '_foo' ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ '_foo' => $propTable ] ) );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

		$instance = new EntityLookup(
			$this->store,
			$this->factory
		);

		$instance->getPropertyValues( $subject, $property );
	}

	public function testGetPropertyValues_Property_Inverse() {

		$property = new DIProperty( 'Bar', true );
		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$propTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$this->idTable->expects( $this->once() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 42 ) );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( '_foo' ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ '_foo' => $propTable ] ) );

		$this->propertySubjectsLookup->expects( $this->once() )
			->method( 'fetchFromTable' )
			->will( $this->returnValue( [] ) );

		$instance = new EntityLookup(
			$this->store,
			$this->factory
		);

		$instance->getPropertyValues( $subject, $property );
	}

	public function testGetPropertyValues_Subject_Null() {

		$property = new DIProperty( 'Bar' );
		$subject = null;

		$propTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$this->idTable->expects( $this->once() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 42 ) );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( '_foo' ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ '_foo' => $propTable ] ) );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'fetchSemanticDataFromTable' )
			->will( $this->returnValue( [] ) );

		$instance = new EntityLookup(
			$this->store,
			$this->factory
		);

		$instance->getPropertyValues( $subject, $property );
	}

	public function testGetPropertySubjects() {

		$property = new DIProperty( 'Bar' );
		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$propTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$this->idTable->expects( $this->once() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 42 ) );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( '_foo' ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ '_foo' => $propTable ] ) );

		$this->propertySubjectsLookup->expects( $this->once() )
			->method( 'fetchFromTable' )
			->will( $this->returnValue( [] ) );

		$instance = new EntityLookup(
			$this->store,
			$this->factory
		);

		$instance->getPropertySubjects( $property, $subject );
	}

	public function testGetAllPropertySubjects() {

		$property = new DIProperty( 'Bar' );

		$propTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$this->idTable->expects( $this->once() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 42 ) );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( '_foo' ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ '_foo' => $propTable ] ) );

		$this->propertySubjectsLookup->expects( $this->once() )
			->method( 'fetchFromTable' )
			->will( $this->returnValue( [] ) );

		$instance = new EntityLookup(
			$this->store,
			$this->factory
		);

		$instance->getAllPropertySubjects( $property );
	}

	public function testGetInProperties() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$propTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propTable->expects( $this->once() )
			->method( 'getDiType' )
			->will( $this->returnValue( $subject->getDIType() ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ '_foo' => $propTable ] ) );

		$this->traversalPropertyLookup->expects( $this->once() )
			->method( 'fetchFromTable' )
			->will( $this->returnValue( [] ) );

		$instance = new EntityLookup(
			$this->store,
			$this->factory
		);

		$instance->getInProperties( $subject );
	}

}
