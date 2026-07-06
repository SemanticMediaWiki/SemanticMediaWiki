<?php

namespace SMW\MediaWiki\Template;

use MediaWiki\Context\RequestContext;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\StubObject\StubObject;
use RuntimeException;

/**
 * @license GPL-2.0-or-later
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
	 * @since 3.1
	 */
	public function __construct( private $parser ) {
	}

	/**
	 * @see Special:ExpandTemplates
	 * @since 3.1
	 *
	 * @param Template|TemplateSet|string $template
	 */
	public function expand( $template ): string|array {
		if ( !$this->parser instanceof Parser && !$this->parser instanceof StubObject ) {
			throw new RuntimeException( 'Missing a parser instance!' );
		}

		if ( $template instanceof Template || $template instanceof TemplateSet ) {
			$template = $template->text();
		}

		$options = $this->parser->getOptions();

		if ( !$options instanceof ParserOptions ) {
			$user = RequestContext::getMain()->getUser();
			$options = new ParserOptions( $user );
			$options->setRemoveComments( true );
			$options->setMaxIncludeSize( self::MAX_INCLUDE_SIZE );
		}

		$title = $this->parser->getTitle();

		$text = $this->parser->preprocess( $template, $title, $options );

		$text = str_replace(
			[ '_&lt;nowiki&gt;_', '_&lt;/nowiki&gt;_', '_&lt;nowiki */&gt;_', '<nowiki>', '</nowiki>' ],
			'',
			$text ?? ''
		);

		return $text;
	}

}
