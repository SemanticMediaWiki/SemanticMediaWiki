<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\RequestOptions;
use SMW\SQLStore\EntityStore\CachingSemanticDataLookup;
use SMW\SQLStore\EntityStore\SemanticDataLookup;
use SMW\SQLStore\EntityStore\StubSemanticData;
use SMW\SQLStore\PropertyTableDefinition;

/**
 * @covers \SMW\SQLStore\EntityStore\CachingSemanticDataLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class CachingSemanticDataLookupTest extends TestCase {

	private $store;
	private $connection;
	private $semanticDataLookup;

	public function setUp(): void {
		parent::setUp();

		$this->semanticDataLookup = $this->getMockBuilder( SemanticDataLookup::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function tearDown(): void {
		CachingSemanticDataLookup::clear();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CachingSemanticDataLookup::class,
			new CachingSemanticDataLookup( $this->semanticDataLookup )
		);
	}

	public function testInitLookupCache() {
		$subject = WikiPage::newFromText( 'Foo' );

		$stubSemanticData = $this->getMockBuilder( StubSemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$stubSemanticData->expects( $this->once() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'newStubSemanticData' )
			->willReturn( $stubSemanticData );

		$instance = new CachingSemanticDataLookup(
			$this->semanticDataLookup
		);

		$instance->initLookupCache( 42, $subject );

		$this->assertInstanceOf(
			StubSemanticData::class,
			$instance->getSemanticDataById( 42 )
		);
	}

	public function testUninitializedGetSemanticDataByIdThrowsException() {
		$instance = new CachingSemanticDataLookup(
			$this->semanticDataLookup
		);

		$this->expectException( '\RuntimeException' );
		$instance->getSemanticDataById( 42 );
	}

	public function testSetLookupCache() {
		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'newStubSemanticData' )
			->willReturn( $semanticData );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'getTableUsageInfo' )
			->willReturn( [] );

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
		$propertyTableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$property = $this->getMockBuilder( Property::class )
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
		$propertyTableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
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
		$subject = WikiPage::newFromText( 'Foo' );

		$propertyTableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$stubSemanticData = $this->getMockBuilder( StubSemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'newStubSemanticData' )
			->willReturn( $stubSemanticData );

		$instance = new CachingSemanticDataLookup(
			$this->semanticDataLookup
		);

		$this->assertInstanceOf(
			StubSemanticData::class,
			$instance->newStubSemanticData( $subject )
		);
	}

	public function testGetSemanticData_Uncached() {
		$propertyTableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$requestOptions = $this->getMockBuilder( RequestOptions::class )
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
		$subject = WikiPage::newFromText( 'Foo' );

		$propertyTableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$stubSemanticData = $this->getMockBuilder( StubSemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$stubSemanticData->expects( $this->once() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'newStubSemanticData' )
			->willReturn( $stubSemanticData );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'fetchSemanticDataFromTable' )
			->willReturn( [] );

		$instance = new CachingSemanticDataLookup(
			$this->semanticDataLookup
		);

		$instance->getSemanticData( 42, $subject, $propertyTableDefinition );
	}

	public function testGetSemanticData_FromStaticCache() {
		$subject = WikiPage::newFromText( 'Foo' );

		$propertyTableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDefinition->expects( $this->once() )
			->method( 'getName' )
			->willReturn( '__foo__' );

		$stubSemanticData = $this->getMockBuilder( StubSemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$stubSemanticData->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'newStubSemanticData' )
			->willReturn( $stubSemanticData );

		$this->semanticDataLookup->expects( $this->once() )
			->method( 'getTableUsageInfo' )
			->willReturn( [ '__foo__' => true ] );

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
