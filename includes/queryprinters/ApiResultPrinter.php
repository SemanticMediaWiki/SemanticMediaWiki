<?php

namespace SMW;
use SMWQueryResult, SMWOutputs;
use Html, FormatJson;

/**
 * Base class for result printers that use the SMWAPI
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
 * @since 1.9
 *
 * @file
 * @ingroup QueryPrinter
 *
 * @licence GNU GPL v2 or later
 * @author mwjames
 */
abstract class ApiResultPrinter extends ResultPrinter {

	/**
	 * Returns html output.
	 *
	 * @since 1.9
	 */
	abstract protected function getHtml( array $data );

	/**
	 * Convenience method to register resources
	 *
	 * @since 1.9
	 *
	 * @param string $resource
	 */
	protected function addResources( $resource ) {
		SMWOutputs::requireResource( $resource );
	}

	/**
	 * Convenience method to create a unique id
	 *
	 * @since 1.9
	 */
	protected function getId( ) {
		return 'smw-' . uniqid();
	}

	/**
	 * Convenience method generating a visual placeholder before any
	 * JS is registered to indicate that resources (JavaScript, CSS)
	 * are being loaded and once ready ensure to set
	 * ( '.smw-spinner' ).hide()
	 *
	 * @since 1.9
	 */
	protected function loading() {
		$this->addResources( 'ext.smw.style' );

		return Html::rawElement(
			'div',
			array( 'class' => 'smw-spinner left mw-small-spinner' ),
			Html::element(
				'p',
				array( 'class' => 'text' ),
				$this->getContext()->msg( 'livepreview-loading' )->inContentLanguage()->text()
			)
		);
	}

	/**
	 * Convenience method to encode output data
	 *
	 * @since 1.9
	 *
	 * @param string $id
	 * @param array $data
	 */
	protected function encode( $id, $data ) {
		SMWOutputs::requireHeadItem( $id, $this->getContext()->getSkin()->makeVariablesScript( array ( $id => FormatJson::encode( $data ) ) ) );
	}

	/**
	 * Returns serialised content
	 *
	 * @see SMWResultPrinter::getResultText()
	 *
	 * @param SMWQueryResult $queryResult
	 * @param $outputMode
	 *
	 * @return string
	 */
	protected function getResultText( SMWQueryResult $queryResult, $outputMode ) {

		// Add parameters that are only known to the specific printer
		$ask = $queryResult->getQuery()->toArray();
		foreach ( $this->params as $key => $value ) {
			if ( is_string( $value ) ) {
				$ask['parameters'][$key] = $value;
			}
		}

		// Combine all data into one object
		$data = array(
			'query' => array(
				'result' => $queryResult->toArray(),
				'ask'    => $ask
			)
		);

		return $this->getHtml( $data );
	}
}
