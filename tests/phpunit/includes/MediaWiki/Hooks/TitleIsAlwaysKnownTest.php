<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\TitleIsAlwaysKnown;

/**
 * @covers \SMW\MediaWiki\Hooks\TitleIsAlwaysKnown
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class TitleIsAlwaysKnownTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$result = '';

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\TitleIsAlwaysKnown',
			new TitleIsAlwaysKnown( $title, $result )
		);
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testPerformUpdate( $namespace, $text, $expected ) {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( $namespace ) );

		$title->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $text ) );

		$result = '';

		$instance = new TitleIsAlwaysKnown( $title, $result );
		$this->assertTrue( $instance->process() );

		$this->assertEquals( $expected,  $result );
	}

	public function titleProvider() {

		$provider = array(
			array( SMW_NS_PROPERTY, 'Modification date', true ),
			array( SMW_NS_PROPERTY, 'Foo', false ),
			array( NS_MAIN, 'Modification date', false ),
		);

		return $provider;
	}

}
