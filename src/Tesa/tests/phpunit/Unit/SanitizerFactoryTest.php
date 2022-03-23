<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\SanitizerFactory;

/**
 * @covers \Onoi\Tesa\SanitizerFactory
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class SanitizerFactoryTest extends \PHPUnit_Framework_TestCase {

	private $sanitizerFactory;

	protected function setUp() {
		$this->sanitizerFactory = new SanitizerFactory();
	}

	public function testCanConstructSanitizer() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\Sanitizer',
			$this->sanitizerFactory->newSanitizer()
		);
	}

	/* StopwordAnalyzer */

	public function testCanConstructStopwordAnalyzerByNullLanguage() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\StopwordAnalyzer\StopwordAnalyzer',
			$this->sanitizerFactory->newStopwordAnalyzerByLanguage( null )
		);
	}

	public function testCanConstructStopwordAnalyzerByEnLanguage() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\StopwordAnalyzer\CdbStopwordAnalyzer',
			$this->sanitizerFactory->newStopwordAnalyzerByLanguage( 'EN' )
		);
	}

	public function testCanConstructCdbStopwordAnalyzer() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\StopwordAnalyzer\CdbStopwordAnalyzer',
			$this->sanitizerFactory->newCdbStopwordAnalyzer()
		);
	}

	public function testCanConstructArrayStopwordAnalyzer() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\StopwordAnalyzer\ArrayStopwordAnalyzer',
			$this->sanitizerFactory->newArrayStopwordAnalyzer()
		);
	}

	public function testCanConstructNullStopwordAnalyzer() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\StopwordAnalyzer\NullStopwordAnalyzer',
			$this->sanitizerFactory->newNullStopwordAnalyzer()
		);
	}

	/* Synonymizer */

	public function testCanConstructSynonymizerByLanguage() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\Synonymizer\Synonymizer',
			$this->sanitizerFactory->newSynonymizerByLanguage()
		);
	}

	public function testCanConstructNullSynonymizer() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\Synonymizer\NullSynonymizer',
			$this->sanitizerFactory->newNullSynonymizer()
		);
	}

	/* LanguageDetector */

	public function testCanConstructNullLanguageDetector() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\LanguageDetector\NullLanguageDetector',
			$this->sanitizerFactory->newNullLanguageDetector()
		);
	}

	public function testCanConstructTextCatLanguageDetector() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\LanguageDetector\TextCatLanguageDetector',
			$this->sanitizerFactory->newTextCatLanguageDetector()
		);
	}

	/* Tokenizer */

	public function testCanConstructPreferredTokenizerByLanguage() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\Tokenizer',
			$this->sanitizerFactory->newPreferredTokenizerByLanguage( 'テスト' )
		);

		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\Tokenizer',
			$this->sanitizerFactory->newPreferredTokenizerByLanguage( '在延安更名为新' )
		);
	}

	public function testCanConstructnewCJKMatchableTokenizer() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\Tokenizer',
			$this->sanitizerFactory->newCJKMatchableTokenizer( 'テスト' )
		);

		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\Tokenizer',
			$this->sanitizerFactory->newCJKMatchableTokenizer( '在延安更名为新' )
		);
	}

	public function testCanConstructIcuWordBoundaryTokenizer() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\IcuWordBoundaryTokenizer',
			$this->sanitizerFactory->newIcuWordBoundaryTokenizer()
		);
	}

	public function testCanConstructGenericRegExTokenizer() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\GenericRegExTokenizer',
			$this->sanitizerFactory->newGenericRegExTokenizer()
		);
	}

	public function testCanConstructPunctuationRegExTokenizer() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\PunctuationRegExTokenizer',
			$this->sanitizerFactory->newPunctuationRegExTokenizer()
		);
	}

	public function testCanConstructJaCompoundGroupTokenizer() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\JaCompoundGroupTokenizer',
			$this->sanitizerFactory->newJaCompoundGroupTokenizer()
		);
	}

	public function testCanConstructJaTinySegmenterTokenizer() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\JaTinySegmenterTokenizer',
			$this->sanitizerFactory->newJaTinySegmenterTokenizer()
		);
	}

	public function testCanConstructCJKSimpleCharacterRegExTokenizer() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\CJKSimpleCharacterRegExTokenizer',
			$this->sanitizerFactory->newCJKSimpleCharacterRegExTokenizer()
		);
	}

	public function testCanConstructNGramTokenizer() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\NGramTokenizer',
			$this->sanitizerFactory->newNGramTokenizer()
		);
	}

}
