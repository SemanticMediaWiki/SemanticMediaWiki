<?php

namespace SMW\Test;

use SMW\WantedPropertiesQueryPage;
use SMW\Settings;

use SMWDataItem;

/**
 * @covers \SMW\WantedPropertiesQueryPage
 * @covers \SMW\QueryPage
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class WantedPropertiesQueryPageTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\WantedPropertiesQueryPage';
	}

	/**
	 * @since 1.9
	 *
	 * @return DIWikiPage
	 */
	private function getMockDIWikiPage( $exists = true ) {

		$text  = $this->newRandomString();

		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'exists'  => $exists,
			'getText' => $text,
			'getNamespace'    => NS_MAIN,
			'getPrefixedText' => $text
		) );

		$diWikiPage = $this->newMockBuilder()->newObject( 'DIWikiPage', array(
			'getTitle'  => $title,
		) );

		return $diWikiPage;
	}

	/**
	 * @since 1.9
	 *
	 * @return WantedPropertiesQueryPage
	 */
	private function newInstance( $result = null ) {

		$listLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\ListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->any() )
			->method( 'fetchList' )
			->will( $this->returnValue( $result ) );

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'getPropertyValues'          => array(),
			'getWantedPropertiesSpecial' => $listLookup
		) );

		$instance = new WantedPropertiesQueryPage( $mockStore, $this->newSettings() );
		$instance->setContext( $this->newContext() );

		return $instance;
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider getUserDefinedDataProvider
	 *
	 * @since 1.9
	 */
	public function testFormatResult( $isUserDefined ) {

		$instance = $this->newInstance();
		$skin     = $this->getMock( 'Skin' );

		$count    = rand();
		$property = $this->newMockBuilder()->newObject( 'DIProperty', array(
			'isUserDefined' => $isUserDefined,
			'getDiWikiPage' => $this->getMockDIWikiPage( true ),
			'getLabel'      => $this->newRandomString(),
		) );

		$expected = $isUserDefined ? (string)$count : '';
		$result   = $instance->formatResult( $skin, array( $property, $count ) );

		$this->assertInternalType( 'string', $result );
		$isUserDefined ? $this->assertContains( $expected, $result ) : $this->assertEmpty( $result );

	}

	/**
	 * @return array
	 */
	public function getUserDefinedDataProvider() {
		return array( array( true ), array( false ) );
	}

	/**
	 * @since 1.9
	 */
	public function testGetResults() {

		$expected = 'Lala';
		$instance = $this->newInstance( $expected );

		$this->assertEquals( $expected, $instance->getResults( null ) );

	}

	public function testFormatResultOnNonUserDefinedProperty() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$setttings = Settings::newFromArray( array() );

		$instance = new WantedPropertiesQueryPage( $store, $setttings );
		$result = $instance->formatResult( $skin, array( 'foo', 0 ) );

		$this->assertInternalType( 'string', $result );
		$this->assertEmpty( $result );
	}

}
