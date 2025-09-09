<?php

namespace SMW\MediaWiki;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\StripState;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class StripMarkerDecoder {

	/**
	 * @var StripState
	 */
	private $stripState;

	/**
	 * @var bool
	 */
	private $isSupported = false;

	/**
	 * @since 3.0
	 *
	 * @param StripState $stripState
	 */
	public function __construct( StripState $stripState ) {
		$this->stripState = $stripState;
	}

	/**
	 * @since 3.0
	 *
	 * @param bool $isSupported
	 */
	public function isSupported( $isSupported ) {
		$this->isSupported = $isSupported;
	}

	/**
	 * @since 3.0
	 *
	 * @return bool
	 */
	public function canUse() {
		return $this->isSupported;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $text
	 *
	 * @return bool
	 */
	public function hasStripMarker( $text ) {
		return strpos( $text ?? '', Parser::MARKER_SUFFIX );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public function decode( $value ) {
		$hasStripMarker = false;

		if ( $this->canUse() ) {
			$hasStripMarker = $this->hasStripMarker( $value );
		}

		if ( $hasStripMarker ) {
			$value = $this->unstrip( $value );
		}

		return $value;
	}

	/**
	 * @since 3.0
	 *
	 * @return text
	 */
	public function unstrip( $text ) {
		while ( $this->hasStripMarker( $text ) ) {
			$from = strpos( $text, Parser::MARKER_PREFIX );
			$to = strpos( $text, Parser::MARKER_SUFFIX, $from ) + strlen( Parser::MARKER_SUFFIX );
			$toStrip = substr( $text, $from, $to - $from );
			if ( str_starts_with( $toStrip, Parser::MARKER_PREFIX . '-nowiki-' ) ) {
				$toStrip = '<nowiki>' . $this->stripState->unstripNoWiki( $toStrip ) . '</nowiki>';
			} else {
				$toStrip = $this->stripState->unstripGeneral( $toStrip );
			}
			$text = substr( $text, 0, $from ) . $toStrip . substr( $text, $to );
		}
		return $text;
	}

	private function addNoWikiToUnstripValue( $text ) {
		return '<nowiki>' . $text . '</nowiki>';
	}

}
