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

		$this->assertInstanceOf(
			$this->getClass(),
			new SMWParamSource( 'Foo',  'Bar' )
		);
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testFormatWithUnknownSource() {

		list( $param, $definitions ) = $this->buildMockParamAndDefinitions( 'foo' );

		$instance = new SMWParamSource( 'Foo', 'Bar' );
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

		list( $param, $definitions ) = $this->buildMockParamAndDefinitions( 'wiki' );

		$instance = new SMWParamSource( 'Foo', 'Bar' );
		$instance->format( $param, $definitions, array() );

		$this->assertTrue( true );
		$GLOBALS['smwgQuerySources'] = $source;
	}

	protected function buildMockParamAndDefinitions( $value ) {

		$param = $this->getMockBuilder( '\ParamProcessor\IParam' )
			->disableOriginalConstructor()
			->setMethods( array( 'setValue', 'getValue' ) )
			->getMock();

		$param->expects( $this->any() )
			->method( 'getValue' )
			->will( $this->returnValue( $value ) );

		$paramDefinition = $this->getMockBuilder( '\ParamProcessor\ParamDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$paramDefinition->expects( $this->any() )
			->method( 'getAllowedValues' )
			->will( $this->returnValue( array( $value ) ) );

		$definitions = array(
			'source' => $paramDefinition
		);

		return array( $param, $definitions );
	}

}
