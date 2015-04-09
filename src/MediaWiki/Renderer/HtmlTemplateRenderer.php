<?php

namespace SMW\MediaWiki\Renderer;

use Parser;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class HtmlTemplateRenderer {

	/**
	 * @var WikitextTemplateRenderer
	 */
	private $wikitextTemplateRenderer;

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @since 2.2
	 *
	 * @param WikitextTemplateRenderer $wikitextTemplateRenderer
	 * @param Parser $parser
	 */
	public function __construct( WikitextTemplateRenderer $wikitextTemplateRenderer, Parser $parser ) {
		$this->wikitextTemplateRenderer = $wikitextTemplateRenderer;
		$this->parser = $parser;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $field
	 * @param mixed $value
	 */
	public function addField( $field, $value ) {
		$this->wikitextTemplateRenderer->addField( $field, $value );
	}

	/**
	 * @since 2.2
	 *
	 * @param string $templateName
	 */
	public function packFieldsForTemplate( $templateName ) {
		$this->wikitextTemplateRenderer->packFieldsForTemplate( $templateName );
	}

	/**
	 * @since since 2.2
	 *
	 * @return string
	 */
	public function render() {

		$wikiText = $this->wikitextTemplateRenderer->render();

		if ( $wikiText === '' ) {
			return '';
		}

		return $this->parser->recursiveTagParse( $wikiText );
	}

}
