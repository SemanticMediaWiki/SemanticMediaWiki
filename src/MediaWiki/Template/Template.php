<?php

namespace SMW\MediaWiki\Template;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class Template {

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var []
	 */
	private $fields = [];

	/**
	 * @since 3.1
	 *
	 * @param string $name
	 */
	public function __construct( $name ) {
		$this->name = $name;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function field( $key, $value ) {
		$this->fields[$key] = $value;
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function text() {

		$text = '{{' . $this->name;

		foreach ( $this->fields as $key => $value ) {
			$text .= "|$key=$value";
		}

		$text .= '}}';

		return $text;
	}

}
