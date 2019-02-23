<?php

namespace SMW\Property;

use SMW\Message;
use Html;

/**
 * @license GNU GPL v2+
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

		if ( isset( $message['_merge' ] ) ) {
			$msg = [];
			$msgKey = [];

			foreach ( $message['_merge' ] as $m ) {
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

		if ( $type !== 'plain' ) {
			$class = "smw-callout smw-callout-$type";
		}

		return Html::rawElement(
			'div',
			[
				'id' => "$msgKey",
				'class' => "plainlinks $msgKey $class"
			],
			$msg
		);
	}

	private function msg( $msg, $type = Message::PARSE, $lang = Message::USER_LANGUAGE ) {
		return  Message::get( $msg, Message::PARSE, Message::USER_LANGUAGE );
	}

}
