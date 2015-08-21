<?php

namespace SMW\Test;

use SMW\UnusedPropertiesQueryPage;
use SMW\MessageFormatter;

use SMWDataItem;

/**
 * @covers \SMW\UnusedPropertiesQueryPage
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
class UnusedPropertiesQueryPageTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\UnusedPropertiesQueryPage';
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
	 * @return UnusedPropertiesQueryPage
	 */
	private function newInstance( $result = null, $values = array() ) {

		$listLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\ListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->any() )
			->method( 'fetchList' )
			->will( $this->returnValue( $result ) );

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'getPropertyValues'          => $values,
			'getUnusedPropertiesSpecial' => $listLookup
		) );

		$instance = new UnusedPropertiesQueryPage( $mockStore, $this->newSettings() );
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

		// Skin stub object
		$skin = $this->getMock( 'Skin' );

		// DIProperty
		$instance = $this->newInstance();

		$property = $this->newMockBuilder()->newObject( 'DIProperty', array(
			'isUserDefined' => $isUserDefined,
			'getDiWikiPage' => $this->getMockDIWikiPage( true ),
			'getLabel'      => $this->newRandomString(),
		) );

		$expected = $property->getDiWikiPage()->getTitle()->getText();
		$result   = $instance->formatResult( $skin, $property );

		$this->assertInternalType( 'string', $result );
		$this->assertContains( $expected, $result );

		// Multiple entries
		$instance = $this->newInstance();
		$multiple = array( $this->getMockDIWikiPage(), $this->getMockDIWikiPage() );

		$property = $this->newMockBuilder()->newObject( 'DIProperty', array(
			'isUserDefined' => $isUserDefined,
			'getDiWikiPage' => $this->getMockDIWikiPage( true ),
			'getLabel'      => $this->newRandomString(),
		) );

		$expected = $property->getDiWikiPage()->getTitle()->getText();
		$instance = $this->newInstance( null, $multiple );

		$result   = $instance->formatResult( $skin, $property );

		$this->assertInternalType( 'string', $result );
		$this->assertContains( $expected, $result );

		// DIError
		$instance = $this->newInstance();
		$error    = $this->newRandomString();
		$diError  = $this->newMockBuilder()->newObject( 'DIError', array(
			'getErrors' => $error
		) );

		$result   = $instance->formatResult( $skin, $diError );

		$this->assertInternalType( 'string', $result );
		$this->assertContains( $error, $result );

	}

	/**
	 * @since 1.9
	 */
	public function testInvalidResultException() {

		$this->setExpectedException( '\SMW\InvalidResultException' );

		$instance = $this->newInstance();
		$skin = $this->getMock( 'Skin' );

		$this->assertInternalType( 'string', $instance->formatResult( $skin, null ) );

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
}
