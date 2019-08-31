<?php

namespace SMW\MediaWiki\Template;

use Parser;
use ParserOptions;
use Title;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TemplateExpander {

	/**
	 * @see Special:ExpandTemplates
	 * @var int Maximum size in bytes to include. 50MB allows fixing those huge pages
	 */
	const MAX_INCLUDE_SIZE = 50000000;

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @var Title
	 */
	private $title;

	/**
	 * @since 3.1
	 *
	 * @param Parser $parser
	 */
	public function __construct( $parser ) {
		$this->parser = $parser;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 */
	public function setTitle( Title $title ) {
		$this->title = $title;
	}

	/**
	 * @see Special:ExpandTemplates
	 * @since 3.1
	 *
	 * @param Template|TemplateSet|srting $template
	 *
	 * @return string
	 */
	public function expand( $template ) {

		if ( !$this->parser instanceof Parser && !$this->parser instanceof \StubObject ) {
			throw new RuntimeException( 'Missing a parser instance!' );
		}

		if ( $template instanceof Template || $template instanceof TemplateSet ) {
			$template = $template->text();
		}

		$options = $this->parser->getOptions();

		if ( !$options instanceof ParserOptions ) {
			$options = new ParserOptions();
			$options->setRemoveComments( true );
			$options->setTidy( true );
			$options->setMaxIncludeSize( self::MAX_INCLUDE_SIZE );
		}

		$title = $this->parser->getTitle();

		if ( !$title instanceof Title ) {

			if ( $this->title !== null ) {
				$title = $this->title;
			} else {
				$title = $GLOBALS['wgTitle'];
			}

			if ( !$title instanceof Title ) {
				$title = Title::newFromText( 'UNKNOWN_TITLE' );
			}
		}

		$text = $this->parser->preprocess( $template, $title, $options );

		$text = str_replace(
			[ '_&lt;nowiki&gt;_', '_&lt;/nowiki&gt;_', '_&lt;nowiki */&gt;_', '<nowiki>', '</nowiki>' ],
			'',
			$text
		);

		return $text;
	}

}
