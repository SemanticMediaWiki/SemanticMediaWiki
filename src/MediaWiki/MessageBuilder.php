<?php

namespace SMW\MediaWiki;

use IContextSource;
use Language;
use Message;
use RuntimeException;
use Title;

/**
 * Convenience class to build language dependant messages and special text
 * components and decrease depdencency on the Language object with SMW's code
 * base
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class MessageBuilder {

	/**
	 * @var Language
	 */
	private $language = null;

	/**
	 * @since 2.1
	 *
	 * @param Language|null $language
	 */
	public function __construct( Language $language = null ) {
		$this->language = $language;
	}

	/**
	 * @since 2.1
	 *
	 * @param Language $language
	 *
	 * @return MessageBuilder
	 */
	public function setLanguage( Language $language ) {
		$this->language = $language;
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param IContextSource $context
	 *
	 * @return MessageBuilder
	 */
	public function setLanguageFromContext( IContextSource $context ) {
		$this->language = $context->getLanguage();
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param mixed $number
	 * @param boolean $useForSpecialNumbers set to true for numbers like dates
	 *
	 * @return string
	 */
	public function formatNumberToText( $number, $useForSpecialNumbers = false ) {
		return $this->getLanguage()->formatNum( $number, $useForSpecialNumbers );
	}

	/**
	 * @since 2.1
	 *
	 * @param array $list
	 *
	 * @return string
	 */
	public function listToCommaSeparatedText( array $list ) {
		return $this->getLanguage()->listToText( $list );
	}

	/**
	 * @since 2.1
	 *
	 * @param Title $title,
	 * @param integer $offset,
	 * @param integer $offset,
	 * @param array $query,
	 * @param boolean|null $isAtTheEnd
	 *
	 * @return string
	 */
	public function prevNextToText( Title $title, $limit, $offset, array $query, $isAtTheEnd ) {
		return $this->getLanguage()->viewPrevNext( $title, $offset, $limit, $query, $isAtTheEnd );
	}

	/**
	 * @since 2.1
	 *
	 * @param string $key
	 *
	 * @return Message
	 */
	public function getMessage( $key ) {

		$params = func_get_args();
		array_shift( $params );

		if ( isset( $params[0] ) && is_array( $params[0] ) ) {
			$params = $params[0];
		}

		$message = new Message( $key, $params );

		return $message->inLanguage( $this->getLanguage() );
	}

	private function getLanguage() {

		if ( $this->language instanceof Language ) {
			return $this->language;
		}

		throw new RuntimeException( 'Expected a valid language object' );
	}

}
