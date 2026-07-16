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
			$caption = $this->neutralizeAnnotations( $caption );
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
	 * A caption is display text and must not annotate. `[[Foo::@@@|caption]]`
	 * hands its caption to InTextAnnotationParser, which never rescans it, but
	 * this function returns wikitext before InTextAnnotationParser runs, so an
	 * unhandled caption would reach that scan: `[[Bar::Baz]]` would store an
	 * annotation of its own and `[[SMW::off]]` would discard the annotations of
	 * everything that follows it on the page.
	 *
	 * removeAnnotation() reduces an annotation to the text it displays, leaving
	 * ordinary links alone; obfuscateAnnotation() then encodes the brackets of
	 * whatever remains, since removeAnnotation() unwraps only the outermost
	 * annotation and would otherwise expose a nested one.
	 */
	private function neutralizeAnnotations( string $caption ): string {
		return (string)LinksEncoder::obfuscateAnnotation(
			LinksEncoder::removeAnnotation( $caption )
		);
	}

	/**
	 * Resources requested during rendering (tooltip styles and scripts) must
	 * be committed here because the result bypasses the commit performed by
	 * InTextAnnotationParser::parse()
	 */
	private function commitRequestedResources( Parser $parser ): void {
		if ( $parser->getTitle()->isSpecialPage() ) {
			global $wgOut;
			Outputs::commitToOutputPage( $wgOut );
		} else {
			Outputs::commitToParser( $parser );
		}
	}

	private function isSemanticEnabledNamespace(): bool {
		return ApplicationFactory::getInstance()->getNamespaceExaminer()->isSemanticEnabled(
			$this->parserData->getTitle()->getNamespace()
		);
	}

}
