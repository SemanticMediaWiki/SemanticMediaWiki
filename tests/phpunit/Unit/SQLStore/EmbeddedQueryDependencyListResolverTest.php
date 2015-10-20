<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\EmbeddedQueryDependencyListResolver;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\PrintRequest;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMWQuery as Query;
use SMWDIBlob as DIBlob;

/**
 * @covers \SMW\SQLStore\EmbeddedQueryDependencyListResolver
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class EmbeddedQueryDependencyListResolverTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->applicationFactory->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyHierarchyLookup = $this->getMockBuilder( '\SMW\PropertyHierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\EmbeddedQueryDependencyListResolver',
			new EmbeddedQueryDependencyListResolver( $store, $propertyHierarchyLookup )
		);
	}

	public function testTryToGetQueryDependencySubjectListForNonSetQueryResult() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyHierarchyLookup = $this->getMockBuilder( '\SMW\PropertyHierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EmbeddedQueryDependencyListResolver(
			$store,
			$propertyHierarchyLookup
		);

		$this->assertNull(
			$instance->getQueryId()
		);

		$this->assertNull(
			$instance->getSubject()
		);

		$this->assertEmpty(
			$instance->getQueryDependencySubjectList()
		);
	}

	public function testTryToGetQueryDependencySubjectListForLimitZeroQuery() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$query = new Query( $description );
		$query->setSubject( $subject );

		$query->setUnboundLimit( 0 );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyHierarchyLookup = $this->getMockBuilder( '\SMW\PropertyHierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EmbeddedQueryDependencyListResolver(
			$store,
			$propertyHierarchyLookup
		);

		$instance->setQueryResult( $queryResult );

		$this->assertEmpty(
			$instance->getQueryDependencySubjectList()
		);
	}

	public function testExcludePropertyFromDependencyDetection() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$description = new SomeProperty(
			new DIProperty( 'Foobar' ),
			new ValueDescription( DIWikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( $description );
		$query->setSubject( $subject );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->will( $this->returnValue( array() ) );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

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

		$propertyHierarchyLookup->expects( $this->at( 3 ) )
			->method( 'findSubpropertListFor' )
			->with( $this->equalTo( new DIProperty( 'Subprop' ) ) )
			->will( $this->returnValue( array() ) );

		$instance = new EmbeddedQueryDependencyListResolver(
			$store,
			$propertyHierarchyLookup
		);

		$instance->setQueryResult( $queryResult );
		$instance->setPropertyDependencyDetectionBlacklist( array( 'Foobar', 'Subprop' ) );

		$expected = array(
			DIWikiPage::newFromText( 'Foo' ),
			DIWikiPage::newFromText( 'Bar' )
		//	DIWikiPage::newFromText( 'Subprop', SMW_NS_PROPERTY ) removed
		//	DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY ) removed
		);

		$this->assertEquals(
			$expected,
			$instance->getQueryDependencySubjectList()
		);
	}

	/**
	 * @dataProvider queryProvider
	 */
	public function testGetQueryDependencySubjectList( $query, $expected ) {

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->will( $this->returnValue( array() ) );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyHierarchyLookup = $this->getMockBuilder( '\SMW\PropertyHierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EmbeddedQueryDependencyListResolver(
			$store,
			$propertyHierarchyLookup
		);

		$instance->setQueryResult( $queryResult );

		$this->assertEquals(
			$expected,
			$instance->getQueryDependencySubjectList()
		);
	}

	public function testResolvePropertyHierarchy() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$description = new SomeProperty(
			new DIProperty( 'Foobar' ),
			new ValueDescription( DIWikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( $description );
		$query->setSubject( $subject );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->will( $this->returnValue( array() ) );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

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

		$propertyHierarchyLookup->expects( $this->at( 3 ) )
			->method( 'findSubpropertListFor' )
			->with( $this->equalTo( new DIProperty( 'Subprop' ) ) )
			->will( $this->returnValue( array() ) );

		$instance = new EmbeddedQueryDependencyListResolver(
			$store,
			$propertyHierarchyLookup
		);

		$instance->setQueryResult( $queryResult );

		$expected = array(
			DIWikiPage::newFromText( 'Foo' ),
			DIWikiPage::newFromText( 'Bar' ),
			DIWikiPage::newFromText( 'Subprop', SMW_NS_PROPERTY ),
			DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
		);

		$this->assertEquals(
			$expected,
			$instance->getQueryDependencySubjectList()
		);
	}

	public function testResolveCategoryHierarchy() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$description = new ClassDescription(
			DIWikiPage::newFromText( 'Foocat', NS_CATEGORY )
		);

		$query = new Query( $description );
		$query->setSubject( $subject );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->will( $this->returnValue( array() ) );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

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
				array( DIWikiPage::newFromText( 'Subcat', NS_CATEGORY ) ) ) );

		$propertyHierarchyLookup->expects( $this->at( 3 ) )
			->method( 'findSubcategoryListFor' )
			->with( $this->equalTo( DIWikiPage::newFromText( 'Subcat', NS_CATEGORY ) ) )
			->will( $this->returnValue( array() ) );

		$instance = new EmbeddedQueryDependencyListResolver(
			$store,
			$propertyHierarchyLookup
		);

		$instance->setQueryResult( $queryResult );

		$expected = array(
			DIWikiPage::newFromText( 'Foo' ),
			DIWikiPage::newFromText( 'Subcat', NS_CATEGORY ),
			DIWikiPage::newFromText( 'Foocat', NS_CATEGORY )
		);

		$this->assertEquals(
			$expected,
			$instance->getQueryDependencySubjectList()
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
		$query->setSubject( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			)
		);

		#1
		$description = new SomeProperty(
			new DIProperty( 'Foobar' ),
			new ValueDescription( new DIBlob( 'Bar' ) )
		);

		$query = new Query( $description );
		$query->setSubject( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			)
		);

		#2 uses inverse property declaration
		$description = new SomeProperty(
			new DIProperty( 'Foobar', true ),
			new ValueDescription( DIWikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query( $description );
		$query->setSubject( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
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

		$query->setSubject( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
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

		$query->setSubject( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY )
			)
		);

		#5
		$description = new ClassDescription(
			DIWikiPage::newFromText( 'Foocat', NS_CATEGORY )
		);

		$query = new Query( $description );
		$query->setSubject( $subject );

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
		$query->setSubject( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'FooConcept', SMW_NS_CONCEPT )
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
		$query->setSubject( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY ),
				DIWikiPage::newFromText( 'Foobaz', SMW_NS_PROPERTY )
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
		$query->setSubject( $subject );

		$provider[] = array(
			$query,
			array(
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ),
				DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY ),
				DIWikiPage::newFromText( 'Foobaz', SMW_NS_PROPERTY )
			)
		);

		return $provider;
	}

}
