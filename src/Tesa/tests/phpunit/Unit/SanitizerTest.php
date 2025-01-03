<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\Sanitizer;
use Onoi\Tesa\SanitizerFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Onoi\Tesa\Sanitizer
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class SanitizerTest extends TestCase {

	private $sanitizerFactory;

	protected function setUp(): void {
		$this->sanitizerFactory = new SanitizerFactory();
	}

	public function testTransliteration() {
		$instance = new Sanitizer( 'ÀÁÂÃÄÅàáâãäåÒÓÔÕÕÖØòóôõöøÈÉÊËèéêëðÇçÐÌÍÎÏìíîïÙÚÛÜùúûüÑñŠšŸÿýŽž' );
		$instance->applyTransliteration();

		$this->assertEquals(
			'AAAAAEAaaaaaeaOOOOOOEOoooooeoEEEEeeeeðCcÐIIIIiiiiUUUUEuuuueNnSsYyyZz',
			$instance
		);
	}

	public function testToLowercase() {
		$instance = new Sanitizer( 'ÀÁÂÃÄÅ ABC 텍스트의 テスト часто הוא פשוט' );
		$instance->toLowercase();

		$this->assertEquals(
			'àáâãäå abc 텍스트의 テスト часто הוא פשוט',
			$instance
		);
	}

	public function testReduceLengthTo() {
		$instance = new Sanitizer( 'ABCDEF' );
		$instance->reduceLengthTo( 3 );

		$this->assertEquals(
			3,
			mb_strlen( $instance )
		);

		$instance->reduceLengthTo( 10 );

		$this->assertEquals(
			3,
			mb_strlen( $instance )
		);
	}

	public function testReduceLengthToNearestWholeWordForLatinString() {
		$instance = new Sanitizer( 'abc def gh in 123' );
		$instance->reduceLengthTo( 12 );

		$this->assertEquals(
			10,
			mb_strlen( $instance )
		);

		$this->assertEquals(
			'abc def gh',
			$instance
		);
	}

	public function testReduceLengthToNearestWholeWordForNonLatinString() {
		$instance = new Sanitizer( '一　二　三' );
		$instance->reduceLengthTo( 3 );

		$this->assertEquals(
			3,
			mb_strlen( $instance )
		);

		$this->assertEquals(
			'一　二',
			$instance
		);
	}

	public function testReplace() {
		$instance = new Sanitizer( 'テスト' );
		$instance->replace( array( 'テスト' ), array( 'Test' ) );

		$this->assertEquals(
			'Test',
			$instance
		);
	}

	public function testSanitizeWithSimpleStopwordList() {
		$text = 'Foo bar foobar';

		$tokenizer = $this->getMockBuilder( '\Onoi\Tesa\Tokenizer\Tokenizer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$tokenizer->expects( $this->once() )
			->method( 'tokenize' )
			->with( $text )
			->willReturn( array( 'Foo', 'bar', 'foobar' ) );

		$synonymizer = $this->getMockBuilder( '\Onoi\Tesa\Synonymizer\Synonymizer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$synonymizer->expects( $this->any() )
			->method( 'synonymize' )
			->willReturnArgument( 0 );

		$instance = new Sanitizer( $text );

		$stopwordAnalyzer = $this->sanitizerFactory->newArrayStopwordAnalyzer(
			array( 'bar' )
		);

		$this->assertEquals(
			'Foo foobar',
			$instance->sanitizeWith( $tokenizer, $stopwordAnalyzer, $synonymizer )
		);
	}

	public function testSanitizeByStopwordsToIncludeExemptionWithMinLengthRestriction() {
		$text = 'Foo bar foobar';

		$tokenizer = $this->getMockBuilder( '\Onoi\Tesa\Tokenizer\Tokenizer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$tokenizer->expects( $this->once() )
			->method( 'isWordTokenizer' )
			->willReturn( true );

		$tokenizer->expects( $this->once() )
			->method( 'tokenize' )
			->with( $text )
			->willReturn( array( 'Foo', 'bar', 'foobar' ) );

		$synonymizer = $this->getMockBuilder( '\Onoi\Tesa\Synonymizer\Synonymizer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$synonymizer->expects( $this->any() )
			->method( 'synonymize' )
			->willReturnArgument( 0 );

		$instance = new Sanitizer( $text );

		$stopwordAnalyzer = $this->sanitizerFactory->newArrayStopwordAnalyzer(
			array( 'bar' )
		);

		$instance->setOption( Sanitizer::MIN_LENGTH, 4 );
		$instance->setOption( Sanitizer::WHITELIST, array( 'bar' ) );

		$this->assertEquals(
			'bar foobar',
			$instance->sanitizeWith( $tokenizer, $stopwordAnalyzer, $synonymizer )
		);
	}

	public function testTrySanitizeByStopwordsWithProximityCheck() {
		$text = 'foo foo テスト テスト';

		$tokenizer = $this->getMockBuilder( '\Onoi\Tesa\Tokenizer\Tokenizer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$tokenizer->expects( $this->once() )
			->method( 'isWordTokenizer' )
			->willReturn( true );

		$tokenizer->expects( $this->once() )
			->method( 'tokenize' )
			->with( $text )
			->willReturn( array( 'foo', 'foo', 'テスト', 'テスト' ) );

		$synonymizer = $this->getMockBuilder( '\Onoi\Tesa\Synonymizer\Synonymizer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$synonymizer->expects( $this->any() )
			->method( 'synonymize' )
			->willReturnArgument( 0 );

		$instance = new Sanitizer( $text );

		$stopwordAnalyzer = $this->sanitizerFactory->newArrayStopwordAnalyzer();

		$this->assertEquals(
			'foo テスト',
			$instance->sanitizeWith( $tokenizer, $stopwordAnalyzer, $synonymizer )
		);
	}

}
