<?php

namespace SMW;

use SMWSemanticData;

use ContentHandler;
use Title;

/**
 * Process and builds a '_REDI' property where a redirect
 * object is availavle
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Process and builds a '_REDI' property where a redirect
 * object is availavle
 *
 * @ingroup Utility
 * @ingroup Builder
 */
class RedirectBuilder {

	/** @var SMWSemanticData */
	protected $semanticData = null;

	/** @var boolean */
	protected $canBuild = true;

	/**
	 * @since 1.9
	 *
	 * @param SMWSemanticData $semanticData
	 */
	public function __construct( SMWSemanticData $semanticData ) {
		$this->semanticData = $semanticData;
	}

	/**
	 * Indicates if the current object instance can
	 * build a redirect
	 *
	 * @since 1.9
	 *
	 * @param boolean $canBuild
	 *
	 * @return RedirectBuilder
	 */
	public function canBuild( $canBuild = true ) {
		$this->canBuild = $canBuild;
		return $this;
	}

	/**
	 * Builds a '_REDI' property and attaches it to the
	 * available semantic data container
	 *
	 * @par Example:
	 * @code
	 *  $redirect = new RedirectBuilder( $semanticData );
	 *  $redirect->canBuild( true )->build( $text );
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param vargs
	 *
	 * @return string
	 */
	public function build( /* ... */ ) {

		$argument = func_get_arg( 0 );

		if ( $this->canBuild && is_string( $argument ) ) {
			$title = $this->buildFromText( $argument );
		} else if ( $this->canBuild && $argument instanceof Title ) {
			$title = $argument;
		} else {
			$title = null;
		}

		if ( $title !== null ) {
			$this->semanticData->addPropertyObjectValue( new DIProperty( '_REDI' ), DIWikiPage::newFromTitle( $title, '__red' ) );
		}

	}

	/**
	 * Extract a redirect destination from a string and return the Title,
	 * or null if the text doesn't contain a valid redirect
	 *
	 * @note ContentHandler got introduced with Mw 1.21
	 *
	 * @since 1.9
	 *
	 * @param  string $text
	 *
	 * @return Title|null
	 */
	protected function buildFromText( $text ) {

		if ( class_exists( 'ContentHandler' ) ) {
			$title = ContentHandler::makeContent( $text, null, CONTENT_MODEL_WIKITEXT )->getRedirectTarget();
		} else {
			// @codeCoverageIgnoreStart
			$title = Title::newFromRedirect( $text );
			// @codeCoverageIgnoreEnd
		}

		return $title;
	}
}
