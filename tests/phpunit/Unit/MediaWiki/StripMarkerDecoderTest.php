<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\StripMarkerDecoder;
use Title;

/**
 * @covers \SMW\MediaWiki\StripMarkerDecoder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since  3.0
 *
 * @author mwjames
 */
class StripMarkerDecoderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$stripState = $this->getMockBuilder( '\StripState' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			StripMarkerDecoder::class,
			new StripMarkerDecoder( $stripState )
		);
	}

	public function testIsSupported() {

		$stripState = $this->getMockBuilder( '\StripState' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new StripMarkerDecoder(
			$stripState
		);

		$instance->isSupported( true );

		$this->assertTrue(
			$instance->canUse()
		);
	}

	public function testDecodeWithoutStrip() {

		$stripState = $this->getMockBuilder( '\StripState' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new StripMarkerDecoder(
			$stripState
		);

		$instance->isSupported( true );

		$this->assertEquals(
			'<pre>Foo</pre>',
			$instance->decode( '<pre>Foo</pre>' )
		);
	}

	public function testUnstripNoWiki() {

		$stripState = $this->getMockBuilder( '\StripState' )
			->disableOriginalConstructor()
			->getMock();

		$stripState->expects( $this->once() )
			->method( 'unstripNoWiki' )
			->will( $this->returnArgument( 0 ) );

		$instance = new StripMarkerDecoder(
			$stripState
		);

		$this->assertEquals(
			'&lt;nowiki&gt;&lt;pre&gt;Foo&lt;/pre&gt;&lt;/nowiki&gt;',
			$instance->unstrip( '<pre>Foo</pre>' )
		);
	}

	public function testUnstripGeneral() {

		$stripState = $this->getMockBuilder( '\StripState' )
			->disableOriginalConstructor()
			->getMock();

		$stripState->expects( $this->once() )
			->method( 'unstripNoWiki' )
			->will( $this->returnValue( '' ) );

		$stripState->expects( $this->once() )
			->method( 'unstripGeneral' );

		$instance = new StripMarkerDecoder(
			$stripState
		);

		$instance->isSupported( true );

		$instance->unstrip( '<pre>Foo</pre>' );
	}

}
