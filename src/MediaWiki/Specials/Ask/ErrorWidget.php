<?php

namespace SMW\MediaWiki\Specials\Ask;

use Html;
use SMW\Message;
use SMW\ProcessingErrorMsgHandler;
use SMWQuery as Query;

/**
 * @license GPL-2.0-or-later
 * @since   2.5
 *
 * @author mwjames
 */
class ErrorWidget {

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public static function disabled() {
		return Html::errorBox(
			Message::get( 'smw_iq_disabled', Message::TEXT, Message::USER_LANGUAGE ),
			'',
			'smw-error-disabled'
		);
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public static function noResult() {
		return Html::noticeBox(
			Message::get( 'smw_result_noresults', Message::TEXT, Message::USER_LANGUAGE ),
			'smw-notice-result-noresults'
		);
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public static function noScript() {
		return Html::rawElement(
			'div',
			[
				'id'    => 'ask-status',
				'class' => 'smw-ask-status plainlinks'
			],
			Html::rawElement(
				'noscript',
				[],
				Html::errorBox(
					Message::get( 'smw-noscript', Message::PARSE, Message::USER_LANGUAGE ),
					'',
					'smw-error-noscript'
				)
			)
		);
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public static function sessionFailure() {
		return Html::errorBox(
			Message::get( 'sessionfailure', Message::TEXT, Message::USER_LANGUAGE ),
			'',
			'smw-error-session-failure'
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param Query|null $query
	 *
	 * @return string
	 */
	public static function queryError( ?Query $query = null ) {
		if ( $query === null || !is_array( $query->getErrors() ) || $query->getErrors() === [] ) {
			return '';
		}

		$errors = [];

		foreach ( ProcessingErrorMsgHandler::normalizeAndDecodeMessages( $query->getErrors() ) as $value ) {

			if ( $value === '' ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$value = implode( " ", $value );
			}

			$errors[] = $value;
		}

		if ( count( $errors ) > 1 ) {
			$error = '<ul><li>' . implode( '</li><li>', $errors ) . '</li></ul>';
		} else {
			$error = implode( ' ', $errors );
		}

		return Html::errorBox( $error, '', 'smw-error-result-error' );
	}

}
