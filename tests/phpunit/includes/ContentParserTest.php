<?php

namespace SMW\Test;

use ContentHandler;
use Parser;
use Revision;
use SMW\ContentParser;
use SMW\Tests\Utils\Mock\MockTitle;
use TextContent;
use TextContentHandler;
use Title;

/**
 * @covers \SMW\ContentParser
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class ContentParserTest extends SemanticMediaWikiTestCase {

	public function getClass() {
		return '\SMW\ContentParser';
	}

	private function newInstance( Title $title = null, $parser = null ) {

		if ( $title === null ) {
			$title = Title::newFromText( __METHOD__ );
		}

		return new ContentParser( $title, $parser );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testCanParseOnInstance() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance()->parse() );
	}

	/**
	 * @depends testCanParseOnInstance
	 */
	public function testRunParseOnText() {

		$text     = 'Foo-1-' . __METHOD__;
		$expected = '<p>' . $text . "\n" . '</p>';

		$this->assertParserOutput( $expected, $this->newInstance()->parse( $text ) );
	}

	/**
	 * @dataProvider titleRevisionDataProvider
	 */
	public function testRunParseOnTitle( $setup, $expected, $withContentHandler = false ) {

		$instance = $this->getMockBuilder( '\SMW\ContentParser' )
			->setConstructorArgs( [ $setup['title'], new Parser() ] )
			->setMethods( [ 'hasContentHandler' ] )
			->getMock();

		$instance->expects( $this->any() )
			->method( 'hasContentHandler' )
			->will( $this->returnValue( $withContentHandler ) );

		$instance->setRevision( $setup['revision'] );

		$this->assertInstanceAfterParse( $expected, $instance->parse() );

	}

	/**
	 * @dataProvider contentDataProvider
	 *
	 * @since 1.9
	 */
	public function testRunParseOnTitleWithContentHandler( $setup, $expected ) {

		if ( !class_exists( 'ContentHandler') ) {
			$this->markTestSkipped(
				'Skipping test due to missing class (probably MW 1.21 or lower).'
			);
		}

		$this->testRunParseOnTitle( $setup, $expected, true );
	}

	protected function assertInstanceAfterParse( $expected, $instance ) {

		$this->assertInstanceOf( $this->getClass(), $instance );

		if ( $expected['error'] ) {
			return $this->assertError( $instance );
		}

		$this->assertParserOutput( $expected['text'], $instance );
	}

	protected function assertError( $instance ) {
		$this->assertInternalType( 'array', $instance->getErrors() );
		$this->assertNotEmpty( $instance->getErrors() );
	}

	protected function assertParserOutput( $text, $instance ) {

		$this->assertInstanceOf( 'ParserOutput', $instance->getOutput() );

		if ( $text !== '' ) {

			return $this->assertContains(
				$text,
				$instance->getOutput()->getText(),
				'Asserts that getText() returns expected text component'
			);

		}

		$this->assertEmpty( $instance->getOutput()->getText() );
	}

	/**
	 * @return array
	 */
	public function titleRevisionDataProvider() {

		$provider = [];

		$text     = 'Foo-2-' . __METHOD__;
		$expected ='<p>' . $text . "\n" . '</p>';

		// #0 Title does not exists
		$title = $this->newMockBuilder()->newObject( 'Title', [
			'getDBkey'        => 'Lila',
			'exists'          => false,
			'getText'         => null,
			'getPageLanguage' => $this->getLanguage()
		] );

		$provider[] = [
			[
				'title'    => $title,
				'revision' => null,
			],
			[
				'error'    => true,
				'text'     => ''
			]
		];

		// #1 Valid revision
		// Required by MW 1.29, method got removed
		if ( method_exists( 'Revision', 'getText' ) ) {
			$title = $this->newMockBuilder()->newObject( 'Title', [
				'getDBkey'        => 'Lula',
				'exists'          => true,
				'getPageLanguage' => $this->getLanguage()
			] );

			$revision = $this->newMockBuilder()->newObject( 'Revision', [
				'getId'   => 9001,
				'getUser' => 'Lala',
				'getText' => $text,
			] );

			$provider[] = [
				[
					'title'    => $title,
					'revision' => $revision,
				],
				[
					'error'    => false,
					'text'     => $expected
				]
			];
		}

		// #2 Null revision
		$title = $this->newMockBuilder()->newObject( 'Title', [
			'getDBkey'        => 'Lula',
			'exists'          => true,
			'getPageLanguage' => $this->getLanguage()
		] );

		$revision = null;

		$provider[] = [
			[
				'title'    => $title,
				'revision' => $revision,
			],
			[
				'error'    => true,
				'text'     => ''
			]
		];

		return $provider;
	}

	/**
	 * @return array
	 */
	public function contentDataProvider() {

		$provider = [];

		if ( !class_exists( 'ContentHandler') ) {
			$provider[] = [ [], [] ];
			return $provider;
		}

		$text     = 'Foo-3-' . __METHOD__;

		// #0 Title does not exists
		$title = MockTitle::buildMock( 'Lila' );

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( false ) );

		$title->expects( $this->any() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $this->getLanguage() ) );

		$provider[] = [
			[
				'title'    => $title,
				'revision' => null,
			],
			[
				'error'    => true,
				'text'     => ''
			]
		];

		// #1 Valid revision
		$title = $this->newMockBuilder()->newObject( 'Title', [
			'getDBkey'        => 'Lula',
			'exists'          => true,
			'getPageLanguage' => $this->getLanguage()
		] );

		$revision = $this->newMockBuilder()->newObject( 'Revision', [
			'getId'   => 9001,
			'getUser' => 'Lala',
			'getContent' => new TextContent( $text )
		] );

		$provider[] = [
			[
				'title'    => $title,
				'revision' => $revision,
			],
			[
				'error'    => false,
				'text'     => $text
			]
		];

		// #1 Empty content
		$title = $this->newMockBuilder()->newObject( 'Title', [
			'getDBkey'        => 'Lula',
			'exists'          => true,
			'getPageLanguage' => $this->getLanguage(),
			'getContentModel' => CONTENT_MODEL_WIKITEXT
		] );

		$revision = $this->newMockBuilder()->newObject( 'Revision', [
			'getId'             => 9001,
			'getUser'           => 'Lala',
			'getContent'        => false,
			'getContentHandler' => new TextContentHandler()
		] );

		$provider[] = [
			[
				'title'    => $title,
				'revision' => $revision,
			],
			[
				'error'    => false,
				'text'     => ''
			]
		];

		// #2 "real" revision and content
		$title    = $this->newTitle();
		$content  = ContentHandler::makeContent( $text, $title, CONTENT_MODEL_WIKITEXT, null );

		$revision = new Revision(
			[
				'id'         => 42,
				'page'       => 23,
				'title'      => $title,

				'content'    => $content,
				'length'     => $content->getSize(),
				'comment'    => "testing",
				'minor_edit' => false,

				'content_format' => null,
			]
		);

		$provider[] = [
			[
				'title'    => $title,
				'revision' => $revision,
			],
			[
				'error'    => false,
				'text'     => $text
			]
		];

		return $provider;
	}

}
