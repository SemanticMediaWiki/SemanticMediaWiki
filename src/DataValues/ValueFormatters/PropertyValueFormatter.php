<?php

namespace SMW\DataValues\ValueFormatters;

use SMW\ApplicationFactory;
use SMW\Highlighter;
use SMW\Message;
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

		$wikiPageValue = $this->prepareWikiPageValue( $linker );

		if ( $wikiPageValue === null ) {
			return '';
		}

		if ( $type === self::WIKI_SHORT ) {
			$text = $this->doHighlightText( $wikiPageValue->getShortWikiText( $linker ) );
		}

		if ( $type === self::HTML_SHORT ) {
			$text = $this->doHighlightText( $wikiPageValue->getShortHTMLText( $linker ), $linker );
		}

		if ( $type === self::WIKI_LONG ) {
			$text = $wikiPageValue->getLongWikiText( $linker );
		}

		if ( $type === self::HTML_LONG ) {
			$text = $this->doHighlightText( $wikiPageValue->getLongHTMLText( $linker ), $linker );
		}

		return $text . $this->hintPreferredLabelUse();
	}

	private function getWikiValue() {

		if ( $this->dataValue->getPreferredLabel() !== '' ) {
			return $this->dataValue->getPreferredLabel();
		}

		if ( $this->dataValue->getWikiPageValue() !== null && $this->dataValue->getWikiPageValue()->getDisplayTitle() !== '' ) {
			return $this->dataValue->getWikiPageValue()->getDisplayTitle();
		}

		return $this->dataValue->getDataItem()->getLabel();
	}

	private function prepareWikiPageValue( $linker = null ) {

		$wikiPageValue = $this->dataValue->getWikiPageValue();

		if ( $wikiPageValue === null ) {
			return null;
		}

		$label = $this->dataValue->getDataItem()->getLabel();
		$preferredLabel = $this->dataValue->getPreferredLabel();
		$caption = $this->dataValue->getCaption();

		if ( $caption !== false && $caption !== '' ) {
			$wikiPageValue->setCaption( $caption );
		} elseif ( $preferredLabel !== '' ) {
			$wikiPageValue->setCaption( $preferredLabel );
		} else {
			$wikiPageValue->setCaption( $label );
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
			$this->dataValue->getOptionBy( PropertyValue::OPT_USER_LANGUAGE )
		);

		$highlighter->setContent( array (
			'userDefined' => $this->dataValue->getDataItem()->isUserDefined(),
			'caption' => $text,
			'content' => $content !== '' ? $content : Message::get( 'smw_isspecprop' )
		) );

		return $highlighter->getHtml();
	}

	private function canHighlight( &$propertyDescription, $linker ) {

		if ( $this->dataValue->getOptionBy( PropertyValue::OPT_NO_HIGHLIGHT ) === true ) {
			return false;
		}

		$dataItem = $this->dataValue->getDataItem();
		$propertySpecificationLookup = ApplicationFactory::getInstance()->getPropertySpecificationLookup();

		$propertyDescription = $propertySpecificationLookup->getPropertyDescriptionBy(
			$dataItem,
			$linker
		);

		return !$dataItem->isUserDefined() || $propertyDescription !== '';
	}

	private function hintPreferredLabelUse() {

		$preferredLabel = $this->dataValue->getPreferredLabel();
		$label = $this->dataValue->getDataItem()->getLabel();

		if ( !$this->dataValue->isEnabledFeature( SMW_DV_PROV_LHNT ) ||
			$preferredLabel === $this->dataValue->getDataItem()->getCanonicalLabel() ||
			$preferredLabel === '' ) {
			return '';
		}

		$preferredLabelMarker = '';

		if ( $preferredLabel !== $label ) {
			$preferredLabelMarker = '&nbsp;' . \Html::rawElement( 'span', array( 'title' => $label ), '<sup>ᵖ</sup>' );
		}

		return $preferredLabelMarker;
	}

}
