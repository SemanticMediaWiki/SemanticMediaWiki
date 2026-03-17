<?php

namespace SMW\MediaWiki\Renderer;

use MediaWiki\Parser\Parser;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class HtmlTemplateRenderer {

	/**
	 * @since 2.2
	 */
	public function __construct(
		private readonly WikitextTemplateRenderer $wikitextTemplateRenderer,
		private readonly Parser $parser,
	) {
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
