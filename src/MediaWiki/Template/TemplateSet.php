<?php

namespace SMW\MediaWiki\Template;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class TemplateSet {

	/**
	 * @var
	 */
	private $templates = [];

	/**
	 * @since 3.1
	 *
	 * @param array $templates
	 */
	public function __construct( array $templates = [] ) {
		$this->templates = $templates;
	}

	/**
	 * @since 3.1
	 *
	 * @param Template $template
	 */
	public function addTemplate( Template $template ) {
		$this->templates[] = $template;
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function text() {
		$text = '';

		foreach ( $this->templates as $template ) {

			if ( $template instanceof Template ) {
				$text .= $template->text();
			} else {
				$text .= $template;
			}
		}

		return $text;
	}

}
