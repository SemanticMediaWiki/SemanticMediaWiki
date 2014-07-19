<?php

namespace SMW;

use SMWQueryResult;
use SMWOutputs;
use Html;
use FormatJson;

/**
 * Base class for result printers that use the serialized results
 *
 * @since 1.9
 *
 * @license GNU GPL v2 or later
 * @author mwjames
 */
abstract class RawResultPrinter extends ResultPrinter {

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
	protected function createLoadingHtmlPlaceholder() {
		$this->addResources( 'ext.smw.style' );

		return Html::rawElement(
			'div',
			array( 'class' => 'smw-spinner left mw-small-spinner' ),
			Html::element(
				'p',
				array( 'class' => 'text' ),
				$this->msg( 'smw-livepreview-loading' )->inContentLanguage()->text()
			)
		);
	}

	/**
	 * @deprecated since 2.0
	 */
	protected function loading() {
		return $this->createLoadingHtmlPlaceholder();
	}

	/**
	 * Convenience method to encode output data
	 *
	 * @since 1.9
	 *
	 * @param string $id
	 * @param array $data
	 */
	protected function encodeToJsonForId( $id, $data ) {
		SMWOutputs::requireHeadItem(
			$id,
			$this->getSkin()->makeVariablesScript( array ( $id => FormatJson::encode( $data ) )
		) );

		return $this;
	}

	/**
	 * @deprecated since 2.0
	 */
	protected function encode( $id, $data ) {
		return $this->encodeToJsonForId( $id, $data );
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
