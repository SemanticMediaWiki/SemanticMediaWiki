<?php

namespace SMW;

use Html;
use Language;

/**
 * Class implementing message output formatting
 *
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * This class is implementing message output formatting to avoid having
 * classes to invoke a language object that is not a direct dependency (which
 * means that context relevant information is mostly missing from the invoking
 * class) therefore it is more appropriate to collect Message objects from the
 * source and initiate an output formatting only when necessary and requested.
 *
 * @ingroup Formatter
 */
class MessageFormatter {

	/** @var array */
	protected $messages = [];

	/** @var string */
	protected $type = 'warning';

	/** @var string */
	protected $separator = ' <!--br-->';

	/** @var boolean */
	protected $escape = true;

	/**
	 * @since 1.9
	 *
	 * @param Language $language
	 */
	public function __construct( Language $language ) {
		$this->language = $language;
	}

	/**
	 * Convenience factory method to invoke a message array together with
	 * a language object
	 *
	 * @par Example:
	 * @code
	 *  MessageFormatter::newFromArray( $language, array( 'Foo' ) )->getHtml();
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param Language $language
	 * @param array|null $messages
	 *
	 * @return MessageFormatter
	 */
	public static function newFromArray( Language $language, array $messages =  [] ) {
		$instance = new self( $language );
		return $instance->addFromArray( $messages );
	}

	/**
	 * Creates a Message object from a key and adds it to an internal array
	 *
	 * @since 1.9
	 *
	 * @param string $key message key
	 *
	 * @return MessageFormatter
	 */
	public function addFromKey( $key /*...*/ ) {
		$params = func_get_args();
		array_shift( $params );
		$this->addFromArray( [ new \Message( $key, $params ) ] );
		return $this;
	}

	/**
	 * Adds an arbitrary array of messages which can either contain text
	 * or/and Message objects
	 *
	 * @par Example:
	 * @code
	 *  $msgFormatter = new MessageFormatter( $language );
	 *  $msgFormatter->addFromArray( array( 'Foo', new Message( 'Bar' ) ) )->getHtml()
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param array $messages
	 *
	 * @return MessageFormatter
	 */
	public function addFromArray( array $messages ) {

		$messages = ProcessingErrorMsgHandler::normalizeAndDecodeMessages( $messages );

		foreach ( $messages as $message ) {
			if ( is_string( $message ) ) {
				$this->messages[md5( $message )] = $message;
			} else{
				$this->messages[] = $message;
			}
		}

		return $this;
	}

	/**
	 * Returns unformatted invoked messages
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getMessages() {
		return $this->messages;
	}

	/**
	 * Used in connection with the html output to invoke a specific display
	 * type
	 *
	 * @see Highlighter::getTypeId
	 *
	 * @since 1.9
	 *
	 * @return MessageFormatter
	 */
	public function setType( $type ) {
		$this->type = $type;
		return $this;
	}

	/**
	 * Enables/disables escaping for the output representation
	 *
	 * @note Escaping is generally enabled but in cases of special pages or
	 * with messages already being escaped this option can be circumvent by
	 * invoking escape( false )
	 *
	 * @since 1.9
	 *
	 * @param boolean $escape
	 *
	 * @return MessageFormatter
	 */
	public function escape( $escape ) {
		$this->escape = (bool)$escape;
		return $this;
	}

	/**
	 * Clears the internal message array
	 *
	 * @since 1.9
	 *
	 * @return MessageFormatter
	 */
	public function clear() {
		$this->messages = [];
		return $this;
	}

	/**
	 * Returns if the internal message array does contain messages
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function exists() {
		return $this->messages !== [];
	}

	/**
	 * Overrides invoked language object
	 *
	 * @since 1.9
	 *
	 * @param Language $language
	 *
	 * @return MessageFormatter
	 */
	public function setLanguage( Language $language ) {
		$this->language = $language;
		return $this;
	}

	/**
	 * Formatting and normalization of an array
	 *
	 * @note The array is being recursively resolved in order to ensure that
	 * the returning representation is a 1-n array where duplicate entries
	 * have been eliminated already while Message objects being transformed
	 * into a simple text representation using the invoked language
	 *
	 * @since 1.9
	 *
	 * @param array $messages
	 *
	 * @return array
	 */
	protected function doFormat( array $messages ) {
		$newArray = [];

		foreach ( $messages as $msg ) {

			if ( $msg instanceof \Message ) {
				$text = $msg->inLanguage( $this->language )->text();
				$newArray[md5( $text )] = $text;
			} elseif ( (array)$msg === $msg ) {
				foreach ( $this->doFormat( $msg ) as $m ) {
					$newArray[md5( $m )] = $m;
				}
			} elseif ( (string)$msg === $msg ) {
				$newArray[md5( $msg )] = $msg;
			}
		}

		return $newArray;
	}

	/**
	 * Converts the message array into a string representation
	 *
	 * @since 1.9
	 *
	 * @param boolean $escape
	 * @param boolean $html
	 *
	 * @return string
	 */
	protected function getString( $html = true ) {

		if ( $this->escape ) {
			$messages = array_map( 'htmlspecialchars', array_values( $this->doFormat( $this->messages ) ) );
		} else {
			$messages = array_values( $this->doFormat( $this->messages ) );
		}

		if ( count( $messages ) == 1 ) {
			$messageString = $messages[0];
		} else {
			foreach ( $messages as &$message ) {
				$message = $html ? Html::rawElement( 'li', [], $message ) : $message;
			}

			$messageString = implode( $this->separator, $messages );
			$messageString = $html ? Html::rawElement( 'ul', [], $messageString ) : $messageString;
		}

		return $messageString;
	}

	/**
	 * Returns html representation of the formatted messages
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getHtml() {

		if ( $this->exists() ) {

			$highlighter = Highlighter::factory( $this->type );
			$highlighter->setContent( [ 'content' => $this->getString( true ) ] );

			return $highlighter->getHtml();
		}

		return '';
	}

	/**
	 * Returns plain text representation of the formatted messages
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getPlain() {
		return $this->exists() ? $this->getString( false ) : '';
	}
}
