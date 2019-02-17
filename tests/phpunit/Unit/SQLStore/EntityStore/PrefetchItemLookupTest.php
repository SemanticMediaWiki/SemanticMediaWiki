<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SQLStore\EntityStore\PrefetchItemLookup;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\EntityStore\PrefetchItemLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class PrefetchItemLookupTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $semanticDataLookup;
	private $requestOptions;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->semanticDataLookup = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\CachingSemanticDataLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestOptions = $this->getMockBuilder( '\SMW\RequestOptions' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			PrefetchItemLookup::class,
			new PrefetchItemLookup( $this->store, $this->semanticDataLookup )
		);
	}

	public function testGetPropertyValues() {

		$subjects = [
			DIWikiPage::newFromText( __METHOD__ ),
		];

		$propertyTableDef = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ 'smw_foo' => $propertyTableDef ] ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'smw_foo' ) );

		$this->semanticDataLookup->expects( $this->atLeastOnce() )
			->method( 'prefetchDataFromTable' )
			->will( $this->returnValue( [ 42 => [ 'Bar#0##' ] ] ) );

		$instance = new PrefetchItemLookup(
			$this->store,
			$this->semanticDataLookup
		);

		$instance->getPropertyValues( $subjects, new DIProperty( 'Foo' ), $this->requestOptions );
	}

	public function testGetPropertyValuesThrowsException() {

		$subjects = [
			DIWikiPage::newFromText( __METHOD__ ),
		];

		$instance = new PrefetchItemLookup(
			$this->store,
			$this->semanticDataLookup
		);

		$this->setExpectedException( '\RuntimeException' );
		$instance->getPropertyValues( $subjects, new DIProperty( 'Foo', true ), $this->requestOptions );
	}

}
