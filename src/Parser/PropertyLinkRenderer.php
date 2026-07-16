<?php

namespace SMW\Parser;

use SMW\DataValueFactory;
use SMW\DataValues\PropertyValue;
use SMW\Formatters\Highlighter;
use SMW\Localizer\Localizer;
use SMW\ParserData;

/**
 * Renders a link to a property page, as produced by the `[[Foo::@@@]]`
 * in-text annotation syntax (#1855) and the {{#property_link}} parser
 * function (#5624).
 *
 * @license GPL-2.0-or-later
 * @since 7.2.0
 */
class PropertyLinkRenderer {

	public function __construct( private ParserData $parserData ) {
	}

	/**
	 * @param string[] $properties
	 * @param string $value the annotated value, `@@@` or `@@@<lang>`
	 * @param string|false $caption
	 */
	public function render( array $properties, string $value, string|false $caption ): string {
		$property = end( $properties );
		$linker = smwfGetLinker();
		$class = 'smw-property';

		// #4037
		// [[Foo::@@@|#] where `|#` indicates a noLink request
		if ( $caption === '#' ) {
			$linker = false;
			$caption = false;
			$class = 'smw-property nolink';
		}

		$dataValue = DataValueFactory::getInstance()->newPropertyValueByLabel(
			$property,
			$caption,
			$this->parserData->getSubject()
		);

		$dataValue->setLinkAttributes( [ 'class' => $class ] );

		$lang = Localizer::getAnnotatedLanguageCodeFrom( $value );
		if ( $lang !== false ) {
			$dataValue->setOption( $dataValue::OPT_USER_LANGUAGE, $lang );
			$dataValue->setCaption(
				$caption === false ? $dataValue->getWikiValue() : $caption
			);
		}

		if ( $dataValue instanceof PropertyValue ) {
			$dataValue->setOption( $dataValue::OPT_HIGHLIGHT_LINKER, true );
		}

		$result = $dataValue->getShortWikitext( $linker );

		// The property-link output is returned directly rather than going
		// through InTextAnnotationParser::addPropertyValue(), so the
		// user-language signal must be recorded here. A property link renders
		// a tooltip (title and, for predefined properties, a localized
		// description) in the viewer's interface language, unless an explicit
		// language was annotated (`@@@<lang>`), in which case the output is
		// content-stable. An invalid property renders no output.
		// Recording this lets the caller add the `userlang` parser-cache key
		// (see InTextAnnotationParser::parse() and PropertyLinkParserFunction).
		if ( !$dataValue->isValid() ||
			( $lang === false && Highlighter::hasHighlighterClass( $result ) )
		) {
			$this->parserData->markVariesByUserLanguage();
		}

		return $result;
	}

}
