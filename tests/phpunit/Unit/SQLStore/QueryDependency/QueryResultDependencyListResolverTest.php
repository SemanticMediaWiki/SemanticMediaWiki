<?php

namespace SMW\Tests\Unit\SQLStore\QueryDependency;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataValueFactory;
use SMW\HierarchyLookup;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Query\PrintRequest;
use SMW\Query\Query;
use SMW\Query\QueryResult;
use SMW\Query\Result\ItemJournal;
use SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.3
 *
 * @author mwjames
 */
class QueryResultDependencyListResolverTest extends TestCase {

	private $testEnvironment;
	private $store;
	private $hierarchyLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->hierarchyLookup = $this->getMockBuilder( HierarchyLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();

		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			QueryResultDependencyListResolver::class,
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
		$subject = WikiPage::newFromText( 'Foo' );

		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMock();

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$query->setUnboundLimit( 0 );

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$queryResult->expects( $this->never() )
			->method( 'getStore' )
			->willReturn( $this->store );

		$this->hierarchyLookup = $this->getMockBuilder( HierarchyLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->hierarchyLookup->expects( $this->any() )
			->method( 'getConsecutiveHierarchyList' )
			->willReturn( [] );

		$instance = new QueryResultDependencyListResolver(
			$this->hierarchyLookup
		);

		$this->assertEmpty(
			$instance->getDependencyListFrom( $queryResult )
		);
	}

	public function testExcludePropertyFromDependencyDetection() {
		$subject = WikiPage::newFromText( 'Foo' );

		$description = new SomeProperty(
			new Property( 'Foobar' ),
			new ValueDescription( WikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->willReturn( [] );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$queryResult->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $this->store );

		$this->hierarchyLookup = $this->getMockBuilder( HierarchyLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->hierarchyLookup->expects( $this->any() )
			->method( 'hasSubproperty' )
			->willReturn( true );

		$this->hierarchyLookup->expects( $this->once() )
			->method( 'getConsecutiveHierarchyList' )
			->with( new Property( 'Foobar' ) )
			->willReturn(
				[ new Property( 'Subprop' ) ] );

		$instance = new QueryResultDependencyListResolver(
			$this->hierarchyLookup
		);

		$instance->setPropertyDependencyExemptionlist( [ 'Subprop' ] );

		$expected = [
			WikiPage::newFromText( 'Foo' ),
			WikiPage::newFromText( 'Bar' ),
			'Foobar#102##' => WikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
		// DIWikiPage::newFromText( 'Subprop', SMW_NS_PROPERTY ) removed
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
		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->willReturn( [] );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$queryResult->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $this->store );

		$this->hierarchyLookup = $this->getMockBuilder( HierarchyLookup::class )
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
		$subject = WikiPage::newFromText( 'Bar' );

		$description = new ClassDescription(
			WikiPage::newFromText( 'Foocat', NS_CATEGORY )
		);

		$query = new Query( $description );
		$query->setContextPage( WikiPage::newFromText( 'Foo' ) );

		$itemJournal = $this->getMockBuilder( ItemJournal::class )
			->disableOriginalConstructor()
			->getMock();

		$itemJournal->expects( $this->once() )
			->method( 'getEntityList' )
			->willReturn( [ $subject ] );

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getItemJournal' )
			->willReturn( $itemJournal );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$this->hierarchyLookup = $this->getMockBuilder( HierarchyLookup::class )
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
		$subject = WikiPage::newFromText( 'Foo' );

		$description = new SomeProperty(
			new Property( 'Foobar' ),
			new ValueDescription( WikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->willReturn( [] );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$queryResult->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $this->store );

		$this->hierarchyLookup = $this->getMockBuilder( HierarchyLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->hierarchyLookup->expects( $this->any() )
			->method( 'hasSubproperty' )
			->willReturn( true );

		$this->hierarchyLookup->expects( $this->once() )
			->method( 'getConsecutiveHierarchyList' )
			->with( new Property( 'Foobar' ) )
			->willReturn(
				[ new Property( 'Subprop' ) ] );

		$instance = new QueryResultDependencyListResolver(
			$this->hierarchyLookup
		);

		$expected = [
			WikiPage::newFromText( 'Foo' ),
			WikiPage::newFromText( 'Bar' ),
			'Subprop#102##' => WikiPage::newFromText( 'Subprop', SMW_NS_PROPERTY ),
			'Foobar#102##' => WikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
		];

		$this->assertEquals(
			$expected,
			$instance->getDependencyListFrom( $queryResult )
		);
	}

	public function testResolveCategoryHierarchy() {
		$subject = WikiPage::newFromText( 'Foo' );

		$description = new ClassDescription(
			WikiPage::newFromText( 'Foocat', NS_CATEGORY )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->willReturn( [] );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$queryResult->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $this->store );

		$this->hierarchyLookup = $this->getMockBuilder( HierarchyLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->hierarchyLookup->expects( $this->any() )
			->method( 'hasSubcategory' )
			->willReturn( true );

		$this->hierarchyLookup->expects( $this->once() )
			->method( 'getConsecutiveHierarchyList' )
			->with( WikiPage::newFromText( 'Foocat', NS_CATEGORY ) )
			->willReturn(
				[
					WikiPage::newFromText( 'Subcat', NS_CATEGORY ),
					WikiPage::newFromText( 'Foocat', NS_CATEGORY ) ] );

		$instance = new QueryResultDependencyListResolver(
			$this->hierarchyLookup
		);

		$expected = [
			WikiPage::newFromText( 'Foo' ),
			'Subcat#14##' => WikiPage::newFromText( 'Subcat', NS_CATEGORY ),
			'Foocat#14##' => WikiPage::newFromText( 'Foocat', NS_CATEGORY ),
			WikiPage::newFromText( 'Foocat', NS_CATEGORY )
		];

		$this->assertEquals(
			$expected,
			$instance->getDependencyListFrom( $queryResult )
		);
	}

	public function queryProvider() {
		$subject = WikiPage::newFromText( 'Foo' );

		# 0
		$description = new SomeProperty(
			new Property( 'Foobar' ),
			new ValueDescription( WikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				WikiPage::newFromText( 'Foo' ),
				WikiPage::newFromText( 'Bar' ),
				'Foobar#102##' => WikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			]
		];

		# 1
		$description = new SomeProperty(
			new Property( 'Foobar' ),
			new ValueDescription( new Blob( 'Bar' ) )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				WikiPage::newFromText( 'Foo' ),
				'Foobar#102##' => WikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			]
		];

		# 2 uses inverse property declaration
		$description = new SomeProperty(
			new Property( 'Foobar', true ),
			new ValueDescription( WikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				WikiPage::newFromText( 'Foo' ),
				WikiPage::newFromText( 'Bar' ),
				'Foobar#102##' => WikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			]
		];

		# 3 Conjunction
		$description = new SomeProperty(
			new Property( 'Foobar' ),
			new ValueDescription( WikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( new Conjunction( [
			$description,
			new NamespaceDescription( NS_MAIN )
		] ) );

		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				WikiPage::newFromText( 'Foo' ),
				WikiPage::newFromText( 'Bar' ),
				'Foobar#102##' => WikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			]
		];

		# 4 Disjunction
		$description = new SomeProperty(
			new Property( 'Foobar' ),
			new ValueDescription( WikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( new Disjunction( [
			$description,
			new NamespaceDescription( NS_MAIN )
		] ) );

		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				WikiPage::newFromText( 'Foo' ),
				WikiPage::newFromText( 'Bar' ),
				'Foobar#102##' => WikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			]
		];

		# 5
		$description = new ClassDescription(
			WikiPage::newFromText( 'Foocat', NS_CATEGORY )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				WikiPage::newFromText( 'Foo' ),
				WikiPage::newFromText( 'Foocat', NS_CATEGORY )
			]
		];

		# 6
		$description = new ConceptDescription(
			WikiPage::newFromText( 'FooConcept', SMW_NS_CONCEPT )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				WikiPage::newFromText( 'Foo' ),
				'FooConcept#108##' => WikiPage::newFromText( 'FooConcept', SMW_NS_CONCEPT )
			]
		];

		# 7 Printrequest
		$pv = DataValueFactory::getInstance()->newPropertyValueByLabel( 'Foobaz' );

		$description = new SomeProperty(
			new Property( 'Foobar', true ),
			new ValueDescription( WikiPage::newFromText( 'Bar' ) )
		);

		$description->addPrintRequest(
			new PrintRequest( PrintRequest::PRINT_PROP, '', $pv )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				WikiPage::newFromText( 'Foo' ),
				WikiPage::newFromText( 'Bar' ),
				WikiPage::newFromText( 'Foobaz', SMW_NS_PROPERTY ),
				'Foobar#102##' => WikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY ),
			]
		];

		# 8 Inverse printrequest
		$pv = DataValueFactory::getInstance()->newPropertyValueByLabel( 'Foobaz' );
		$pv->setInverse( true );

		$description = new SomeProperty(
			new Property( 'Foobar', true ),
			new ValueDescription( WikiPage::newFromText( 'Bar' ) )
		);

		$description->addPrintRequest(
			new PrintRequest( PrintRequest::PRINT_PROP, '', $pv )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				WikiPage::newFromText( 'Foo' ),
				WikiPage::newFromText( 'Bar' ),
				WikiPage::newFromText( 'Foobaz', SMW_NS_PROPERTY ),
				'Foobar#102##' => WikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY ),
			]
		];

		# 9 SMW_CMP_EQ comparator
		$description = new SomeProperty(
			new Property( 'Foobar' ),
			new ValueDescription( WikiPage::newFromText( 'EQ_Comparator' ), null, SMW_CMP_EQ )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				WikiPage::newFromText( 'Foo' ),
				WikiPage::newFromText( 'EQ_Comparator' ),
				'Foobar#102##' => WikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			]
		];

		# 10 Ignore entity with SMW_CMP_EQ comparator
		$description = new SomeProperty(
			new Property( 'Foobar' ),
			new ValueDescription( WikiPage::newFromText( 'LIKE_Comparator' ), null, SMW_CMP_LIKE )
		);

		$query = new Query( $description );
		$query->setContextPage( $subject );

		$provider[] = [
			$query,
			[
				WikiPage::newFromText( 'Foo' ),
				'Foobar#102##' => WikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			]
		];

		return $provider;
	}

}
