<?php

namespace SMW\MediaWiki\Specials\Browse;

use SMW\Message;
use SMW\ApplicationFactory;
use SMWDataValue as DataValue;
use SMWDataItem as DataItem;
use SMWPropertyValue as PropertyValue;
use SMW\DataValueFactory;
use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMWInfolink as Infolink;
use SMW\DIProperty;
use SMW\Localizer;

/**
 * @private
 *
 * This class should eventually be injected instead of relying on static methods,
 * for now this is the easiest way to unclutter the mammoth Browse class and
 * splitting up responsibilities.
 *
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class ValueFormatter {

	/**
	 * @since 2.5
	 *
	 * @param DataValue $value
	 *
	 * @return string
	 */
	public static function getFormattedSubject( DataValue $dataValue ) {

		$extra = '';

		if ( $dataValue->getDataItem()->getNamespace() === SMW_NS_PROPERTY ) {

			$dv = DataValueFactory::getInstance()->newDataValueByItem(
				DIProperty::newFromUserLabel( $dataValue->getDataItem()->getDBKey() )
			);

			$label = $dv->getFormattedLabel( DataValueFormatter::WIKI_LONG );

			// Those with a formatted displayTitle
			// foaf:homepage&nbsp;<span style="font-size:small;">(Foaf:homepage)</span>
			if ( strpos( $label, '&nbsp;<span' ) !== false ) {
				list( $label, $extra ) = explode( '&nbsp;', $label );
				$extra = '&nbsp;' . $extra;
			}

			$dataValue->setCaption( $label );
		}

		return $dataValue->getLongHTMLText( smwfGetLinker() ) . $extra;
	}

	/**
	 * Displays a value, including all relevant links (browse and search by property)
	 *
	 * @since 2.5
	 *
	 * @param DataValue $value
	 * @param PropertyValue $property
	 * @param boolean $incoming
	 *
	 * @return string
	 */
	public static function getFormattedValue( DataValue $dataValue, PropertyValue $propertyValue, $incoming = false ) {

		$linker = smwfGetLinker();
		$dataItem = $dataValue->getContextPage();

		// Allow the DV formatter to access a specific language code
		$dataValue->setOption(
			DataValue::OPT_CONTENT_LANGUAGE,
			Localizer::getInstance()->getPreferredContentLanguage( $dataItem )->getCode()
		);

		$dataValue->setOption(
			DataValue::OPT_USER_LANGUAGE,
			Localizer::getInstance()->getUserLanguage()->getCode()
		);

		// Use LOCL formatting where appropriate (date)
		$dataValue->setOutputFormat( 'LOCL' );

		// For a redirect, disable the DisplayTitle to show the original (aka source) page
		if ( $propertyValue->isValid() && $propertyValue->getDataItem()->getKey() == '_REDI' ) {
			$dataValue->setOption( 'smwgDVFeatures', ( $dataValue->getOption( 'smwgDVFeatures' ) & ~SMW_DV_WPV_DTITLE ) );
		}

		$html = $dataValue->getLongHTMLText( $linker );

		if ( $dataValue->getTypeID() === '_wpg' || $dataValue->getTypeID() === '__sob' ) {
			$html .= "&#160;" . Infolink::newBrowsingLink( '+', $dataValue->getLongWikiText() )->getHTML( $linker );
		} elseif ( $incoming && $propertyValue->isVisible() ) {
			$html .= "&#160;" . Infolink::newInversePropertySearchLink( '+', $dataValue->getTitle(), $propertyValue->getDataItem()->getLabel(), 'smwsearch' )->getHTML( $linker );
		} elseif ( $dataValue->getProperty() instanceof DIProperty && $dataValue->getProperty()->getKey() !== '_INST' ) {
			$html .= $dataValue->getInfolinkText( SMW_OUTPUT_HTML, $linker );
		}

		return $html;
	}

	/**
	 * Figures out the label of the property to be used. For outgoing ones it is just
	 * the text, for incoming ones we try to figure out the inverse one if needed,
	 * either by looking for an explicitly stated one or by creating a default one.
	 *
	 * @since 2.5
	 *
	 * @param PropertyValue $property
	 * @param boolean $incoming
	 * @param boolean $showInverse
	 *
	 * @return string
	 */
	public static function getPropertyLabel( PropertyValue $propertyValue, $incoming = false, $showInverse = false ) {

		$proptext = null;

		$linker = smwfGetLinker();
		$property = $propertyValue->getDataItem();

		if ( $propertyValue->isVisible() ) {
			$propertyValue->setCaption( self::findPropertyLabel( $propertyValue, $incoming, $showInverse ) );
			$proptext = $propertyValue->getShortHTMLText( $linker ) . "\n";
		} elseif ( $property->getKey() == '_INST' ) {
			$proptext = $linker->specialLink( 'Categories' );
		} elseif ( $property->getKey() == '_REDI' ) {
			$proptext = $linker->specialLink( 'Listredirects', 'isredirect' );
		}

		return $proptext;
	}

	private static function findPropertyLabel( PropertyValue $propertyValue, $incoming = false, $showInverse = false ) {

		$property = $propertyValue->getDataItem();
		$contextPage = $propertyValue->getContextPage();

		// Change caption for the incoming, Has query instance
		if ( $incoming && $property->getKey() === '_ASK' && strpos( $contextPage->getSubobjectName(), '_QUERY' ) === false ) {
			return self::addNonBreakingSpace( wfMessage( 'smw-query-reference-link-label' )->text() );
		}

		if ( !$incoming || !$showInverse ) {
			return self::addNonBreakingSpace( $propertyValue->getWikiValue() );
		}

		$inverseProperty = PropertyValue::makeUserProperty( wfMessage( 'smw_inverse_label_property' )->text() );

		$dataItems = ApplicationFactory::getInstance()->getStore()->getPropertyValues(
			$property->getDiWikiPage(),
			$inverseProperty->getDataItem()
		);

		if ( $dataItems !== array() ) {
			$text = str_replace( '_', ' ', end( $dataItems )->getDBKey() );
		} else {
			$text = wfMessage( 'smw_inverse_label_default', $propertyValue->getWikiValue() )->text();
		}

		return self::addNonBreakingSpace( $text );
	}

	/**
	 * Replace the last two space characters with unbreakable spaces for beautification.
	 *
	 * @since 2.5
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public static function addNonBreakingSpace( $text ) {

		$nonBreakingSpace = html_entity_decode( '&#160;', ENT_NOQUOTES, 'UTF-8' );
		$text = preg_replace( '/[\s]/u', $nonBreakingSpace, $text, - 1, $count );

		if ( $count > 2) {
			return preg_replace( '/($nonBreakingSpace)/u', ' ', $text, max( 0, $count - 2 ) );
		}

		return $text;
	}

}
