<?php

namespace Onoi\Tesa\Tokenizer;

use RuntimeException;

/**
 * PHP Version of the TinySegmenter as a super compact Japanese tokenizer.
 * - https://github.com/setchi/codeute/blob/71c09c86cd1ce1cf9c8ca4d20b1db60b3784227a/fuel/app/classes/model/lib/tiny_segmenter.php
 *
 * TinySegmenter was originally developed by Taku Kudo <taku(at)chasen.org>.
 * Pulished under the BSD license http://chasen.org/~taku/software/TinySegmenter/LICENCE.txt
 *
 * PHP Version was developed by xnights <programming.magic(at)gmail.com>.
 * For details, see http://programming-magic.com/?id=172
 *
 * The model is based on the http://research.nii.ac.jp/src/list.html corpus
 * together with an optimized L1-norm regularization.
 *
 * - https://github.com/shogo82148/TinySegmenterMaker
 *
 * @since 0.1
 */
class JaTinySegmenterTokenizer implements Tokenizer {

	private $patterns_ = array(
		"[一二三四五六七八九十百千万億兆]"=>"M", // numbers (japanese)
		"[一-龠々〆ヵヶ]"=>"H", // kanji & misc characters
		"[ぁ-ん]"=>"I", // hiragana
		"[ァ-ヴーｱ-ﾝﾞｰ]"=>"K", // katakana
		"[a-zA-Zａ-ｚＡ-Ｚ]"=>"A", // ascii / romaji letters
		"[0-9０-９]"=>"N", // ascii / romaji numbers
	);

	/**
	 * @var Tokenizer
	 */
	private $tokenizer;

	/**
	 * This is kept static on purpose.
	 * @var array
	 */
	private static $model;

	/**
	 * @var string
	 */
	private $modelFile;

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

