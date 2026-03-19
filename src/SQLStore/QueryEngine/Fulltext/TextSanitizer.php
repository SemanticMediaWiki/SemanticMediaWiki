<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use Cdb\Reader;
use Exception;
use IntlRuleBasedBreakIterator;
use SMW\Utils\Normalizer;
use TextCat;
use Transliterator;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class TextSanitizer {

	/**
	 * @var array
	 */
	private $languageDetection = [];

	/**
	 * @var int
	 */
	private $minTokenSize = 3;

	/**
	 * @var Transliterator|null
	 */
	private $transliterator;

	/**
	 * @var array<string, Reader|null>
	 */
	private $stopwordReaders = [];

	/**
	 * @since 7.0.0
	 */
	public function __construct() {
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getVersions() {
		$languageDetector = '(Disabled)';

		if ( isset( $this->languageDetection['TextCatLanguageDetector'] ) ) {
			$languageDetector = 'TextCatLanguageDetector (' . implode( ', ', $this->languageDetection['TextCatLanguageDetector'] ) . ')';
		}

		return [
			'ICU (Intl) PHP-extension' => ( extension_loaded( 'intl' ) ? INTL_ICU_VERSION : '(Disabled)' ),
			'LanguageDetector' => $languageDetector
		];
	}

	/**
	 * @since 2.5
	 *
	 * @param array $languageDetection
	 */
	public function setLanguageDetection( array $languageDetection ) {
		$this->languageDetection = $languageDetection;
	}

	/**
	 * @since 2.5
	 *
	 * @param int $minTokenSize
	 */
	public function setMinTokenSize( $minTokenSize ) {
		$this->minTokenSize = $minTokenSize;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $text
	 * @param bool $isSearchTerm
	 *
	 * @return string
	 */
	public function sanitize( $text, $isSearchTerm = false ) {
		$text = rawurldecode( trim( $text ) );

		// Language detection must run on the original text before
		// transliteration, which would romanize non-Latin scripts
		// and confuse TextCat
		$language = $this->predictLanguage( $text );

		$exemptionList = '';

		// Those have special meaning when running a match search against
		// the fulltext index (wildcard, phrase matching markers etc.)
		if ( $isSearchTerm ) {
			$exemptionList = [ '*', '"', '+', '-', '&', ',', '@', '~' ];
		}

		$text = mb_strtolower( $text );

		$text = $this->transliterate( $text );

		$text = mb_convert_kana( $text, 'a' );

		$text = str_replace(
			[ 'http://', 'https://', 'mailto:', '%2A', '_', '&#x005B;', '&#91;', "\n", "\t" ],
			[ '', '', '', '*', ' ', '[', '[', '', '' ],
			$text
		);

		$tokens = $this->tokenize( $text, $language, $exemptionList );

		$filtered = $this->filterTokens( $tokens, $language, $exemptionList );

		$text = implode( ' ', $filtered );

		// Remove possible spaces added by the tokenizer
		$text = str_replace(
			[ ' *', '* ', ' "', '" ', '+ ', '- ', '@ ', '~ ', '*+', '*-', '*~' ],
			[ '*', '*', '"', '"', '+', '-', '@', '~', '* +', '* -', '* ~' ],
			$text
		);

		return $text;
	}

	/**
	 * @param string $text
	 *
	 * @return string
	 */
	private function transliterate( $text ) {
		if ( $this->transliterator === null ) {
			$this->transliterator = Transliterator::create( 'Any-Latin; Latin-ASCII' );
		}

		if ( $this->transliterator === null ) {
			return $text;
		}

		$result = $this->transliterator->transliterate( $text );

		return $result !== false ? $result : $text;
	}

	/**
	 * @param string $text
	 * @param string|null $language
	 * @param string|array $exemptionList
	 *
	 * @return array
	 */
	private function tokenize( $text, $language, $exemptionList ) {
		$hasCjk = (bool)preg_match( '/[\x{4e00}-\x{9fa5}]/u', $text );
		$hasIcu = class_exists( IntlRuleBasedBreakIterator::class );

		if ( $hasIcu ) {
			$isWordTokenizer = !$hasCjk;
			$tokens = $this->tokenizeWithIcu( $text, $language, $isWordTokenizer );
			$joined = implode( ' ', $tokens );

			return $this->tokenizeWithGenericRegex( $joined, $exemptionList );
		}

		if ( $hasCjk ) {
			$hasJapanese = (bool)preg_match( '/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $text );

			if ( $hasJapanese ) {
				$segmenter = new JaTinySegmenterTokenizer();
				$tokens = $segmenter->tokenize( $text );
			} else {
				$tokens = $this->tokenizeWithNgram( $text );
			}

			$joined = implode( ' ', $tokens );
			$tokens = $this->tokenizeWithCjkRegex( $joined, $exemptionList );
			$joined = implode( ' ', $tokens );

			return $this->tokenizeWithGenericRegex( $joined, $exemptionList );
		}

		return $this->tokenizeWithGenericRegex( $text, $exemptionList );
	}

	/**
	 * Port of IcuWordBoundaryTokenizer::createTokens()
	 *
	 * @param string $text
	 * @param string|null $language
	 * @param bool $useWordBoundary
	 *
	 * @return array
	 */
	private function tokenizeWithIcu( $text, $language, $useWordBoundary ): array {
		$tokens = [];

		$tokenizer = IntlRuleBasedBreakIterator::createWordInstance( $language ?? 'en' );

		if ( $tokenizer === null ) {
			return [ $text ];
		}

		$tokenizer->setText( $text );
		$prev = 0;

		foreach ( $tokenizer as $token ) {
			if ( $token == 0 ) {
				continue;
			}

			$res = substr( $text, $prev, $token - $prev );

			if ( $res !== '' && $res !== ' ' ) {
				$tokens[] = $res;
			}

			$prev = $token;
		}

		return $tokens;
	}

	/**
	 * Port of GenericRegExTokenizer::tokenize()
	 *
	 * @param string $text
	 * @param string|array $exemptionList
	 *
	 * @return array
	 */
	private function tokenizeWithGenericRegex( $text, $exemptionList ) {
		$pattern = str_replace(
			$exemptionList,
			'',
			'([\s\-_,:;?!%\'\|\/\(\)\[\]{}<>\r\n"]|(?<!\d)\.(?!\d)|(?<=\p{L})(?=\p{N}))'
		);

		$result = preg_split( '/' . $pattern . '/u', $text, -1, PREG_SPLIT_NO_EMPTY );

		if ( $result === false ) {
			$result = [];
		}

		return $result;
	}

	/**
	 * Port of CJKSimpleCharacterRegExTokenizer::tokenize()
	 *
	 * @param string $text
	 * @param string|array $exemptionList
	 *
	 * @return array
	 */
	private function tokenizeWithCjkRegex( $text, $exemptionList ) {
		$pattern = str_replace(
			$exemptionList,
			'',
			'([\s\、，,。／？《》〈〉；：""＂〃＇｀［］｛｝＼｜～！－＝＿＋）（()＊…—─％￥…◆★◇□■【】＃·啊吧把并被才从的得当对但到地而该过个给还和叫将就可来了啦里没你您哪那呢去却让使是时省随他我为现县向像象要由矣已以也又与于在之这则最乃\/\(\)\[\]{}<>\r\n"]|(?<!\d)\.(?!\d))'
		);

		$result = preg_split( '/' . $pattern . '/u', $text, -1, PREG_SPLIT_NO_EMPTY );

		if ( $result !== false ) {
			return $result;
		}

		return [];
	}

	/**
	 * Port of NGramTokenizer::createNGrams() without marker support
	 *
	 * @param string $text
	 * @param int $ngramSize
	 *
	 * @return array
	 */
	private function tokenizeWithNgram( $text, $ngramSize = 2 ): array {
		$ngramList = [];

		// Text is already lowercased by sanitize() before reaching here
		$textLength = mb_strlen( $text, 'UTF-8' );

		for ( $i = 0; $i < $textLength; ++$i ) {
			if ( $i + $ngramSize > $textLength ) {
				continue;
			}

			$ngramList[] = mb_substr( $text, $i, $ngramSize, 'UTF-8' );
		}

		return $ngramList;
	}

	/**
	 * @param array $tokens
	 * @param string|null $language
	 * @param string|array $exemptionList
	 *
	 * @return array
	 */
	private function filterTokens( $tokens, $language, $exemptionList ): array {
		if ( !$tokens || !is_array( $tokens ) ) {
			return [];
		}

		$whiteList = [];

		if ( is_array( $exemptionList ) && $exemptionList !== [] ) {
			$whiteList = array_fill_keys( $exemptionList, true );
		}

		// Determine if we should use word-based min length or character-based
		$hasCjk = false;

		foreach ( $tokens as $token ) {
			if ( preg_match( '/[\x{4e00}-\x{9fa5}]/u', $token ) ) {
				$hasCjk = true;
				break;
			}
		}

		$minLength = $hasCjk ? 1 : $this->minTokenSize;

		$stopwordReader = $this->openStopwordReader( $language );

		$index = [];
		$pos = 0;

		foreach ( $tokens as $word ) {
			// If it is not an exemption and less than the required minimum length
			// or identified as stop word it is removed
			if ( !isset( $whiteList[$word] ) && (
				mb_strlen( $word ) < $minLength ||
				( $stopwordReader !== null && $this->isStopWord( $stopwordReader, $word ) )
			) ) {
				continue;
			}

			// Simple proximity, check for same words appearing next to each other
			if ( isset( $index[$pos - 1] ) && $index[$pos - 1] === $word ) {
				continue;
			}

			$index[] = trim( $word );
			$pos++;
		}

		return $index;
	}

	/**
	 * @param string|null $language
	 *
	 * @return Reader|null
	 */
	private function openStopwordReader( $language ) {
		if ( $language === null ) {
			return null;
		}

		$key = strtolower( $language );

		if ( array_key_exists( $key, $this->stopwordReaders ) ) {
			return $this->stopwordReaders[$key];
		}

		$file = __DIR__ . '/data/stopwords/' . $key . '.cdb';

		try {
			$this->stopwordReaders[$key] = Reader::open( $file );
		} catch ( Exception $e ) {
			$this->stopwordReaders[$key] = null;
		}

		return $this->stopwordReaders[$key];
	}

	/**
	 * @param Reader $reader
	 * @param string $word
	 *
	 * @return bool
	 */
	private function isStopWord( $reader, $word ) {
		return $reader->get( $word ) !== false;
	}

	/**
	 * @param string $text
	 *
	 * @return string|null
	 */
	private function predictLanguage( $text ) {
		if ( $this->languageDetection === [] ) {
			return null;
		}

		if ( !isset( $this->languageDetection['TextCatLanguageDetector'] ) ) {
			return null;
		}

		$textCat = new TextCat();
		$candidates = $this->languageDetection['TextCatLanguageDetector'];

		$result = $textCat->classify(
			Normalizer::reduceLengthTo( $text, 200 ),
			$candidates
		);

		if ( is_array( $result ) && $result !== [] ) {
			return key( $result );
		}

		return null;
	}

}
