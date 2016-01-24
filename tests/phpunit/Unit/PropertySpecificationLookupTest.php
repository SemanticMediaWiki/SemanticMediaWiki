<?php

namespace SMW\Tests;

use SMW\PropertySpecificationLookup;
use SMW\DIProperty;
use SMWDIContainer as DIContainer;
use SMWContainerSemanticData as ContainerSemanticData;

/**
 * @covers \SMW\PropertySpecificationLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PropertySpecificationLookupTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\PropertySpecificationLookup',
			new PropertySpecificationLookup( $this->store )
		);
	}

	public function testGetPropertyDescriptionForPredefinedProperty() {

		$instance = new PropertySpecificationLookup(
			$this->store
		);

		$this->assertInternalType(
			'string',
			$instance->getPropertyDescriptionFor( new DIProperty( '_PDESC' ) )
		);
	}

	public function testGetPropertyDescriptionForPredefinedPropertyViaCacheForLanguageCode() {

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$cache->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( true ) );

		$cache->expects( $this->once() )
			->method( 'fetch' )
			->with( $this->stringContains( 'foo:smw:pspec:' ) )
			->will( $this->returnValue( array( 'en:-' => 1001 ) ) );

		$instance = new PropertySpecificationLookup(
			$this->store,
			$cache
		);

		$instance->setCachePrefix( 'foo' );
		$instance->setLanguageCode( 'en' );

		$this->assertEquals(
			1001,
			$instance->getPropertyDescriptionFor( new DIProperty( '_PDESC' ) )
		);
	}

	public function testTryToGetLocalPropertyDescriptionForUserdefinedProperty() {

		$property = new DIProperty( 'SomeProperty' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $property->getDiWikiPage() ),
				$this->equalTo( new DIProperty( '_PDESC' ) ),
				$this->anything() )
			->will( $this->returnValue( array(
				new DIContainer( ContainerSemanticData::makeAnonymousContainer() ) ) ) );

		$instance = new PropertySpecificationLookup(
			$this->store
		);

		$this->assertInternalType(
			'string',
			$instance->getPropertyDescriptionFor( $property )
		);
	}

}
