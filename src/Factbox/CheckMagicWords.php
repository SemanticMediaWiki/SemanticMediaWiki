<?php

namespace SMW\Factbox;

use MediaWiki\Parser\ParserOutput;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class CheckMagicWords {

	/**
	 * @since 3.1
	 */
	public function __construct( private array $options ) {
	}

	/**
	 * Returns magic words attached to the ParserOutput object
	 *
	 * @since 3.1
	 *
	 * @param ParserOutput $parserOutput
	 *
	 * @return string|null
	 */
	public function getMagicWords( ParserOutput $parserOutput ) {
		$smwMagicWords = $parserOutput->getExtensionData( 'smwmagicwords' );
		$mws = [];

		if ( $smwMagicWords !== null ) {
			$mws = $smwMagicWords;
		}

		if ( in_array( 'SMW_SHOWFACTBOX', $mws ) ) {
			$showfactbox = SMW_FACTBOX_NONEMPTY;
		} elseif ( in_array( 'SMW_NOFACTBOX', $mws ) ) {
			$showfactbox = SMW_FACTBOX_HIDDEN;
		} elseif ( isset( $this->options['preview'] ) && $this->options['preview'] ) {
			$showfactbox = $this->options['showFactboxEdit'];
		} else {
			$showfactbox = $this->options['showFactbox'];
		}

		return $showfactbox;
	}

}
