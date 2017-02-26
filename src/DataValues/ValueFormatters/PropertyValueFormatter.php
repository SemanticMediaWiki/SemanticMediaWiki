<?php

namespace SMW\DataValues\ValueFormatters;

use SMW\ApplicationFactory;
use SMW\Highlighter;
use SMW\Localizer;
use SMW\Message;
use SMW\DIWikiPage;
use SMWDataValue as DataValue;
use SMWPropertyValue as PropertyValue;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyValueFormatter extends DataValueFormatter {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isFormatterFor( DataValue $dataValue ) {
		return $dataValue instanceof PropertyValue;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function format( $type, $linker = null ) {

		if ( !$this->dataValue instanceof PropertyValue ) {
			throw new RuntimeException( "The formatter is missing a valid PropertyValue object" );
		}

		if ( !$this->dataValue->isVisible() ) {
			return '';
		}

		if ( $type === self::VALUE ) {
			return $this->getWikiValue();
		}

		if ( $type === PropertyValue::FORMAT_LABEL ) {
			return $this->getFormattedLabel( $linker );
		}

		if ( $type === PropertyValue::SEARCH_LABEL ) {
			return $this->getSearchLabel();
		}

		$wikiPageValue = $this->prepareWikiPageValue( $linker );
		$text = '';

		if ( $wikiPageValue === null ) {
			return '';
		}

		if ( $type === self::WIKI_SHORT ) {
			$text = $this->doHighlightText(
				$wikiPageValue->getShortWikiText( $linker ),
				$this->dataValue->getOption( PropertyValue::OPT_HIGHLIGHT_LINKER ) ? $linker : null
			);
		}

		if ( $type === self::HTML_SHORT ) {
			$text = $this->doHighlightText( $wikiPageValue->getShortHTMLText( $linker ), $linker );
		}

		if ( $type === self::WIKI_LONG ) {
			$text =  $this->doHighlightText( $wikiPageValue->getLongWikiText( $linker ) );
		}

		if ( $type === self::HTML_LONG ) {
			$text = $this->doHighlightText( $wikiPageValue->getLongHTMLText( $linker ), $linker );
		}

		return $text . $this->hintPreferredLabelUse();
	}

	/**
	 * Formatting rule set:
	 * - preferred goes before translation
	 * - displayTitle goes before translation
	 * - translation goes before "normal" label
	 */
	private function getFormattedLabel( $linker = null ) {

		$property = $this->dataValue->getDataItem();
		$output = '';
		$displayTitle = '';

		$preferredLabel = $property->getPreferredLabel(
			$this->dataValue->getOption( PropertyValue::OPT_USER_LANGUAGE )
		);

		$label = $preferredLabel;

		if ( $preferredLabel === '' && ( $label = $this->findTranslatedPropertyLabel( $property ) ) === '' ) {
			$label = $property->getLabel();
		}

		if ( $this->dataValue->getWikiPageValue() !== null ) {
			$displayTitle = $this->dataValue->getWikiPageValue()->getDisplayTitle();
		}

		$canonicalLabel = $property->getCanonicalLabel();

		// Display title goes before a translated label (but not preferred)
		if ( $preferredLabel === '' && $displayTitle !== '' ) {
			$label = $displayTitle;
		//	$canonicalLabel = $displayTitle;
		}

		// Internal format only used by PropertyValue
		$format = $this->getOption( PropertyValue::FORMAT_LABEL );
		$this->dataValue->setCaption( $label );

		if ( $format === self::VALUE ) {
			$output = $this->dataValue->getWikiValue();
		}

		if ( $format === self::WIKI_LONG && $linker !== null ) {
			$output = $this->dataValue->getLongWikiText( $linker );
		} elseif ( $format === self::WIKI_LONG && $preferredLabel === '' && $displayTitle !== '' ) {
			$output = $displayTitle;
		} elseif ( $format === self::WIKI_LONG ) {
			// Avoid Title::getPrefixedText as it transforms the text to have a
			// leading capital letter in some configurations
			$output = Localizer::getInstance()->createTextWithNamespacePrefix( SMW_NS_PROPERTY, $label );
		}

		if ( $format === self::HTML_SHORT && $linker !== null ) {
			$output = $this->dataValue->getShortHTMLText( $linker );
		}

		// Output both according to the formatting rule set forth by
		if ( $canonicalLabel !== $label ) {
			$canonicalLabel = \Html::rawElement(
				'span', array( 'style' => 'font-size:small;' ), '(' . $canonicalLabel . ')' );
			$output = $output . '&nbsp;'.  $canonicalLabel;
		}

		return $output;
	}

	private function getWikiValue() {

		$property = $this->dataValue->getDataItem();
		$languageCode = $this->dataValue->getOption( PropertyValue::OPT_USER_LANGUAGE );

		if ( ( $preferredLabel = $property->getPreferredLabel( $languageCode ) ) !== '' ) {
			return $preferredLabel;
		}

		if ( $this->dataValue->getWikiPageValue() !== null && $this->dataValue->getWikiPageValue()->getDisplayTitle() !== '' ) {
			return $this->dataValue->getWikiPageValue()->getDisplayTitle();
		}

		if ( ( $translatedPropertyLabel = $this->findTranslatedPropertyLabel( $property ) ) !== '' ) {
			return $translatedPropertyLabel;
		}

		return $this->dataValue->getDataItem()->getLabel();
	}

	/**
	 * The display title modifies the search/sort characteristics (#1534),
	 * (foo:Bar vs. Foo:Bar vs. FOO:bar) therefore select a possible DisplayTitle
	 * before any other label preference.
	 */
	private function getSearchLabel() {

		$wikiPageValue = $this->dataValue->getWikiPageValue();

		if ( $wikiPageValue !== null && ( $displayTitle = $wikiPageValue->getDisplayTitle() ) !== '' ) {
			return $displayTitle;
		}

		return $this->dataValue->getDataItem()->getLabel();
	}

	private function prepareWikiPageValue( $linker = null ) {

		$wikiPageValue = $this->dataValue->getWikiPageValue();

		if ( $wikiPageValue === null ) {
			return null;
		}

		$property = $this->dataValue->getDataItem();
		$caption = $this->dataValue->getCaption();

		if ( $caption !== false && $caption !== '' ) {
			$wikiPageValue->setCaption( $caption );
		} elseif ( ( $preferredLabel = $this->dataValue->getPreferredLabel() ) !== '' ) {
			$wikiPageValue->setCaption( $preferredLabel );
		} elseif ( ( $translatedPropertyLabel = $this->findTranslatedPropertyLabel( $property ) ) !== '' ) {
			$wikiPageValue->setCaption( $translatedPropertyLabel );
		} else {
			$wikiPageValue->setCaption( $property->getLabel() );
		}

		return $wikiPageValue;
	}

	private function doHighlightText( $text, $linker = null ) {

		$content = '';

		if ( !$this->canHighlight( $content, $linker ) ) {
			return $text;
		}

		$highlighter = Highlighter::factory(
			Highlighter::TYPE_PROPERTY,
			$this->dataValue->getOption( PropertyValue::OPT_USER_LANGUAGE )
		);

		$highlighter->setContent( array (
			'userDefined' => $this->dataValue->getDataItem()->isUserDefined(),
			'caption' => $text,
			'content' => $content !== '' ? $content : Message::get( 'smw_isspecprop' )
		) );

		return $highlighter->getHtml();
	}

	private function canHighlight( &$propertyDescription, $linker ) {

		if ( $this->dataValue->getOption( PropertyValue::OPT_NO_HIGHLIGHT ) === true ) {
			return false;
		}

		$dataItem = $this->dataValue->getDataItem();

		$propertyDescription = ApplicationFactory::getInstance()->getPropertySpecificationLookup()->getPropertyDescriptionBy(
			$dataItem,
			$this->dataValue->getOption( PropertyValue::OPT_USER_LANGUAGE ),
			$linker
		);

		return !$dataItem->isUserDefined() || $propertyDescription !== '';
	}

	private function hintPreferredLabelUse() {

		if ( !$this->dataValue->isEnabledFeature( SMW_DV_PROV_LHNT ) ||
			$this->dataValue->getOption( PropertyValue::OPT_NO_PREF_LHNT ) ) {
			return '';
		}

		$property = $this->dataValue->getDataItem();

		$preferredLabel = $property->getPreferredLabel(
			$this->dataValue->getOption( PropertyValue::OPT_USER_LANGUAGE )
		);

		// When comparing with a caption set from the "outside", normalize
		// the string to avoid a false negative in case of a non-breaking space
		$caption = str_replace(
			array( "&#160;", "&nbsp;", html_entity_decode( '&#160;', ENT_NOQUOTES, 'UTF-8' ) ),
			" ",
			$this->dataValue->getCaption()
		);

		if ( $preferredLabel === '' || $caption !== $preferredLabel ) {
			return '';
		}

		$label = $property->getLabel();
		$preferredLabelMarker = '';

		if ( $preferredLabel !== $label ) {
			$preferredLabelMarker = '&nbsp;' . \Html::rawElement( 'span', array( 'title' => $property->getCanonicalLabel() ), '<sup>ᵖ</sup>' );
		}

		return $preferredLabelMarker;
	}

	private function findTranslatedPropertyLabel( $property ) {

		// User-defined properties don't have any translatable label (this is
		// what the preferred label is for)
		if ( $property->isUserDefined() ) {
			return '';
		}

		return ApplicationFactory::getInstance()->getPropertyLabelFinder()->findPropertyLabelByLanguageCode(
			$property->getKey(),
			$this->dataValue->getOption( PropertyValue::OPT_USER_LANGUAGE )
		);
	}

}
