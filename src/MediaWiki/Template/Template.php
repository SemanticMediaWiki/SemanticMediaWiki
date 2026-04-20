<?php

namespace SMW\MediaWiki\Template;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class Template {

	private array $fields = [];

	/**
	 * @since 3.1
	 */
	public function __construct( private $name ) {
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function field( $key, $value ): void {
		$this->fields[$key] = $value;
	}

	/**
	 * @since 3.1
	 */
	public function text(): string {
		$text = '{{' . $this->name;

		foreach ( $this->fields as $key => $value ) {
			$text .= "|$key=$value";
		}

		$text .= '}}';

		return $text;
	}

}
