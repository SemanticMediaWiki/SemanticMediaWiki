<?php

namespace SMW\Tests\Factbox;

use SMW\Factbox\CheckMagicWords;

/**
 * @covers \SMW\Factbox\CheckMagicWords
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class CheckMagicWordsTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CheckMagicWords::class,
			new CheckMagicWords( [] )
		);
	}

	/**
	 * @dataProvider magicWordsProvider
	 */
	public function testGetMagicWords( $magicWords, $options, $expected ) {
		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->any() )
			->method( 'getExtensionData' )
			->willReturn( $magicWords );

		$instance = new CheckMagicWords(
			$options
		);

		$this->assertSame(
			$expected,
			$instance->getMagicWords( $parserOutput )
		);
	}

	public function magicWordsProvider() {
		yield [
			[ 'SMW_SHOWFACTBOX' ],
			[],
			SMW_FACTBOX_NONEMPTY
		];

		yield [
			[ 'SMW_NOFACTBOX' ],
			[],
			SMW_FACTBOX_HIDDEN
		];

		yield [
			null,
			[
				'showFactbox' => SMW_FACTBOX_NONEMPTY,
			],
			SMW_FACTBOX_NONEMPTY
		];

		yield [
			null,
			[
				'showFactbox' => SMW_FACTBOX_HIDDEN,
			],
			SMW_FACTBOX_HIDDEN
		];

		yield [
			null,
			[
				'preview' => true,
				'showFactboxEdit' => SMW_FACTBOX_HIDDEN,
			],
			SMW_FACTBOX_HIDDEN
		];

		yield [
			null,
			[
				'preview' => true,
				'showFactboxEdit' => SMW_FACTBOX_NONEMPTY,
			],
			SMW_FACTBOX_NONEMPTY
		];
	}

}
