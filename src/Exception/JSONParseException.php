<?php

namespace SMW\Exception;

use Seld\JsonLint\JsonParser;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class JSONParseException extends RuntimeException {

	/**
	 * @since 3.2
	 *
	 * @param string $json
	 */
	public function __construct( $json ) {
		$this->message = $this->buildMessage( $json );
	}

	/**
	 * @since 3.2
	 */
	public function getTidyMessage() : string {
		return str_replace( "\n", '', $this->getMessage() );
	}

	/**
	 * PHP has no built-in functionality to find errors in a JSON therefore we rely
	 * on `JsonLint` to help us find a more meaningful message other than
	 * "Syntax error".
	 *
	 * `JsonLint` notes "... like json_decode() does, but slower, throws exceptions
	 * on failure." now that we failed we can take the performance penalty here
	 * and allow users to make an informed decision about the state of the
	 * JSON.
	 */
	protected function getParseError( $json ) {

		$parser = new JsonParser();

		try {
			$parser->parse( $json );
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}

		return '';
	}

	private function buildMessage( $json ) {
		return $this->getParseError( $json );
	}

}
