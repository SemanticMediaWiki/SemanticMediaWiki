<?php

namespace SMW\Tests\SQLStore\QueryDependency;

use SMW\DataValueFactory;
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
use SMW\Tests\TestEnvironment;
use SMWDIBlob as DIBlob;
use SMWQuery as Query;

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
	private $hierarchyLookup;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->hierarchyLookup = $this->getMockBuilder( '\SMW\HierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver',
			new QueryResultDependencyListResolver( $this->hierarchyLookup )
		);
	}

	public function testTryTogetDependencyListFromForNonSetQueryResult() {

		$instance = new QueryResultDependencyListResolver(
			$this->hierarchyLookup
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

		$this->hierarchyLookup = $this->getMockBuilder( '\SMW\HierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->hierarchyLookup->expects( $this->any() )
			->method( 'getConsecutiveHierarchyList' )
			->will( $this->returnValue( [] ) );

		$instance = new QueryResultDependencyListResolver(
			$this->hierarchyLookup
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
			->will( $this->returnValue( [] ) );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$queryResult->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $this->store ) );

		$this->hierarchyLookup = $this->getMockBuilder( '\SMW\HierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->hierarchyLookup->expects( $this->any() )
			->method( 'hasSubproperty' )
			->will( $this->returnValue( true ) );

		$this->hierarchyLookup->expects( $this->at( 1 ) )
			->method( 'getConsecutiveHierarchyList' )
			->with( $this->equalTo( new DIProperty( 'Foobar' ) ) )
			->will( $this->returnValue(
				[ new DIProperty( 'Subprop' ) ] ) );

		$instance = new QueryResultDependencyListResolver(
			$this->hierarchyLookup
		);

		$instance->setPropertyDependencyExemptionlist( [ 'Subprop' ] );

		$expected = [
			DIWikiPage::newFromText( 'Foo' ),
			DIWikiPage::newFromText( 'Bar' ),
			'Foobar#102##' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
		//	DIWikiPage::newFromText( 'Subprop', SMW_NS_PROPERTY ) removed
		];

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
			->will( $this->returnValue( [] ) );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$queryResult->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $this->store ) );

		$this->hierarchyLookup = $this->getMockBuilder( '\SMW\HierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QueryResultDependencyListResolver(
			$this->hierarchyLookup
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

		$itemJournal = $this->getMockBuilder( '\SMW\Query\Result\ItemJournal' )
			->disableOriginalConstructor()
			->getMock();

		$itemJournal->expects( $this->once() )
			->method( 'getEntityList' )
			->will( $this->returnValue( [ $subject ] ) );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getItemJournal' )
			->will( $this->returnValue( $itemJournal ) );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$this->hierarchyLookup = $this->getMockBuilder( '\SMW\HierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QueryResultDependencyListResolver(
			$this->hierarchyLookup
		);

		$this->assertEquals(
			[ $subject ],
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
			->will( $this->returnValue( [] ) );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$queryResult->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $this->store ) );

		$this->hierarchyLookup = $this->getMockBuilder( '\SMW\HierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->hierarchyLookup->expects( $this->any() )
			->method( 'hasSubproperty' )
			->will( $this->returnValue( true ) );

		$this->hierarchyLookup->expects( $this->at( 1 ) )
			->method( 'getConsecutiveHierarchyList' )
			->with( $this->equalTo( new DIProperty( 'Foobar' ) ) )
			->will( $this->returnValue(
				[ new DIProperty( 'Subprop' ) ] ) );

		$instance = new QueryResultDependencyListResolver(
			$this->hierarchyLookup
		);

		$expected = [
			DIWikiPage::newFromText( 'Foo' ),
			DIWikiPage::newFromText( 'Bar' ),
			'Subprop#102##' => DIWikiPage::newFromText( 'Subprop', SMW_NS_PROPERTY ),
			'Foobar#102##' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
		];

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
			->will( $this->returnValue( [] ) );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$queryResult->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $this->store ) );

		$this->hierarchyLookup = $this->getMockBuilder( '\SMW\HierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->hierarchyLookup->expects( $this->any() )
			->method( 'hasSubcategory' )
			->will( $this->returnValue( true ) );

		$this->hierarchyLookup->expects( $this->at( 1 ) )
			->method( 'getConsecutiveHierarchyList' )
			->with( $this->equalTo( DIWikiPage::newFromText( 'Foocat', NS_CATEGORY ) ) )
			->will( $this->returnValue(
				[
					DIWikiPage::newFromText( 'Subcat', NS_CATEGORY ),
					DIWikiPage::newFromText( 'Foocat', NS_CATEGORY ) ] ) );

		$instance = new QueryResultDependencyListResolver(
			$this->hierarchyLookup
		);

		$expected = [
			DIWikiPage::newFromText( 'Foo' ),
			'Subcat#14##' => DIWikiPage::newFromText( 'Subcat', NS_CATEGORY ),
			'Foocat#14##' => DIWikiPage::newFromText( 'Foocat', NS_CATEGORY ),
			DIWikiPage::newFromText( 'Foocat', NS_CATEGORY )
		];

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

		$provider[] = [
			$query,
			[
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				'Foobar#102##' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			]
		];

		#1
		$description = new SomeProperty(
			new DIProperty( 'Foobar' ),
			new ValueDescription( new DIBlob( 'Bar' ) )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				DIWikiPage::newFromText( 'Foo' ),
				'Foobar#102##' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			]
		];

		#2 uses inverse property declaration
		$description = new SomeProperty(
			new DIProperty( 'Foobar', true ),
			new ValueDescription( DIWikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				'Foobar#102##' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			]
		];

		#3 Conjunction
		$description = new SomeProperty(
			new DIProperty( 'Foobar' ),
			new ValueDescription( DIWikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( new Conjunction( [
			$description,
			new NamespaceDescription( NS_MAIN )
		] ) );

		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				'Foobar#102##' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			]
		];

		#4 Disjunction
		$description = new SomeProperty(
			new DIProperty( 'Foobar' ),
			new ValueDescription( DIWikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( new Disjunction( [
			$description,
			new NamespaceDescription( NS_MAIN )
		] ) );

		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				'Foobar#102##' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			]
		];

		#5
		$description = new ClassDescription(
			DIWikiPage::newFromText( 'Foocat', NS_CATEGORY )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Foocat', NS_CATEGORY )
			]
		];

		#6
		$description = new ConceptDescription(
			DIWikiPage::newFromText( 'FooConcept', SMW_NS_CONCEPT )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				DIWikiPage::newFromText( 'Foo' ),
				'FooConcept#108##' => DIWikiPage::newFromText( 'FooConcept', SMW_NS_CONCEPT )
			]
		];

		#7 Printrequest
		$pv = DataValueFactory::getInstance()->newPropertyValueByLabel( 'Foobaz' );

		$description = new SomeProperty(
			new DIProperty( 'Foobar', true ),
			new ValueDescription( DIWikiPage::newFromText( 'Bar' ) )
		);

		$description->addPrintRequest(
			new PrintRequest( PrintRequest::PRINT_PROP, '', $pv )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				DIWikiPage::newFromText( 'Foobaz', SMW_NS_PROPERTY ),
				'Foobar#102##' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY ),
			]
		];

		#8 Inverse printrequest
		$pv = DataValueFactory::getInstance()->newPropertyValueByLabel( 'Foobaz' );
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

		$provider[] = [
			$query,
			[
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				DIWikiPage::newFromText( 'Foobaz', SMW_NS_PROPERTY ),
				'Foobar#102##' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY ),
			]
		];

		#9 SMW_CMP_EQ comparator
		$description = new SomeProperty(
			new DIProperty( 'Foobar' ),
			new ValueDescription( DIWikiPage::newFromText( 'EQ_Comparator' ), null, SMW_CMP_EQ )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'EQ_Comparator' ),
				'Foobar#102##' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			]
		];

		#10 Ignore entity with SMW_CMP_EQ comparator
		$description = new SomeProperty(
			new DIProperty( 'Foobar' ),
			new ValueDescription( DIWikiPage::newFromText( 'LIKE_Comparator' ), null, SMW_CMP_LIKE )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				DIWikiPage::newFromText( 'Foo' ),
				'Foobar#102##' => DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			]
		];

		return $provider;
	}

}
