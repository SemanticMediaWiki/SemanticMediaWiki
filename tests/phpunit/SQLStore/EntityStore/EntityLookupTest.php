<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SQLStore\EntityStore\EntityIdManager;
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
class EntityLookupTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $factory;
	private EntityIdManager $idTable;
	private $traversalPropertyLookup;
	private $propertySubjectsLookup;
	private $propertiesLookup;
	private $semanticDataLookup;

	protected function setUp(): void {
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
			->willReturn( $this->idTable );

		$this->factory = $this->getMockBuilder( '\SMW\SQLStore\SQLStoreFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->factory->expects( $this->any() )
			->method( 'newTraversalPropertyLookup' )
			->willReturn( $this->traversalPropertyLookup );

		$this->factory->expects( $this->any() )
			->method( 'newPropertySubjectsLookup' )
			->willReturn( $this->propertySubjectsLookup );

		$this->factory->expects( $this->any() )
			->method( 'newPropertiesLookup' )
			->willReturn( $this->propertiesLookup );

		$this->factory->expects( $this->any() )
			->method( 'newSemanticDataLookup' )
			->willReturn( $this->semanticDataLookup );
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
			->willReturn( [] );

		$this->idTable->expects( $this->once() )
			->method( 'getSMWPageIDandSort' )
			->willReturn( 42 );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'getSemanticDataById' )
			->with( 42 )
			->willReturn( $semanticData );

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
			->willReturn( '_foo' );

		$this->idTable->expects( $this->once() )
			->method( 'getSMWPageID' )
			->willReturn( 42 );

		$this->idTable->expects( $this->once() )
			->method( 'getPropertyTableHashes' )
			->willReturn( [ '_foo' => '...' ] );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ '_foo' => $propTable ] );

		$this->propertiesLookup->expects( $this->once() )
			->method( 'fetchFromTable' )
			->willReturn( [] );

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
			->willReturn( [] );

		$propTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$this->idTable->expects( $this->once() )
			->method( 'getSMWPageIDandSort' )
			->willReturn( 1001 );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->willReturn( '_foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ '_foo' => $propTable ] );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

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
			->willReturn( 42 );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->willReturn( '_foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ '_foo' => $propTable ] );

		$this->propertySubjectsLookup->expects( $this->once() )
			->method( 'fetchFromTable' )
			->willReturn( [] );

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
			->willReturn( 42 );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->willReturn( '_foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ '_foo' => $propTable ] );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'fetchSemanticDataFromTable' )
			->willReturn( [] );

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
			->willReturn( 42 );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->willReturn( '_foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ '_foo' => $propTable ] );

		$this->propertySubjectsLookup->expects( $this->once() )
			->method( 'fetchFromTable' )
			->willReturn( [] );

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
			->willReturn( 42 );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->willReturn( '_foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ '_foo' => $propTable ] );

		$this->propertySubjectsLookup->expects( $this->once() )
			->method( 'fetchFromTable' )
			->willReturn( [] );

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
			->willReturn( $subject->getDIType() );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ '_foo' => $propTable ] );

		$this->traversalPropertyLookup->expects( $this->once() )
			->method( 'fetchFromTable' )
			->willReturn( [] );

		$instance = new EntityLookup(
			$this->store,
			$this->factory
		);

		$instance->getInProperties( $subject );
	}

}