		return $this->loadModel()->segment( $string );
	}

	private function loadModel() {

		if ( self::$model !== null ) {
			return $this;
		}

		$contents = null;
		$file = __DIR__ . '/model/rwcp.model.json';

		if ( ( $contents = @file_get_contents( $file ) ) !== false ) {
			self::$model = json_decode( $contents, true );
		}

		if ( $contents === false || json_last_error() !== JSON_ERROR_NONE ) {
			throw new RuntimeException( "Couldn't read the model from {$file}." );
		}

		return $this;
	}

	protected function segment( $input, $encoding = null ) {

		if ( !$input ) {
			return array();
		}

		if ( !$encoding ) {
			$encoding = mb_detect_encoding( $input );
		}

		if ( $encoding !== 'UTF-8' ) {
			$input = mb_convert_encoding( $input, 'UTF-8', $encoding );
		}

		$result = array();
		$seg = array( "B3", "B2", "B1" );

		$ctype = array( "O", "O", "O" );
		$o = $this->mb_string_to_array_( $input );

		for ( $i = 0; $i<count($o); ++$i ) {
			$seg[] = $o[$i];
			$ctype[] = $this->ctype_( $o[$i] );
		}

		$seg[] = "E1";
		$seg[] = "E2";
		$seg[] = "E3";
		$ctype[] = "O";
		$ctype[] = "O";
		$ctype[] = "O";
		$word = $seg[3];
		$p1 = "U";
		$p2 = "U";
		$p3 = "U";

		for($i = 4; $i<count($seg)-3; ++$i){
			$score = self::$model["BIAS"];
			$w1 = $seg[$i-3];
			$w2 = $seg[$i-2];
			$w3 = $seg[$i-1];
			$w4 = $seg[$i];
			$w5 = $seg[$i+1];
			$w6 = $seg[$i+2];
			$c1 = $ctype[$i-3];
			$c2 = $ctype[$i-2];
			$c3 = $ctype[$i-1];
			$c4 = $ctype[$i];
			$c5 = $ctype[$i+1];
			$c6 = $ctype[$i+2];
			$score += $this->ts_(@self::$model["UP1"][$p1]);
			$score += $this->ts_(@self::$model["UP2"][$p2]);
			$score += $this->ts_(@self::$model["UP3"][$p3]);
			$score += $this->ts_(@self::$model["BP1"][$p1 . $p2]);
			$score += $this->ts_(@self::$model["BP2"][$p2 . $p3]);
			$score += $this->ts_(@self::$model["UW1"][$w1]);
			$score += $this->ts_(@self::$model["UW2"][$w2]);
			$score += $this->ts_(@self::$model["UW3"][$w3]);
			$score += $this->ts_(@self::$model["UW4"][$w4]);
			$score += $this->ts_(@self::$model["UW5"][$w5]);
			$score += $this->ts_(@self::$model["UW6"][$w6]);
			$score += $this->ts_(@self::$model["BW1"][$w2 . $w3]);
			$score += $this->ts_(@self::$model["BW2"][$w3 . $w4]);
			$score += $this->ts_(@self::$model["BW3"][$w4 . $w5]);
			$score += $this->ts_(@self::$model["TW1"][$w1 . $w2 . $w3]);
			$score += $this->ts_(@self::$model["TW2"][$w2 . $w3 . $w4]);
			$score += $this->ts_(@self::$model["TW3"][$w3 . $w4 . $w5]);
			$score += $this->ts_(@self::$model["TW4"][$w4 . $w5 . $w6]);
			$score += $this->ts_(@self::$model["UC1"][$c1]);
			$score += $this->ts_(@self::$model["UC2"][$c2]);
			$score += $this->ts_(@self::$model["UC3"][$c3]);
			$score += $this->ts_(@self::$model["UC4"][$c4]);
			$score += $this->ts_(@self::$model["UC5"][$c5]);
			$score += $this->ts_(@self::$model["UC6"][$c6]);
			$score += $this->ts_(@self::$model["BC1"][$c2 . $c3]);
			$score += $this->ts_(@self::$model["BC2"][$c3 . $c4]);
			$score += $this->ts_(@self::$model["BC3"][$c4 . $c5]);
			$score += $this->ts_(@self::$model["TC1"][$c1 . $c2 . $c3]);
			$score += $this->ts_(@self::$model["TC2"][$c2 . $c3 . $c4]);
			$score += $this->ts_(@self::$model["TC3"][$c3 . $c4 . $c5]);
			$score += $this->ts_(@self::$model["TC4"][$c4 . $c5 . $c6]);
			//  $score += $this->ts_(@self::$model["TC5"][$c4 . $c5 . $c6]);
			$score += $this->ts_(@self::$model["UQ1"][$p1 . $c1]);
			$score += $this->ts_(@self::$model["UQ2"][$p2 . $c2]);
			$score += $this->ts_(@self::$model["UQ1"][$p3 . $c3]);
			$score += $this->ts_(@self::$model["BQ1"][$p2 . $c2 . $c3]);
			$score += $this->ts_(@self::$model["BQ2"][$p2 . $c3 . $c4]);
			$score += $this->ts_(@self::$model["BQ3"][$p3 . $c2 . $c3]);
			$score += $this->ts_(@self::$model["BQ4"][$p3 . $c3 . $c4]);
			$score += $this->ts_(@self::$model["TQ1"][$p2 . $c1 . $c2 . $c3]);
			$score += $this->ts_(@self::$model["TQ2"][$p2 . $c2 . $c3 . $c4]);
			$score += $this->ts_(@self::$model["TQ3"][$p3 . $c1 . $c2 . $c3]);
			$score += $this->ts_(@self::$model["TQ4"][$p3 . $c2 . $c3 . $c4]);

			$p = "O";

			if ( $score > 0 ) {

				if ( $word !== '' && $word !== ' ' ) {
					$result[] = $word;
				}

				$word = "";
				$p = "B";
			}

			$p1 = $p2;
			$p2 = $p3;
			$p3 = $p;

			if ( $seg[$i] !== '' && $seg[$i] !== ' ' ) {
				$word .= $seg[$i];
			}
		}

		$result[] = $word;

		if ( $encoding !== 'UTF-8') {
			foreach( $result as &$str ) {
				$str = mb_convert_encoding( $str, $encoding, 'UTF-8' );
			}
		}

		return $result;
	}

	private function ctype_( $str ) {

		foreach( $this->patterns_ as $pattern => $type ) {
			if( preg_match( '/'.$pattern.'/u', $str ) ) {
				return $type;
			}
		}

		return "O";
	}

	private function ts_( $v ) {
		return $v ? $v : 0;
	}

	private function mb_string_to_array_( $str, $encoding = 'UTF-8' ) {

		$result = array();
		$length = mb_strlen( $str, $encoding );

		for ( $i=0; $i < $length; ++$i ) {
			$result[] = mb_substr( $str, $i, 1, $encoding );
		}

		return $result;
	}

}
