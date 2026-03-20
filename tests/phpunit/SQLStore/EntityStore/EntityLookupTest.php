<?php

namespace SMW\Tests\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\SQLStore\EntityStore\CachingSemanticDataLookup;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\EntityStore\EntityLookup;
use SMW\SQLStore\EntityStore\PropertiesLookup;
use SMW\SQLStore\EntityStore\PropertySubjectsLookup;
use SMW\SQLStore\EntityStore\StubSemanticData;
use SMW\SQLStore\EntityStore\TraversalPropertyLookup;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\SQLStoreFactory;

/**
 * @covers \SMW\SQLStore\EntityStore\EntityLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class EntityLookupTest extends TestCase {

	private $store;
	private $factory;
	private EntityIdManager $idTable;
	private $traversalPropertyLookup;
	private $propertySubjectsLookup;
	private $propertiesLookup;
	private $semanticDataLookup;

	protected function setUp(): void {
		$this->idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->traversalPropertyLookup = $this->getMockBuilder( TraversalPropertyLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertySubjectsLookup = $this->getMockBuilder( PropertySubjectsLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertiesLookup = $this->getMockBuilder( PropertiesLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->semanticDataLookup = $this->getMockBuilder( CachingSemanticDataLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $this->idTable );

		$this->factory = $this->getMockBuilder( SQLStoreFactory::class )
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
		$subject = new WikiPage( 'Foo', NS_MAIN );

		$semanticData = $this->getMockBuilder( StubSemanticData::class )
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
		$subject = new WikiPage( 'Foo', NS_MAIN );

		$propTable = $this->getMockBuilder( PropertyTableDefinition::class )
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
		$property = new Property( 'Bar' );
		$subject = new WikiPage( 'Foo', NS_MAIN );

		$semanticData = $this->getMockBuilder( StubSemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$propTable = $this->getMockBuilder( PropertyTableDefinition::class )
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
		$property = new Property( 'Bar', true );
		$subject = new WikiPage( 'Foo', NS_MAIN );

		$propTable = $this->getMockBuilder( PropertyTableDefinition::class )
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
		$property = new Property( 'Bar' );
		$subject = null;

		$propTable = $this->getMockBuilder( PropertyTableDefinition::class )
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
		$property = new Property( 'Bar' );
		$subject = new WikiPage( 'Foo', NS_MAIN );

		$propTable = $this->getMockBuilder( PropertyTableDefinition::class )
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
		$property = new Property( 'Bar' );

		$propTable = $this->getMockBuilder( PropertyTableDefinition::class )
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
		$subject = new WikiPage( 'Foo', NS_MAIN );

		$propTable = $this->getMockBuilder( PropertyTableDefinition::class )
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
