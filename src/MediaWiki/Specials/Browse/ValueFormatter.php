<?php

namespace SMW\MediaWiki\Specials\Browse;

use SMW\Message;
use SMW\ApplicationFactory;
use SMWDataValue as DataValue;
use SMWDataItem as DataItem;
use SMWPropertyValue as PropertyValue;
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
	 * Displays a value, including all relevant links (browse and search by property)
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

		if ( !$incoming || !$showInverse ) {
			return self::addNonBreakingSpace( $propertyValue->getWikiValue() );
		}

		$inverseProperty = PropertyValue::makeUserProperty( wfMessage( 'smw_inverse_label_property' )->text() );
		$property = $propertyValue->getDataItem();

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

		return  $text;
	}

}
