<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\MagicWordFinder;

use ParserOutput;

/**
 * @covers \SMW\MediaWiki\MagicWordFinder
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   2.0
 *
 * @author mwjames
 */
class MagicWordFinderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MagicWordFinder',
			new MagicWordFinder()
		);

		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MagicWordFinder',
			new MagicWordFinder( $parserOutput )
		);
	}

	/**
	 * @dataProvider magicWordsProvider
	 */
	public function testMatchAndRemove( $magicWord, $text, $expectedText, $expectedWords ) {

		$instance = new MagicWordFinder();
		$words = $instance->matchAndRemove( $magicWord, $text );

		$this->assertInternalType( 'array', $words );
		$this->assertEquals( $expectedWords, $words );
		$this->assertEquals( $expectedText, $text );
	}

	public function testSetGetMagicWords() {

		$this->assertMagicWord(
			new MagicWordFinder( new ParserOutput() ),
			array( 'Foo' )
		);
	}

	public function testSetGetMagicWordsOnLegacyStorage() {

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\MagicWordFinder' )
			->disableOriginalConstructor()
			->setMethods( array( 'hasExtensionData' ) )
			->getMock();

		$instance->expects( $this->any() )
			->method( 'hasExtensionData' )
			->will( $this->returnValue( false ) );

		$this->assertMagicWord(
			$instance->setOutput( new ParserOutput() ),
			array( 'Foo' )
		);
	}

	protected function assertMagicWord( $instance, $magicWord ) {

		$this->assertEmpty( $instance->getMagicWords() );

		$instance->pushMagicWordsToParserOutput( $magicWord );

		$this->assertEquals(
			$magicWord,
			$instance->getMagicWords()
		);
	}

	/**
	 * @return array
	 */
	public function magicWordsProvider() {

		$provider = array();

		$provider[] = array(
			'SMW_NOFACTBOX',
			'Lorem ipsum dolor sit amet consectetuer auctor at quis',
			'Lorem ipsum dolor sit amet consectetuer auctor at quis',
			array()
		);

		$provider[] = array(
			'SMW_NOFACTBOX',
			'Lorem ipsum dolor sit __NOFACTBOX__ amet consectetuer auctor at quis',
			'Lorem ipsum dolor sit  amet consectetuer auctor at quis',
			array( 'SMW_NOFACTBOX' )
		);

		$provider[] = array(
			'SMW_SHOWFACTBOX',
			'Lorem ipsum dolor __NOFACTBOX__ sit amet consectetuer auctor at quis __SHOWFACTBOX__',
			'Lorem ipsum dolor __NOFACTBOX__ sit amet consectetuer auctor at quis ',
			array( 'SMW_SHOWFACTBOX' )
		);

		return $provider;
	}

}
