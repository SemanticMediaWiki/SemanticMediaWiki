<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use SMW\SQLStore\QueryEngine\Fulltext\TextSanitizer;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\TextSanitizer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class TextSanitizerTest extends \PHPUnit\Framework\TestCase {

	private $sanitizerFactory;

	protected function setUp(): void {
		$this->sanitizerFactory = $this->getMockBuilder( '\Onoi\Tesa\SanitizerFactory' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\TextSanitizer',
			new TextSanitizer( $this->sanitizerFactory )
		);
	}

	/**
	 * @dataProvider textOnMockProvider
	 */
	public function testSanitizs( $text, $expected ) {
		$sanitizer = $this->getMockBuilder( '\Onoi\Tesa\Sanitizer' )
			->disableOriginalConstructor()
			->getMock();

		$sanitizer->expects( $this->atLeastOnce() )
			->method( 'sanitizeWith' )
			->willReturn( $text );

		$stopwordAnalyzer = $this->getMockBuilder( '\Onoi\Tesa\StopwordAnalyzer\StopwordAnalyzer' )
			->disableOriginalConstructor()
			->getMock();

		$synonymizer = $this->getMockBuilder( '\Onoi\Tesa\Synonymizer\Synonymizer' )
			->disableOriginalConstructor()
			->getMock();

		$tokenizer = $this->getMockBuilder( '\Onoi\Tesa\Tokenizer\Tokenizer' )
			->disableOriginalConstructor()
			->getMock();

		$this->sanitizerFactory->expects( $this->atLeastOnce() )
			->method( 'newSanitizer' )
			->willReturn( $sanitizer );

		$this->sanitizerFactory->expects( $this->atLeastOnce() )
			->method( 'newPreferredTokenizerByLanguage' )
			->willReturn( $tokenizer );

		$this->sanitizerFactory->expects( $this->atLeastOnce() )
			->method( 'newStopwordAnalyzerByLanguage' )
			->willReturn( $stopwordAnalyzer );

		$this->sanitizerFactory->expects( $this->atLeastOnce() )
			->method( 'newSynonymizerByLanguage' )
			->willReturn( $synonymizer );

		$instance = new TextSanitizer(
			$this->sanitizerFactory
		);

		$this->assertEquals(
			$expected,
			$instance->sanitize( $text )
		);
	}

	public function textOnMockProvider() {
		$provider[] = [
			'foo',
			'foo'
		];

		$provider[] = [
			'foo* - bar',
			'foo* -bar'
		];

		$provider[] = [
			'foo* + bar',
			'foo* +bar'
		];

		$provider[] = [
			'foo *',
			'foo*'
		];

		$provider[] = [
			'* foo *',
			'*foo*'
		];

		$provider[] = [
			'*foo* bar',
			'*foo*bar'
		];

		$provider[] = [
			'+foo*, *bar',
			'+foo*,*bar'
		];

		$provider[] = [
			'+foo* -bar',
			'+foo* -bar'
		];

		$provider[] = [
			'+foo* ~ bar',
			'+foo* ~bar'
		];

		return $provider;
	}

}
