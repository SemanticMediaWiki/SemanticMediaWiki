<?php

namespace SMW\Tests\SQLStore\QueryEngine\Compiler;

use SMW\Tests\Utils\UtilityFactory;

use SMW\SQLStore\QueryEngine\Compiler\SomePropertyCompiler;
use SMW\SQLStore\QueryEngine\QueryBuilder;

use SMW\Query\Language\ValueDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMWDIBlob as DIBlob;

/**
 * @covers \SMW\SQLStore\QueryEngine\Compiler\SomePropertyCompiler
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class SomePropertyCompilerTest extends \PHPUnit_Framework_TestCase {

	private $queryContainerValidator;

	protected function setUp() {
		parent::setUp();

		$this->queryContainerValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSqlQueryPartValidator();
	}

	public function testCanConstruct() {

		$queryBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QueryBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Compiler\SomePropertyCompiler',
			new SomePropertyCompiler( $queryBuilder )
		);
	}

	public function testCompileDescriptionForUnknownTablePropertyId() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( '' ) );

		$description = new SomeProperty(
			new DIProperty( 'Foo' ),
			new ThingDescription()
		);

		$expected = new \stdClass;
		$expected->type = 0;

		$instance = new SomePropertyCompiler( new QueryBuilder( $store ) );

		$this->assertTrue( $instance->canCompileDescription( $description ) );

		$this->queryContainerValidator->assertThatContainerHasProperties(
			$expected,
			$instance->compileDescription( $description )
		);
	}

	public function testCompileDescriptionForNonIdSubject() {

		$proptable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'usesIdSubject' ) )
			->getMock();

		$proptable->expects( $this->any() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( false ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Foo' ) );

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( 'Foo' => $proptable ) ) );

		$description = new SomeProperty(
			new DIProperty( 'Foo' ),
			new ThingDescription()
		);

		$expected = new \stdClass;
		$expected->type = 0;

		$instance = new SomePropertyCompiler( new QueryBuilder( $store ) );

		$this->assertTrue( $instance->canCompileDescription( $description ) );

		$this->queryContainerValidator->assertThatContainerHasProperties(
			$expected,
			$instance->compileDescription( $description )
		);
	}

	public function testCompileDescriptionForNonWikiPageTypeInverseProperty() {

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$property->expects( $this->once() )
			->method( 'isInverse' )
			->will( $this->returnValue( true ) );

		$property->expects( $this->once() )
			->method( 'findPropertyTypeID' )
			->will( $this->returnValue( '_txt' ) );

		$proptable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'usesIdSubject' ) )
			->getMock();

		$proptable->expects( $this->any() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( true ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Foo' ) );

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( 'Foo' => $proptable ) ) );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$expected = new \stdClass;
		$expected->type = 0;

		$instance = new SomePropertyCompiler( new QueryBuilder( $store ) );

		$this->assertTrue( $instance->canCompileDescription( $description ) );

		$this->queryContainerValidator->assertThatContainerHasProperties(
			$expected,
			$instance->compileDescription( $description )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testCompileDescription( $description, $isFixedPropertyTable, $indexField, $sortKeys, $expected ) {

		$dataItemHandler = $this->getMockBuilder( '\SMWDataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->any() )
			->method( 'getIndexField' )
			->will( $this->returnValue( $indexField ) );

		$dataItemHandler->expects( $this->any() )
			->method( 'getWhereConds' )
			->will( $this->returnValue( array( $indexField => 'fixedFooWhereCond' ) ) );

		$objectIds = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getSMWPropertyID', 'getSMWPageID' ) )
			->getMock();

		$objectIds->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 42 ) );

		$objectIds->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 91 ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$proptable = $this->getMockBuilder( '\SMWSQLStore3Table' )
			->disableOriginalConstructor()
			->getMock();

		$proptable->expects( $this->any() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( true ) );

		$proptable->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnValue( 'FooPropTable' ) );

		$proptable->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( $isFixedPropertyTable ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Foo' ) );

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( 'Foo' => $proptable ) ) );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIds ) );

		$store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$queryBuilder = new QueryBuilder( $store );
		$queryBuilder->setSortKeys( $sortKeys );

		$instance = new SomePropertyCompiler( $queryBuilder );

		$this->assertTrue( $instance->canCompileDescription( $description ) );

		$this->queryContainerValidator->assertThatContainerHasProperties(
			$expected,
			$instance->compileDescription( $description )
		);
	}

	public function descriptionProvider() {

		#0 Blob + wildcard
		$isFixedPropertyTable = false;
		$indexField = '';
		$sortKeys = array();
		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = array( 1 => "t0.p_id" );
		$expected->sortfields = array();

		$provider[] = array(
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		);

		#1 WikiPage + SMW_CMP_EQ
		$isFixedPropertyTable = false;
		$indexField = 'wikipageIndex';
		$sortKeys = array();
		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_wpg' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ), null, SMW_CMP_EQ )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = array( 1 => "t0.p_id", 2 => "t0.wikipageIndex" );
		$expected->queryNumber = 0;
		$expected->where = '';
		$expected->sortfields = array();

		$provider[] = array(
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		);

		#2 WikiPage + SMW_CMP_EQ + sort
		$isFixedPropertyTable = false;
		$indexField = 'wikipageIndex';
		$sortKeys = array( 'Foo' => 'DESC' );
		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_wpg' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ), null, SMW_CMP_EQ )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = array( 1 => "t0.p_id", 2 => "t0.wikipageIndex" );
		$expected->queryNumber = 0;
		$expected->where = '';
		$expected->sortfields = array( 'Foo' => 'idst0.smw_sortkey' );
		$expected->from = ' INNER JOIN  AS idst0 ON idst0.smw_id=t0.wikipageIndex';

		$provider[] = array(
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		);

		#3 Blob + SMW_CMP_EQ
		$isFixedPropertyTable = false;
		$indexField = 'blobIndex';
		$sortKeys = array();
		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIBlob( 'Bar' ), null, SMW_CMP_EQ )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = array( 1 => "t0.p_id" );
		$expected->queryNumber = 0;
		$expected->where = '(t0.blobIndex=fixedFooWhereCond)';
		$expected->sortfields = array();

		$provider[] = array(
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		);

		#4 Blob + SMW_CMP_EQ + sort
		$isFixedPropertyTable = false;
		$indexField = 'blobIndex';
		$sortKeys = array( 'Foo' => 'ASC' );
		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIBlob( 'Bar' ), null, SMW_CMP_EQ )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = array( 1 => "t0.p_id" );
		$expected->queryNumber = 0;
		$expected->where = '(t0.blobIndex=fixedFooWhereCond)';
		$expected->sortfields = array( 'Foo' => 't0.blobIndex' );
		$expected->from = '';

		$provider[] = array(
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		);

		return $provider;
	}

}
