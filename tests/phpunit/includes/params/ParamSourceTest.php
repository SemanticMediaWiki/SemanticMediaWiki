<?php

namespace SMW\Test;

use SMWParamSource;

/**
 * @covers \SMWParamSource
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.1.1
 *
 * @author mwjames
 */
class ParamSourceTest extends \PHPUnit_Framework_TestCase {

	public function getClass() {
		return '\SMWParamSource';
	}

	public function testCanConstruct() {

		$paramDefinition = $this->getMockBuilder( '\ParamProcessor\ParamDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			$this->getClass(),
			new SMWParamSource( 'Foo',  'Bar' )
		);
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testFormatWithUnknownSource() {

		$param = $this->getMockBuilder( '\ParamProcessor\IParam' )
			->disableOriginalConstructor()
			->setMethods( array( 'setValue', 'getValue' ) )
			->getMock();

		$param->expects( $this->any() )
			->method( 'getValue' )
			->will( $this->returnValue( 'foo' ) );

		$paramDefinition = $this->getMockBuilder( '\ParamProcessor\ParamDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$paramDefinition->expects( $this->any() )
			->method( 'getAllowedValues' )
			->will( $this->returnValue( array( 'foo' ) ) );

		$definitions = array(
			'source' => $paramDefinition
		);

		$instance = new SMWParamSource( 'Foo',  'Bar' );
		$instance->format( $param, $definitions, array() );

		$this->assertTrue( true );
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testFormatWithKnownSource() {

		$source = $GLOBALS['smwgQuerySources'];
		$GLOBALS['smwgQuerySources'] = array(
			'wiki' => '\stdClass'
		);

		$param = $this->getMockBuilder( '\ParamProcessor\IParam' )
			->disableOriginalConstructor()
			->setMethods( array( 'setValue', 'getValue' ) )
			->getMock();

		$param->expects( $this->any() )
			->method( 'getValue' )
			->will( $this->returnValue( 'wiki' ) );

		$paramDefinition = $this->getMockBuilder( '\ParamProcessor\ParamDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$paramDefinition->expects( $this->any() )
			->method( 'getAllowedValues' )
			->will( $this->returnValue( array( 'wiki' ) ) );

		$definitions = array(
			'source' => $paramDefinition
		);

		$instance = new SMWParamSource( 'Foo',  'Bar' );
		$instance->format( $param, $definitions, array() );

		$this->assertTrue( true );
		$GLOBALS['smwgQuerySources'] = $source;
	}

}
