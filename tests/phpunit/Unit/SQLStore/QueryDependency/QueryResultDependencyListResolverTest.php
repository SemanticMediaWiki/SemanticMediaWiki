<?php

namespace SMW\Tests\SQLStore\QueryDependency;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Query\PrintRequest;
use SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver;
use SMWDIBlob as DIBlob;
use SMWQuery as Query;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class QueryResultDependencyListResolverTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $store;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$propertyHierarchyLookup = $this->getMockBuilder( '\SMW\PropertyHierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver',
			new QueryResultDependencyListResolver( $propertyHierarchyLookup )
		);
	}

	public function testTryTogetDependencyListFromForNonSetQueryResult() {

		$propertyHierarchyLookup = $this->getMockBuilder( '\SMW\PropertyHierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QueryResultDependencyListResolver(
			$propertyHierarchyLookup
		);

		$this->assertEmpty(
			$instance->getDependencyListFrom( '' )
		);
	}

	public function testTryTogetDependencyListFromForLimitZeroQuery() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$query->setUnboundLimit( 0 );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$queryResult->expects( $this->never() )
			->method( 'getStore' )
			->will( $this->returnValue( $this->store ) );

		$propertyHierarchyLookup = $this->getMockBuilder( '\SMW\PropertyHierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QueryResultDependencyListResolver(
			$propertyHierarchyLookup
		);

		$this->assertEmpty(
			$instance->getDependencyListFrom( $queryResult )
		);
	}

	public function testExcludePropertyFromDependencyDetection() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$description = new SomeProperty(
			new DIProperty( 'Foobar' ),
			new ValueDescription( DIWikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->will( $this->returnValue( array() ) );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$queryResult->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $this->store ) );

		$propertyHierarchyLookup = $this->getMockBuilder( '\SMW\PropertyHierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$propertyHierarchyLookup->expects( $this->any() )
			->method( 'hasSubpropertyFor' )
			->will( $this->returnValue( true ) );

		$propertyHierarchyLookup->expects( $this->at( 1 ) )
			->method( 'findSubpropertListFor' )
			->with( $this->equalTo( new DIProperty( 'Foobar' ) ) )
			->will( $this->returnValue(
				array( DIWikiPage::newFromText( 'Subprop', SMW_NS_PROPERTY ) ) ) );

		$instance = new QueryResultDependencyListResolver(
			$propertyHierarchyLookup
		);

		$instance->setPropertyDependencyExemptionlist( array( 'Subprop' ) );

		$expected = array(
			DIWikiPage::newFromText( 'Foo' ),
			DIWikiPage::newFromText( 'Bar' ),
			'Foobar#102#' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
		//	DIWikiPage::newFromText( 'Subprop', SMW_NS_PROPERTY ) removed
		);

		$this->assertEquals(
			$expected,
			$instance->getDependencyListFrom( $queryResult )
		);
	}


	/**
	 * @dataProvider queryProvider
	 */
	public function testgetDependencyListFrom( $query, $expected ) {

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->will( $this->returnValue( array() ) );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$queryResult->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $this->store ) );

		$propertyHierarchyLookup = $this->getMockBuilder( '\SMW\PropertyHierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QueryResultDependencyListResolver(
			$propertyHierarchyLookup
		);

		$this->assertEquals(
			$expected,
			$instance->getDependencyListFrom( $queryResult )
		);
	}

	public function testgetDependencyListByLateRetrievalFrom() {

		$subject = DIWikiPage::newFromText( 'Bar' );

		$description = new ClassDescription(
			DIWikiPage::newFromText( 'Foocat', NS_CATEGORY )
		);

		$query = new Query( $description );
		$query->setContextPage( DIWikiPage::newFromText( 'Foo' ) );

		$inMemoryEntityProcessList = $this->getMockBuilder( '\SMW\Query\Result\InMemoryEntityProcessList' )
			->disableOriginalConstructor()
			->getMock();

		$inMemoryEntityProcessList->expects( $this->once() )
			->method( 'getEntityList' )
			->will( $this->returnValue( array( $subject ) ) );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getInMemoryEntityProcessList' )
			->will( $this->returnValue( $inMemoryEntityProcessList ) );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$propertyHierarchyLookup = $this->getMockBuilder( '\SMW\PropertyHierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QueryResultDependencyListResolver(
			$propertyHierarchyLookup
		);

		$this->assertEquals(
			array( $subject ),
			$instance->getDependencyListByLateRetrievalFrom( $queryResult )
		);
	}

	public function testResolvePropertyHierarchy() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$description = new SomeProperty(
			new DIProperty( 'Foobar' ),
			new ValueDescription( DIWikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->will( $this->returnValue( array() ) );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$queryResult->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $this->store ) );

		$propertyHierarchyLookup = $this->getMockBuilder( '\SMW\PropertyHierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$propertyHierarchyLookup->expects( $this->any() )
			->method( 'hasSubpropertyFor' )
			->will( $this->returnValue( true ) );

		$propertyHierarchyLookup->expects( $this->at( 1 ) )
			->method( 'findSubpropertListFor' )
			->with( $this->equalTo( new DIProperty( 'Foobar' ) ) )
			->will( $this->returnValue(
				array( DIWikiPage::newFromText( 'Subprop', SMW_NS_PROPERTY ) ) ) );

		$instance = new QueryResultDependencyListResolver(
			$propertyHierarchyLookup
		);

		$expected = array(
			DIWikiPage::newFromText( 'Foo' ),
			DIWikiPage::newFromText( 'Bar' ),
			'Subprop#102#' => DIWikiPage::newFromText( 'Subprop', SMW_NS_PROPERTY ),
			'Foobar#102#' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
		);

		$this->assertEquals(
			$expected,
			$instance->getDependencyListFrom( $queryResult )
		);
	}

	public function testResolveCategoryHierarchy() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$description = new ClassDescription(
			DIWikiPage::newFromText( 'Foocat', NS_CATEGORY )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->will( $this->returnValue( array() ) );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$queryResult->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $this->store ) );

		$propertyHierarchyLookup = $this->getMockBuilder( '\SMW\PropertyHierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$propertyHierarchyLookup->expects( $this->any() )
			->method( 'hasSubcategoryFor' )
			->will( $this->returnValue( true ) );

		$propertyHierarchyLookup->expects( $this->at( 1 ) )
			->method( 'findSubcategoryListFor' )
			->with( $this->equalTo( DIWikiPage::newFromText( 'Foocat', NS_CATEGORY ) ) )
			->will( $this->returnValue(
				array(
					DIWikiPage::newFromText( 'Subcat', NS_CATEGORY ),
					DIWikiPage::newFromText( 'Foocat', NS_CATEGORY ) ) ) );

		$instance = new QueryResultDependencyListResolver(
			$propertyHierarchyLookup
		);

		$expected = array(
			DIWikiPage::newFromText( 'Foo' ),
			'Subcat#14#' => DIWikiPage::newFromText( 'Subcat', NS_CATEGORY ),
			'Foocat#14#' => DIWikiPage::newFromText( 'Foocat', NS_CATEGORY ),
			DIWikiPage::newFromText( 'Foocat', NS_CATEGORY )
		);

		$this->assertEquals(
			$expected,
			$instance->getDependencyListFrom( $queryResult )
		);
	}

	public function queryProvider() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		#0
		$description = new SomeProperty(
			new DIProperty( 'Foobar' ),
			new ValueDescription( DIWikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				'Foobar#102#' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			)
		);

		#1
		$description = new SomeProperty(
			new DIProperty( 'Foobar' ),
			new ValueDescription( new DIBlob( 'Bar' ) )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				'Foobar#102#' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			)
		);

		#2 uses inverse property declaration
		$description = new SomeProperty(
			new DIProperty( 'Foobar', true ),
			new ValueDescription( DIWikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				'Foobar#102#' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			)
		);

		#3 Conjunction
		$description = new SomeProperty(
			new DIProperty( 'Foobar' ),
			new ValueDescription( DIWikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( new Conjunction( array(
			$description,
			new NamespaceDescription( NS_MAIN )
		) ) );

		$query->setContextPage( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				'Foobar#102#' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			)
		);

		#4 Disjunction
		$description = new SomeProperty(
			new DIProperty( 'Foobar' ),
			new ValueDescription( DIWikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( new Disjunction( array(
			$description,
			new NamespaceDescription( NS_MAIN )
		) ) );

		$query->setContextPage( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				'Foobar#102#' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			)
		);

		#5
		$description = new ClassDescription(
			DIWikiPage::newFromText( 'Foocat', NS_CATEGORY )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Foocat', NS_CATEGORY )
			)
		);

		#6
		$description = new ConceptDescription(
			DIWikiPage::newFromText( 'FooConcept', SMW_NS_CONCEPT )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				'FooConcept#108#' => DIWikiPage::newFromText( 'FooConcept', SMW_NS_CONCEPT )
			)
		);

		#7 Printrequest
		$pv = \SMWPropertyValue::makeUserProperty( 'Foobaz' );

		$description = new SomeProperty(
			new DIProperty( 'Foobar', true ),
			new ValueDescription( DIWikiPage::newFromText( 'Bar' ) )
		);

		$description->addPrintRequest(
			new PrintRequest( PrintRequest::PRINT_PROP, '', $pv )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				DIWikiPage::newFromText( 'Foobaz', SMW_NS_PROPERTY ),
				'Foobar#102#' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY ),
			)
		);

		#8 Inverse printrequest
		$pv = \SMWPropertyValue::makeUserProperty( 'Foobaz' );
		$pv->setInverse( true );

		$description = new SomeProperty(
			new DIProperty( 'Foobar', true ),
			new ValueDescription( DIWikiPage::newFromText( 'Bar' ) )
		);

		$description->addPrintRequest(
			new PrintRequest( PrintRequest::PRINT_PROP, '', $pv )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				DIWikiPage::newFromText( 'Foobaz', SMW_NS_PROPERTY ),
				'Foobar#102#' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY ),
			)
		);

		#9 SMW_CMP_EQ comparator
		$description = new SomeProperty(
			new DIProperty( 'Foobar' ),
			new ValueDescription( DIWikiPage::newFromText( 'EQ_Comparator' ), null, SMW_CMP_EQ )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'EQ_Comparator' ),
				'Foobar#102#' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			)
		);

		#10 Ignore entity with SMW_CMP_EQ comparator
		$description = new SomeProperty(
			new DIProperty( 'Foobar' ),
			new ValueDescription( DIWikiPage::newFromText( 'LIKE_Comparator' ), null, SMW_CMP_LIKE )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				'Foobar#102#' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			)
		);

		return $provider;
	}

}
