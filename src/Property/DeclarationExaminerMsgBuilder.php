<?php

namespace SMW\Property;

use Html;
use SMW\Message;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class DeclarationExaminerMsgBuilder {

	/**
	 * @since 3.1
	 *
	 * @param DeclarationExaminer $declarationExaminer
	 *
	 * @return string
	 */
	public function buildHTML( DeclarationExaminer $declarationExaminer ) {
		$messages = $declarationExaminer->getMessages();
		$html = '';

		if ( $messages === [] ) {
			return '';
		}

		$messageList = [
			'error' => [],
			'warning' => [],
			'info' => [],
			'plain' => []
		];

		foreach ( $messages as $message ) {
			$type = array_shift( $message );

			// Unknown type!
			if ( !isset( $messageList[$type] ) ) {
				continue;
			}

			$msg = $this->makeHTML( $type, $message );

			if ( $msg === '' || $msg === null ) {
				continue;
			}

			$messageList[$type][] = $msg;
		}

		// Display ranking!!
		foreach ( [ 'error', 'warning', 'info', 'plain' ] as $type ) {
			$html .= implode( '', $messageList[$type] );
		}

		return $html;
	}

	private function makeHTML( $type, $message ) {
		$class = '';

		if ( isset( $message['_merge'] ) ) {
			$msg = [];
			$msgKey = [];

			foreach ( $message['_merge'] as $m ) {
				$msgKey[] = $m[0];
				$msg[] = $this->msg( $m );
			}

			$msg = implode( '&nbsp;', $msg );
			$msgKey = implode( '-', $msgKey );
		} elseif ( isset( $message['_list' ] ) && isset( $message['_msgkey' ] ) ) {
			$msgKey = $message['_msgkey' ];
			$msg = $this->msg( $msgKey ) . '<ul><li>' . implode( '</li><li>', $message['_list' ] ) . '</li></ul>';
		} else {
			$msgKey = $message[0];
			$msg = $this->msg( $message );
		}

		if ( $msg === '' ) {
			return;
		}

		$class = "plainlinks $msgKey";
		switch ( $type ) {
			case 'plain':
				return Html::rawElement( 'div', [ 'class' => $class ], $msg );
			case 'error':
				return Html::errorBox( $msg, '', $class );
			case 'info':
				return Html::noticeBox( $msg, $class );
			case 'warning':
				return Html::warningBox( $msg, $class );
			default:
				return '';
		}
	}

	private function msg( $msg, $type = Message::PARSE, $lang = Message::USER_LANGUAGE ) {
		return Message::get( $msg, Message::PARSE, Message::USER_LANGUAGE );
	}

}
