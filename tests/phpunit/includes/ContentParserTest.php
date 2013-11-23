<?php

namespace SMW\Test;

use ContentHandler;
use Parser;
use Revision;

use SMW\ContentParser;
use TextContent;
use TextContentHandler;
use Title;

/**
 * @covers \SMW\ContentParser
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class ContentParserTest extends ParserTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ContentParser';
	}

	/**
	 * @since 1.9
	 *
	 * @return ContentParser
	 */
	private function newInstance( Title $title = null, $parser = null ) {

		if ( $title === null ) {
			$title = $this->newTitle();
		}

		return new ContentParser( $title, $parser );
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
	public function testParse() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance()->parse() );
	}

	/**
	 * @since 1.9
	 */
	public function testParseFromText() {

		$text     = 'Foo-1-' . __METHOD__;
		$expected = '<p>' . $text . "\n" . '</p>';

		$instance = $this->newInstance();
		$instance->parse( $text );

		$this->assertParserOutput( $expected, $instance );

	}

	/**
	 * @dataProvider titleRevisionDataProvider
	 *
	 * @since 1.9
	 */
	public function testFetchFromParser( $setup, $expected ) {

		$instance  = $this->newInstance( $setup['title'], new Parser() );
		$reflector = $this->newReflector();

		$fetchFromParser = $reflector->getMethod( 'fetchFromParser' );
		$fetchFromParser->setAccessible( true );

		$fetchFromParser->invoke( $instance, $setup['revision'] );

		if ( $expected['error'] ) {
			$this->assertError( $instance );
		} else {
			$this->assertParserOutput( $expected['text'], $instance );
		}

	}

	/**
	 * @dataProvider contentDataProvider
	 *
	 * @since 1.9
	 */
	public function testFetchFromContent( $setup, $expected ) {

		if ( !class_exists( 'ContentHandler') ) {
			$this->markTestSkipped(
				'Skipping test due to a missing class (probably MW 1.20 or lower).'
			);
		}

		$instance  = $this->newInstance( $setup['title'], new Parser() );
		$reflector = $this->newReflector();

		$fetchFromContent = $reflector->getMethod( 'fetchFromContent' );
		$fetchFromContent->setAccessible( true );

		$fetchFromContent->invoke( $instance, $setup['revision'] );

		$this->assertEquals( $setup['title'], $instance->getTitle() );

		if ( $expected['error'] ) {
			$this->assertError( $instance );
		} else {
			$this->assertParserOutput( $expected['text'], $instance );
		}

	}

	/**
	 * @since 1.9
	 */
	public function assertError( $instance ) {

		$this->assertInternalType(
			'array',
			$instance->getErrors(),
			'Asserts that getErrors() returns an array'
		);

		$this->assertNotEmpty(
			$instance->getErrors(),
			'Asserts that getErrors() is not empty'
		);

	}

	/**
	 * @since 1.9
	 */
	public function assertParserOutput( $text, $instance ) {

		$this->assertInstanceOf(
			'ParserOutput',
			$instance->getOutput(),
			'Asserts the expected ParserOutput instance'
		);

		if ( $text !== '' ) {

			$this->assertContains(
				$text,
				$instance->getOutput()->getText(),
				'Asserts that getText() returns expected text component'
			);

		} else {

			$this->assertEmpty(
				$instance->getOutput()->getText(),
				'Asserts that getText() returns empty'
			);

		}

	}

	/**
	 * @return array
	 */
	public function titleRevisionDataProvider() {

		$provider = array();

		$text     = 'Foo-2-' . __METHOD__;
		$expected ='<p>' . $text . "\n" . '</p>';

		// #0 Title does not exists
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'        => 'Lila',
			'exists'          => false,
			'getText'         => null,
			'getPageLanguage' => $this->getLanguage()
		) );

		$provider[] = array(
			array(
				'title'    => $title,
				'revision' => null,
			),
			array(
				'error'    => true,
				'text'     => ''
			)
		);

		// #1 Valid revision
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'        => 'Lula',
			'exists'          => true,
			'getPageLanguage' => $this->getLanguage()
		) );

		$revision = $this->newMockBuilder()->newObject( 'Revision', array(
			'getId'   => 9001,
			'getUser' => 'Lala',
			'getText' => $text,
		) );

		$provider[] = array(
			array(
				'title'    => $title,
				'revision' => $revision,
			),
			array(
				'error'    => false,
				'text'     => $expected
			)
		);

		// #2 Null revision
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'        => 'Lula',
			'exists'          => true,
			'getPageLanguage' => $this->getLanguage()
		) );

		$revision = null;

		$provider[] = array(
			array(
				'title'    => $title,
				'revision' => $revision,
			),
			array(
				'error'    => true,
				'text'     => ''
			)
		);

		return $provider;
	}

	/**
	 * @return array
	 */
	public function contentDataProvider() {

		$provider = array();

		if ( !class_exists( 'ContentHandler') ) {
			$provider[] = array( array(), array() );
			return $provider;
		}

		$text     = 'Foo-3-' . __METHOD__;

		// #0 Title does not exists
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'        => 'Lila',
			'exists'          => false,
			'getText'         => null,
			'getPageLanguage' => $this->getLanguage()
		) );

		$provider[] = array(
			array(
				'title'    => $title,
				'revision' => null,
			),
			array(
				'error'    => true,
				'text'     => ''
			)
		);

		// #1 Valid revision
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'        => 'Lula',
			'exists'          => true,
			'getPageLanguage' => $this->getLanguage()
		) );

		$revision = $this->newMockBuilder()->newObject( 'Revision', array(
			'getId'      => 9001,
			'getUser'    => 'Lala',
			'getContent' => new TextContent( $text )
		) );

		$provider[] = array(
			array(
				'title'    => $title,
				'revision' => $revision,
			),
			array(
				'error'    => false,
				'text'     => $text
			)
		);

		// #1 Empty content
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'        => 'Lula',
			'exists'          => true,
			'getPageLanguage' => $this->getLanguage(),
			'getContentModel' => CONTENT_MODEL_WIKITEXT
		) );

		$revision = $this->newMockBuilder()->newObject( 'Revision', array(
			'getId'             => 9001,
			'getUser'           => 'Lala',
			'getContent'        => false,
			'getContentHandler' => new TextContentHandler()
		) );

		$provider[] = array(
			array(
				'title'    => $title,
				'revision' => $revision,
			),
			array(
				'error'    => false,
				'text'     => ''
			)
		);

		// #2 "real" revision and content
		$title    = $this->newTitle();
		$content  = ContentHandler::makeContent( $text, $title, CONTENT_MODEL_WIKITEXT, null );

		$revision = new Revision(
			array(
				'id'         => 42,
				'page'       => 23,
				'title'      => $title,

				'content'    => $content,
				'length'     => $content->getSize(),
				'comment'    => "testing",
				'minor_edit' => false,

				'content_format' => null,
			)
		);

		$provider[] = array(
			array(
				'title'    => $title,
				'revision' => $revision,
			),
			array(
				'error'    => false,
				'text'     => $text
			)
		);

		return $provider;
	}

}
