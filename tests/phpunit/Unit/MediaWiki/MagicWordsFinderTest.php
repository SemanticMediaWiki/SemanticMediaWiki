<?php

namespace SMW\Tests\MediaWiki;

use ParserOutput;
use SMW\MediaWiki\MagicWordsFinder;
use SMW\ApplicationFactory;

/**
 * @covers \SMW\MediaWiki\MagicWordsFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.0
 *
 * @author mwjames
 */
class MagicWordsFinderTest extends \PHPUnit_Framework_TestCase {

	private $magicWordsFinder;

	protected function setUp() {
		parent::setUp();

		$this->magicWordsFinder = ApplicationFactory::getInstance()->create( 'MagicWordsFinder' );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MagicWordsFinder',
			new MagicWordsFinder()
		);

		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MagicWordsFinder',
			new MagicWordsFinder( $parserOutput )
		);
	}

	/**
	 * @dataProvider magicWordsProvider
	 */
	public function testFindMagicWordInText( $magicWord, $text, $expectedText, $expectedWord ) {

		$instance = $this->magicWordsFinder;
		$word = $instance->findMagicWordInText( $magicWord, $text );

		$this->assertInternalType(
			'string',
			$word
		);

		$this->assertEquals(
			$expectedWord,
			$word
		);

		$this->assertEquals(
			$expectedText,
			$text
		);
	}

	public function testSetGetMagicWords() {

		$instance = new MagicWordsFinder(
			new ParserOutput()
		);

		$this->assertMagicWordFromParserOutput(
			$instance,
			[ 'Foo', '', 'Bar' ],
			[ 'Foo', 'Bar' ]
		);
	}

	public function testSetGetMagicWordsOnLegacyStorage() {

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\MagicWordsFinder' )
			->disableOriginalConstructor()
			->setMethods( [ 'hasExtensionData' ] )
			->getMock();

		$instance->expects( $this->any() )
			->method( 'hasExtensionData' )
			->will( $this->returnValue( false ) );

		$instance->setOutput( new ParserOutput() );

		$this->assertMagicWordFromParserOutput(
			$instance,
			[ 'Foo', '', 'Bar' ],
			[ 'Foo', 'Bar' ]
		);
	}

	public function testNoPushOnEmptyMagicWordsList() {

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->never() )
			->method( 'setExtensionData' );

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\MagicWordsFinder' )
			->disableOriginalConstructor()
			->setMethods( [ 'hasExtensionData' ] )
			->getMock();

		$instance->expects( $this->any() )
			->method( 'hasExtensionData' )
			->will( $this->returnValue( true ) );

		$instance->setOutput( $parserOutput );
		$instance->pushMagicWordsToParserOutput( [] );
	}

	protected function assertMagicWordFromParserOutput( $instance, $magicWord, $expectedMagicWords ) {

		$this->assertEmpty(
			$instance->getMagicWords()
		);

		$instance->pushMagicWordsToParserOutput( $magicWord );

		$this->assertEquals(
			$expectedMagicWords,
			$instance->getMagicWords()
		);
	}

	/**
	 * @return array
	 */
	public function magicWordsProvider() {

		$provider = [];

		$provider[] = [
			'SMW_NOFACTBOX',
			'Lorem ipsum dolor sit amet consectetuer auctor at quis',
			'Lorem ipsum dolor sit amet consectetuer auctor at quis',
			''
		];

		$provider[] = [
			'SMW_NOFACTBOX',
			'Lorem ipsum dolor sit __NOFACTBOX__ amet consectetuer auctor at quis',
			'Lorem ipsum dolor sit  amet consectetuer auctor at quis',
			'SMW_NOFACTBOX'
		];

		$provider[] = [
			'SMW_SHOWFACTBOX',
			'Lorem ipsum dolor __NOFACTBOX__ sit amet consectetuer auctor at quis __SHOWFACTBOX__',
			'Lorem ipsum dolor __NOFACTBOX__ sit amet consectetuer auctor at quis ',
			'SMW_SHOWFACTBOX'
		];

		return $provider;
	}

}
