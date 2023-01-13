<?php

namespace Onoi\Tesa\Tokenizer;

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class CJKSimpleCharacterRegExTokenizer implements Tokenizer {

	/**
	 * @var Tokenizer
	 */
	private $tokenizer;

	/**
	 * @var string
	 */
	private $patternExemption = '';

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

		if ( $name === self::REGEX_EXEMPTION ) {
			$this->patternExemption = $value;
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

		// Filter is based on https://github.com/kitech/cms-drupal/blob/master/modules/csplitter/filter.txt
		$pattern = str_replace(
			$this->patternExemption,
			'',
			'([\s\、，,。／？《》〈〉；：“”＂〃＇｀［］｛｝＼｜～！－＝＿＋）（()＊…—─％￥…◆★◇□■【】＃·啊吧把并被才从的得当对但到地而该过个给还和叫将就可来了啦里没你您哪那呢去却让使是时省随他我为现县向像象要由矣已以也又与于在之这则最乃\/\(\)\[\]{}<>\r\n"]|(?<!\d)\.(?!\d))'
		);

		$result = preg_split( '/' . $pattern . '/u', $string, null, PREG_SPLIT_NO_EMPTY );

		if ( $result !== false ) {
			return $result;
		}

		return array();
	}

}
