<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIWikiPage;
use SMW\SQLStore\EntityStore\CachingSemanticDataLookup;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\EntityStore\CachingSemanticDataLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CachingSemanticDataLookupTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $connection;
	private $semanticDataLookup;

	public function setUp() {
		parent::setUp();

		$this->semanticDataLookup = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\SemanticDataLookup' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function tearDown() {
		CachingSemanticDataLookup::clear();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			CachingSemanticDataLookup::class,
			new CachingSemanticDataLookup( $this->semanticDataLookup )
		);
	}

	public function testInitLookupCache() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$stubSemanticData = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\StubSemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$stubSemanticData->expects( $this->once() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'newStubSemanticData' )
			->will( $this->returnValue( $stubSemanticData ) );

		$instance = new CachingSemanticDataLookup(
			$this->semanticDataLookup
		);

		$instance->initLookupCache( 42, $subject );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\StubSemanticData',
			$instance->getSemanticDataById( 42 )
		);
	}

	public function testUninitializedGetSemanticDataByIdThrowsException() {

		$instance = new CachingSemanticDataLookup(
			$this->semanticDataLookup
		);

		$this->setExpectedException( '\RuntimeException' );
		$instance->getSemanticDataById( 42 );
	}

	public function testSetLookupCache() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'newStubSemanticData' )
			->will( $this->returnValue( $semanticData ) );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'getTableUsageInfo' )
			->will( $this->returnValue( [] ) );

		$instance = new CachingSemanticDataLookup(
			$this->semanticDataLookup
		);

		$instance->setLookupCache( 42, $semanticData );

		$this->assertEquals(
			$semanticData,
			$instance->getSemanticDataById( 42 )
		);
	}

	public function testGetOptionsFromConstraint() {

		$propertyTableDefinition = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'newRequestOptions' );

		$instance = new CachingSemanticDataLookup(
			$this->semanticDataLookup
		);

		$instance->newRequestOptions( $propertyTableDefinition, $property );
	}

	public function testFetchSemanticDataFromTable() {

		$propertyTableDefinition = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'fetchSemanticDataFromTable' );

		$instance = new CachingSemanticDataLookup(
			$this->semanticDataLookup
		);

		$instance->fetchSemanticDataFromTable( 42, null, $propertyTableDefinition );
	}

	public function testNewStubSemanticData() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$propertyTableDefinition = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$stubSemanticData = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\StubSemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'newStubSemanticData' )
			->will( $this->returnValue( $stubSemanticData ) );

		$instance = new CachingSemanticDataLookup(
			$this->semanticDataLookup
		);

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\StubSemanticData',
			$instance->newStubSemanticData( $subject )
		);
	}

	public function testGetSemanticData_Uncached() {

		$propertyTableDefinition = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$requestOptions = $this->getMockBuilder( '\SMW\RequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'getSemanticData' );

		$instance = new CachingSemanticDataLookup(
			$this->semanticDataLookup
		);

		$instance->getSemanticData( 42, null, $propertyTableDefinition, $requestOptions );
	}

	public function testGetSemanticData_FreshFetch() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$propertyTableDefinition = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$stubSemanticData = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\StubSemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$stubSemanticData->expects( $this->once() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'newStubSemanticData' )
			->will( $this->returnValue( $stubSemanticData ) );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'fetchSemanticDataFromTable' )
			->will( $this->returnValue( [] ) );

		$instance = new CachingSemanticDataLookup(
			$this->semanticDataLookup
		);

		$instance->getSemanticData( 42, $subject, $propertyTableDefinition );
	}

	public function testGetSemanticData_FromStaticCache() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$propertyTableDefinition = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDefinition->expects( $this->once() )
			->method( 'getName' )
			->will( $this->returnValue( '__foo__' ) );

		$stubSemanticData = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\StubSemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$stubSemanticData->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'newStubSemanticData' )
			->will( $this->returnValue( $stubSemanticData ) );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'getTableUsageInfo' )
			->will( $this->returnValue( [ '__foo__' => true ] ) );

		$this->semanticDataLookup->expects( $this->never() )
			->method( 'fetchSemanticDataFromTable' );

		$instance = new CachingSemanticDataLookup(
			$this->semanticDataLookup
		);

		$instance->invalidateCache( 42 );
		$instance->setLookupCache( 42, $stubSemanticData );

		$instance->getSemanticData( 42, $subject, $propertyTableDefinition );
	}

}
