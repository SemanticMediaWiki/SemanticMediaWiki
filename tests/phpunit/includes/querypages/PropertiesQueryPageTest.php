<?php

namespace SMW\Test;

use SMW\PropertiesQueryPage;
use SMW\MessageFormatter;
use SMW\ArrayAccessor;

use SMWDataItem;

/**
 * @covers \SMW\PropertiesQueryPage
 * @covers \SMW\QueryPage
 *
 *
 * @group SMW
 * @group SMWExtension
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PropertiesQueryPageTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\PropertiesQueryPage';
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
	 * @return PropertiesQueryPage
	 */
	private function newInstance( $result = null, $values = array(), $settings = array() ) {

		$listLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\ListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->any() )
			->method( 'fetchList' )
			->will( $this->returnValue( $result ) );

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'getPropertyValues'    => $values,
			'getPropertiesSpecial' => $listLookup
		) );

		if ( $settings === array() ) {
			$settings = array(
				'smwgPDefaultType'              => '_wpg',
				'smwgPropertyLowUsageThreshold' => 5,
				'smwgPropertyZeroCountDisplay'  => true
			);
		}

		$instance = new PropertiesQueryPage( $mockStore, $this->newSettings( $settings ) );
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
	 * @since 1.9
	 */
	public function testFormatResultDIError() {

		$skin = $this->getMock( 'Skin' );

		$instance = $this->newInstance();
		$error    = $this->newRandomString();
		$diError  = $this->newMockBuilder()->newObject( 'DIError', array(
			'getErrors' => $error
		) );

		$result   = $instance->formatResult(
			$skin,
			array( $diError, null )
		);

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
	 * @dataProvider getUserDefinedDataProvider
	 *
	 * @note Title, wikiPage, and property label are randomized therefore
	 * the expected comparison value is determined after the property object
	 * has been mocked
	 *
	 * @since 1.9
	 */
	public function testFormatPropertyItemUserDefined( $isUserDefined ) {

		$skin = $this->getMock( 'Skin' );

		// Title exists
		$count    = rand();
		$instance = $this->newInstance();
		$property = $this->newMockBuilder()->newObject( 'DIProperty', array(
			'isUserDefined' => $isUserDefined,
			'getDiWikiPage' => $this->getMockDIWikiPage( true ),
			'getLabel'      => $this->newRandomString(),
		) );

		$expected = $property->getDiWikiPage()->getTitle()->getText();
		$result   = $instance->formatResult( $skin, array( $property, $count ) );

		$this->assertInternalType( 'string', $result );
		$this->assertContains( $expected, $result );

		// Title does not exists
		$count    = rand();
		$instance = $this->newInstance();

		$property = $this->newMockBuilder()->newObject( 'DIProperty', array(
			'isUserDefined' => $isUserDefined,
			'getDiWikiPage' => $this->getMockDIWikiPage( false ),
			'getLabel'      => $this->newRandomString(),
		) );

		$expected = $property->getDiWikiPage()->getTitle()->getText();
		$result   = $instance->formatResult( $skin, array( $property, $count ) );

		$this->assertInternalType( 'string', $result );
		$this->assertContains( $expected, $result );

		// Multiple entries
		$count    = rand();
		$multiple = array( $this->getMockDIWikiPage(), $this->getMockDIWikiPage() );

		$property = $this->newMockBuilder()->newObject( 'DIProperty', array(
			'isUserDefined' => $isUserDefined,
			'getDiWikiPage' => $this->getMockDIWikiPage( true ),
			'getLabel'      => $this->newRandomString(),
		) );

		$expected = $property->getDiWikiPage()->getTitle()->getText();
		$instance = $this->newInstance( null, $multiple );

		$result   = $instance->formatResult( $skin, array( $property, $count ) );

		$this->assertInternalType( 'string', $result );
		$this->assertContains( $expected, $result );

	}

	/**
	 * @since 1.9
	 */
	public function testFormatPropertyItemZeroDisplay() {

		$skin = $this->getMock( 'Skin' );

		$count    = 0;
		$instance = $this->newInstance( null, array(), array(
			'smwgPropertyZeroCountDisplay' => false
		) );

		$property = $this->newMockBuilder()->newObject( 'DIProperty', array(
			'isUserDefined' => true,
			'getDiWikiPage' => $this->getMockDIWikiPage( true ),
			'getLabel'      => $this->newRandomString(),
		) );

		$result = $instance->formatResult( $skin, array( $property, $count ) );

		$this->assertInternalType( 'string', $result );
		$this->assertEmpty( $result );
	}

	/**
	 * @since 1.9
	 */
	public function testFormatPropertyItemTitleNull() {

		$skin = $this->getMock( 'Skin' );

		$count    = rand();
		$instance = $this->newInstance();

		$property = $this->newMockBuilder()->newObject( 'DIProperty', array(
			'isUserDefined' => true,
			'getLabel'      => $this->newRandomString(),
		) );

		$expected = $property->getLabel();
		$result   = $instance->formatResult( $skin, array( $property, $count ) );

		$this->assertInternalType( 'string', $result );
		$this->assertContains( $expected, $result );
	}

	/**
	 * @since 1.9
	 */
	public function testFormatPropertyItemLowUsageThreshold() {

		$skin = $this->getMock( 'Skin' );

		$count    = rand();
		$instance = $this->newInstance( null, array(), array(
			'smwgPropertyLowUsageThreshold' => $count + 1,
			'smwgPDefaultType' => '_wpg'
		) );

		$property = $this->newMockBuilder()->newObject( 'DIProperty', array(
			'isUserDefined' => true,
			'getDiWikiPage' => $this->getMockDIWikiPage( true ),
			'getLabel'      => $this->newRandomString(),
		) );

		$expected = $property->getLabel();
		$result   = $instance->formatResult( $skin, array( $property, $count ) );

		$this->assertInternalType( 'string', $result );
		$this->assertContains( $expected, $result );
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

	/**
	 * @since 1.9
	 */
	public function testGetPageHeader() {

		$propertySearch = $this->newRandomString();

		$context = $this->newContext( array( 'property' => $propertySearch ) );
		$context->setTitle( $this->newTitle() );

		$instance = $this->newInstance();
		$instance->setContext( $context );
		$instance->getResults( null );

		$reflector = $this->newReflector();
		$selectOptions = $reflector->getProperty( 'selectOptions' );
		$selectOptions->setAccessible( true );
		$selectOptions->setValue( $instance, array(
			'offset' => 1,
			'limit'  => 2,
			'end'    => 5,
			'count'  => 4
		) );

		$matcher = array(
			'tag' => 'p',
			'attributes' => array( 'class' => 'smw-sp-properties-docu' ),
			'tag' => 'input',
			'attributes' => array( 'name' => 'property', 'value' => $propertySearch ),
			'tag' => 'input',
			'attributes' => array( 'type' => 'submit' )
		);

		// https://github.com/sebastianbergmann/phpunit/issues/1380
		// $this->assertTag( $matcher, $instance->getPageHeader() );
		$this->assertContains( $propertySearch, $instance->getPageHeader() );
	}

}
