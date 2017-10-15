<?php

namespace SMW\Query\Parser;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author Markus Krötzsch
 */
class Tokenizer {

	/**
	 * @var string
	 */
	private $defaultPattern = '';

	/**
	 * @since 3.0
	 *
	 * @param array $prefixes
	 */
	public function setDefaultPattern( array $prefixes ) {

		$pattern = '';

		foreach ( $prefixes as $pref ) {
			$pattern .= '|^' . $pref;
		}

		$this->defaultPattern = '\[\[|\]\]|::|:=|<q>|<\/q>' . $pattern . '|\|\||\|';
	}

	/**
	 * Get the next unstructured string chunk from the query string.
	 * Chunks are delimited by any of the special strings used in inline queries
	 * (such as [[, ]], <q>, ...). If the string starts with such a delimiter,
	 * this delimiter is returned. Otherwise the first string in front of such a
	 * delimiter is returned.
	 * Trailing and initial spaces are ignored if $trim is true, and chunks
	 * consisting only of spaces are not returned.
	 * If there is no more qurey string left to process, the empty string is
	 * returned (and in no other case).
	 *
	 * The stoppattern can be used to customise the matching, especially in order to
	 * overread certain special symbols.
	 *
	 * $consume specifies whether the returned chunk should be removed from the
	 * query string.
	 *
	 * @param string $currentString
	 * @param string $stoppattern
	 * @param boolean $consume
	 * @param boolean $trim
	 *
	 * @return string
	 */
	public function getToken( &$currentString, $stoppattern = '', $consume = true, $trim = true ) {

		if ( $stoppattern === '' ) {
			$stoppattern = $this->defaultPattern;
		}

		$chunks = preg_split( '/[\s]*(' . $stoppattern . ')/iu', $currentString, 2, PREG_SPLIT_DELIM_CAPTURE );

		if ( count( $chunks ) == 1 ) { // no matches anymore, strip spaces and finish
			if ( $consume ) {
				$currentString = '';
			}

			return $trim ? trim( $chunks[0] ) : $chunks[0];
		} elseif ( count( $chunks ) == 3 ) { // this should generally happen if count is not 1
			if ( $chunks[0] === '' ) { // string started with delimiter
				if ( $consume ) {
					$currentString = $chunks[2];
				}

				return $trim ? trim( $chunks[1] ) : $chunks[1];
			} else {
				if ( $consume ) {
					$currentString = $chunks[1] . $chunks[2];
				}

				return $trim ? trim( $chunks[0] ) : $chunks[0];
			}
		}

		// should never happen
		return false;
	}

}
