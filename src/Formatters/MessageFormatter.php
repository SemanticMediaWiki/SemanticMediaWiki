<?php

namespace SMW\Formatters;

use MediaWiki\Html\Html;
use MediaWiki\Language\Language;
use MediaWiki\Message\Message;
use SMW\ProcessingErrorMsgHandler;
use Wikimedia\Message\MessageSpecifier;

/**
 * Class implementing message output formatting.
 *
 * This class handles message output formatting to avoid requiring calling
 * classes to directly depend on a language object (which would otherwise lack
 * necessary context). Instead, it collects Message objects from the source and
 * performs formatting only when explicitly needed.
 *
 * @ingroup Formatter
 * @since 1.9
 * @license GPL-2.0-or-later
 * @author mwjames
 */
class MessageFormatter {

	protected array $messages = [];

	protected string $type = 'warning';

	protected string $separator = ' <!--br-->';

	protected bool $escape = true;

	/**
	 * @since 1.9
	 */
	public function __construct( private $language ) {
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
	 */
	public static function newFromArray( Language $language, array $messages = [] ): static {
		$instance = new self( $language );
		return $instance->addFromArray( $messages );
	}

	/**
	 * Creates a Message object from a key and adds it to an internal array
	 *
	 * @since 1.9
	 */
	public function addFromKey( string|array|MessageSpecifier $key ): static {
		$params = func_get_args();
		array_shift( $params );
		$this->addFromArray( [ new Message( $key, $params ) ] );
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
	 */
	public function addFromArray( array $messages ): static {
		$messages = ProcessingErrorMsgHandler::normalizeAndDecodeMessages( $messages );

		foreach ( $messages as $message ) {
			if ( is_string( $message ) ) {
				$this->messages[md5( $message )] = $message;
			} else {
				$this->messages[] = $message;
			}
		}

		return $this;
	}

	/**
	 * Returns unformatted invoked messages
	 *
	 * @since 1.9
	 */
	public function getMessages(): array {
		return $this->messages;
	}

	/**
	 * Used in connection with the html output to invoke a specific display
	 * type
	 *
	 * @see Highlighter::getTypeId
	 *
	 * @since 1.9
	 */
	public function setType( $type ): static {
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
	 */
	public function escape( bool $escape ): static {
		$this->escape = $escape;
		return $this;
	}

	/**
	 * Clears the internal message array
	 *
	 * @since 1.9
	 */
	public function clear(): static {
		$this->messages = [];
		return $this;
	}

	/**
	 * Returns if the internal message array does contain messages
	 *
	 * @since 1.9
	 */
	public function exists(): bool {
		return $this->messages !== [];
	}

	/**
	 * Overrides invoked language object
	 *
	 * @since 1.9
	 */
	public function setLanguage( Language $language ): static {
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
	 */
	protected function doFormat( array $messages ): array {
		$newArray = [];

		foreach ( $messages as $msg ) {

			if ( $msg instanceof Message ) {
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
	 */
	protected function getString( bool $html = true ): string {
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
	 */
	public function getHtml(): string {
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
	 */
	public function getPlain(): string {
		return $this->exists() ? $this->getString( false ) : '';
	}
}

/**
 * @deprecated since 7.0.0
 */
class_alias( MessageFormatter::class, 'SMW\MessageFormatter' );
