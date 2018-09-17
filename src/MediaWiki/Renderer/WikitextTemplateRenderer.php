<?php

namespace SMW\MediaWiki\Renderer;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class WikitextTemplateRenderer {

	/**
	 * @var array
	 */
	private $fields = [];

	/**
	 * @var string
	 */
	private $template = '';

	/**
	 * @since 2.2
	 *
	 * @param string $field
	 * @param mixed $value
	 */
	public function addField( $field, $value ) {
		$this->fields[$field] = $value;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $templateName
	 */
	public function packFieldsForTemplate( $templateName ) {

		$this->template .= '{{'. $templateName;

		foreach ( $this->fields as $key => $value ) {
			$this->template .= "\n|$key=$value";
		}

		$this->template .= '}}';
		$this->fields = [];
	}

	/**
	 * @since since 2.2
	 *
	 * @return string
	 */
	public function render() {
		$wikiText = $this->template;
		$this->template = '';
		$this->fields = [];
		return $wikiText;
	}

}
