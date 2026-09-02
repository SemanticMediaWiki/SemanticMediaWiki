<?php

namespace SMW\ParserFunctions;

use MediaWiki\Parser\Parser;
use SMW\MediaWiki\Outputs;
use SMW\Parser\LinksEncoder;
use SMW\Parser\PropertyLinkRenderer;
use SMW\ParserData;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * Provides the {{#property_link}} parser function to link to a property page
 * from running text (#5624), as the parser-function equivalent of the
 * `[[Foo::@@@]]` in-text annotation syntax (#1855).
 *
 * {{#property_link:Foo}} renders the same output as [[Foo::@@@]], and
 * {{#property_link:Foo|custom label}} the same as [[Foo::@@@|custom label]].
 *
 * @license GPL-2.0-or-later
 * @since 7.2.0
 */
class PropertyLinkParserFunction {

	public function __construct(
		private ParserData $parserData,
		private PropertyLinkRenderer $propertyLinkRenderer
	) {
	}

	public function parse( array $rawParams ): string {
		$parser = null;

		// Remove parser object from parameters array
		if ( isset( $rawParams[0] ) && $rawParams[0] instanceof Parser ) {
			$parser = array_shift( $rawParams );
		}

		$property = array_shift( $rawParams ) ?? '';
		$caption = array_shift( $rawParams ) ?? false;

		if ( $caption !== false ) {
			// A caption is display text and must not annotate
			$caption = LinksEncoder::neutralizeAnnotation( $caption );
		}

		$result = $this->propertyLinkRenderer->render( [ $property ], '@@@', $caption );

		// Mirrors InTextAnnotationParser::parse() where the `userlang`
		// parser-cache key is only applied for namespaces with semantic
		// links enabled
		if ( $this->parserData->variesByUserLanguage() && $this->isSemanticEnabledNamespace() ) {
			$this->parserData->addExtraParserKey( 'userlang' );
		}

		if ( $parser !== null ) {
			$this->commitRequestedResources( $parser );
		}

		return $result;
	}

	/**
	 * Resources requested during rendering (tooltip styles and scripts) must
	 * be committed here because the result bypasses the commit performed by
	 * InTextAnnotationParser::parse().
	 *
	 * Only a pass that renders HTML may receive the commit: any other pass
	 * discards its ParserOutput, so an eager commit there would both lose the
	 * modules and drain the buffers. In such a pass the requirements stay
	 * buffered for the rendering parse that follows, e.g. the preview parse
	 * of Special:ExpandTemplates rendering what the preprocess pass expanded
	 * under a content-page context title (#7109).
	 */
	private function commitRequestedResources( Parser $parser ): void {
		if ( $parser->getTitle()->isSpecialPage() ) {
			global $wgOut;
			Outputs::commitToOutputPage( $wgOut );
		} elseif ( $parser->getOutputType() === Parser::OT_HTML ) {
			Outputs::commitToParser( $parser );
		}
	}

	private function isSemanticEnabledNamespace(): bool {
		return ApplicationFactory::getInstance()->getNamespaceExaminer()->isSemanticEnabled(
			$this->parserData->getTitle()->getNamespace()
		);
	}

}
