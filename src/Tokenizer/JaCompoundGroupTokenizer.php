<?php

namespace Onoi\Tesa\Tokenizer;

use Onoi\Tesa\CharacterExaminer;

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class JaCompoundGroupTokenizer implements Tokenizer {

	/**
	 * @var array
	 */
	private $compound = array(
		"あっ",
		"あり",
		"ある",
		"い",
		"いう",
		"いる",
		"う",
		"うち",
		"お",
		"および",
		"おり",
		"か",
		"かつて",
		"から",
		"が",
		"き",
		"ここ",
		"こと",
		"この",
		"これ",
		"これら",
		"さ",
		"さらに",
		"し",
		"しかし",
		"する",
		"ず",
		"せ",
		"せる",
		"そして",
		"その",
		"その他",
		"その後",
		"それ",
		"それぞれ",
		"た",
		"ただし",
		"たち",
		"ため",
		"たり",
		"だ",
		"だっ",
		"つ",
		"て",
		"で",
		"でき",
		"できる",
		"です",
		"では",
		"でも",
		"と",
		"という",
		"といった",
		"とき",
		"ところ",
		"として",
		"とともに",
		"とも",
		"と共に",
		"な",
		"ない",
		"なお",
		"なかっ",
		"ながら",
		"なく",
		"なっ",
		"など",
		"なら",
		"なり",
		"なる",
		"に",
		"において",
		"における",
		"について",
		"にて",
		"によって",
		"により",
		"による",
		"に対して",
		"に対する",
		"に関する",
		"の",
		"ので",
		"のみ",
		"は",
		"ば",
		"へ",
		"ほか",
		"ほとんど",
		"ほど",
		"ます",
		"また",
		"または",
		"まで",
		"も",
		"もの",
		"ものの",
		"や",
		"よう",
		"より",
		"ら",
		"られ",
		"られる",
		"れ",
		"れる",
		"を",
		"ん",
		"及び",
		"特に",
		"、",
		"。",
		"「",
		"」"
	);

	/**
	 * @var Tokenizer
	 */
	private $tokenizer;

	/**
	 * @since 0.1
	 *
	 * @param Tokenizer $tokenizer
	 */
	public function __construct( Tokenizer $tokenizer = null ) {
		$this->tokenizer = $tokenizer;
	}

	/**
	 * @since 0.1
	 *
	 * {@inheritDoc}
	 */
	public function setOption( $name, $value ) {
		if ( $this->tokenizer !== null ) {
			$this->tokenizer->setOption( $name, $value );
		}
	}

	/**
	 * @since 0.1
	 *
	 * {@inheritDoc}
	 */
	public function isWordTokenizer() {
		return false;
	}

	/**
	 * @since 0.1
	 *
	 * {@inheritDoc}
	 */
	public function tokenize( $string ) {

		if ( $this->tokenizer !== null ) {
			$string = implode( " ", $this->tokenizer->tokenize( $string ) );
		}

		$result = explode( " " , $this->splitByCharacterGroup(
			str_replace( $this->compound, ' ', $string ) )
		);

		foreach ( $result as $key => $value ) {
			if ( $value === '' ) {
				unset( $result[$key] );
			}

			// Single katakana/hiragana are exempted
			if ( mb_strlen( $value ) === 1 && CharacterExaminer::contains( CharacterExaminer::HIRAGANA_KATAKANA, $value ) ) {
				unset( $result[$key] );
			}
		}

		if ( $result !== false ) {
			return array_values( $result );
		}

		return array();
	}

	/**
	 * @see MediaWiki LanguageJa::segmentByWord
	 *
	 * @since 0.1
	 *
	 * {@inheritDoc}
	 */
	public function splitByCharacterGroup( $string ) {

		// Space strings of like hiragana/katakana/kanji
		$hiragana = '(?:\xe3(?:\x81[\x80-\xbf]|\x82[\x80-\x9f]))'; # U3040-309f
		$katakana = '(?:\xe3(?:\x82[\xa0-\xbf]|\x83[\x80-\xbf]))'; # U30a0-30ff
		$kanji = '(?:\xe3[\x88-\xbf][\x80-\xbf]'
			. '|[\xe4-\xe8][\x80-\xbf]{2}'
			. '|\xe9[\x80-\xa5][\x80-\xbf]'
			. '|\xe9\xa6[\x80-\x99])';
			# U3200-9999 = \xe3\x88\x80-\xe9\xa6\x99

		$reg = "/({$hiragana}+|{$katakana}+|{$kanji}+)/";

		return $this->insertSpace( $string, $reg );
	}

	private function insertSpace( $string, $pattern ) {
		return preg_replace( '/ +/', ' ', preg_replace( $pattern, " $1 ", $string ) );
	}

}
