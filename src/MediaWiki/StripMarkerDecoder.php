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
	 * @var bool
	 */
	private $isSupported = false;

	/**
	 * @since 3.0
	 */
	public function __construct( private readonly StripState $stripState ) {
	}

	/**
	 * @since 3.0
	 *
	 * @param bool $isSupported
	 */
	public function isSupported( $isSupported ): void {
		$this->isSupported = $isSupported;
	}

	/**
	 * @since 3.0
	 *
	 * @return bool
	 */
	public function canUse(): bool {
		return $this->isSupported;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $text
	 *
	 * @return bool
	 */
	public function hasStripMarker( $text ): int|false {
		return strpos( $text ?? '', Parser::MARKER_SUFFIX );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public function decode( $value ): string|array {
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
	public function unstrip( $text ): string|array {
		// Escape the text case to avoid any HTML elements
		// cause an issue during parsing
		return str_replace(
			[ '<', '>', ' ', '[', '{', '=', "'", ':', "\n" ],
			[ '&lt;', '&gt;', ' ', '&#x005B;', '&#x007B;', '&#x003D;', '&#x0027;', '&#58;', "<br />" ],
			$this->doUnstrip( $text ) ?? ''
		);
	}

	public function doUnstrip( $text ) {
		if ( ( $value = $this->stripState->unstripNoWiki( $text ) ) !== '' && !$this->hasStripMarker( $value ) ) {
			return $this->addNoWikiToUnstripValue( $value );
		}

		if ( ( $value = $this->stripState->unstripGeneral( $text ) ) !== '' && !$this->hasStripMarker( $value ) ) {
			return $value;
		}

		return $this->doUnstrip( $value );
	}

	private function addNoWikiToUnstripValue( string $text ): string {
		return '<nowiki>' . $text . '</nowiki>';
	}

}
