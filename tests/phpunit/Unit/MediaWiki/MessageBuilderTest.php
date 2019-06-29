<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\MessageBuilder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\MessageBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class MessageBuilderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MessageBuilder',
			new MessageBuilder( $language )
		);
	}

	public function testFormatNumberToText() {

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->once() )
			->method( 'formatNum' );

		$instance = new MessageBuilder();

		$instance
			->setLanguage( $language )
			->formatNumberToText( 42 );
	}

	public function testListToCommaSeparatedText() {

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->once() )
			->method( 'listToText' );

		$context = $this->getMockBuilder( '\IContextSource' )
			->disableOriginalConstructor()
			->getMock();

		$context->expects( $this->once() )
			->method( 'getLanguage' )
			->will( $this->returnValue( $language ) );

		$instance = new MessageBuilder();

		$instance
			->setLanguageFromContext( $context )
			->listToCommaSeparatedText( [ 'a', 'b' ] );
	}

	public function testPrevNextToText() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		if ( class_exists( 'MediaWiki\Navigation\PrevNextNavigationRenderer' ) ) {
			$instance = new MessageBuilder( $language );
			$html = $instance->prevNextToText( $title, 20, 0, [], false );

			$this->assertStringStartsWith( 'View (previous 20', $html );

			preg_match_all( '!<a.*?</a>!', $html, $m, PREG_PATTERN_ORDER );
			$links = $m[0];

			$nums= [20, 50, 100, 250, 500];
			$i = 0;
			foreach ( $links as $index => $a ) {
				if ( $index < 1 ) {
					$this->assertContains( 'class="mw-nextlink"', $a );
					$this->assertContains( '>next 20<', $a );
					continue;
				}
				$this->assertContains( 'class="mw-numlink"', $a );
				$this->assertContains( 'title="Show ' . $nums[$i] . ' results per page"', $a );
				$this->assertContains( ">$nums[$i]<", $a );
				$i+=1;
			}
		} else {
			$language->expects( $this->once() )
				->method( 'viewPrevNext' );
			$instance = new MessageBuilder( $language );
			$instance->prevNextToText( $title, 20, 0, [], false );
		}

	}

	public function testGetForm() {

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new MessageBuilder( $language );

		$this->assertInstanceOf(
			'\Message',
			$instance->getMessage( 'properties' )
		);
	}

	public function testNullLanguageThrowsException() {

		$instance = new MessageBuilder();

		$this->setExpectedException( 'RuntimeException' );
		$instance->getMessage( 'properties' );
	}

}
