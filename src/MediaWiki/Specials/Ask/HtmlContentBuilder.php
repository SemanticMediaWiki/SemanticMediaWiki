<?php

namespace SMW\MediaWiki\Specials\Ask;

use SMWQuery as Query;
use Html;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class HtmlContentBuilder {

	/**
	 * @since 2.5
	 *
	 * @param Query|null $query
	 *
	 * @return string
	 */
	public function getFormattedErrorString( Query $query = null ) {

		if ( $query === null || !is_array( $query->getErrors() ) || $query->getErrors() === array() ) {
			return '';
		}

		if ( count( $query->getErrors() ) == 1 ) {
			$error = implode( ' ', $query->getErrors() );
		} else {

			// Filter any duplicate messages
			$errors = array();
			foreach ( $query->getErrors() as $key => $value ) {

				if ( is_array( $value ) ) {
					$value = implode( ' ', $value );
				}

				$errors[md5( $value )] = $value;
			}

			$error = '<li>' . implode( '</li><li>', $errors ) . '</li>';
		}

		return Html::rawElement( 'div', array( 'class' => 'smw-callout smw-callout-error' ), $error );
	}

}
