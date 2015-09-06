<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\MessageBuilder;

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
			->listToCommaSeparatedText( array( 'a', 'b' ) );
	}

	public function testPrevNextToText() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->once() )
			->method( 'viewPrevNext' );

		$instance = new MessageBuilder( $language );
		$instance->prevNextToText( $title, 20, 0, array(), false );
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
