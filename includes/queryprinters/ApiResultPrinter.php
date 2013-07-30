<?php

namespace SMW;
use SMWQueryResult, SMWOutputs;
use Html, FormatJson;

/**
 * Base class for result printers that use the Semantic MediaWiki Api
 *
 * @since 1.9
 *
 * @file
 *
 * @license GNU GPL v2 or later
 * @author mwjames
 */

/**
 * Abstract class for query printers using the Semantic MediaWiki Api
 *
 * @ingroup QueryPrinter
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
				$this->msg( 'livepreview-loading' )->inContentLanguage()->text()
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
		SMWOutputs::requireHeadItem(
			$id,
			$this->getSkin()->makeVariablesScript( array ( $id => FormatJson::encode( $data ) )
		) );
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
			if ( is_string( $value ) || is_integer( $value ) || is_bool( $value ) ) {
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
