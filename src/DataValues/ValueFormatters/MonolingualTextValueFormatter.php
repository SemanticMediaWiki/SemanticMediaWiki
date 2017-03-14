<?php

namespace SMW\DataValues\ValueFormatters;

use RuntimeException;
use SMW\DataValueFactory;
use SMW\DataValues\MonolingualTextValue;
use SMW\DIProperty;
use SMW\Message;
use SMWDataValue as DataValue;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class MonolingualTextValueFormatter extends DataValueFormatter {

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function isFormatterFor( DataValue $dataValue ) {
		return $dataValue instanceof MonolingualTextValue;
	}

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function format( $type, $linker = null ) {

		if ( !$this->dataValue instanceof MonolingualTextValue ) {
			throw new RuntimeException( "The formatter is missing a valid MonolingualTextValue object" );
		}

		if (
			$this->dataValue->getCaption() !== false &&
			( $type === self::WIKI_SHORT || $type === self::HTML_SHORT ) ) {
			return $this->dataValue->getCaption();
		}

		return $this->getOutputText( $type, $linker );
	}

	protected function getOutputText( $type, $linker = null ) {

		if ( !$this->dataValue->isValid() ) {
			return ( ( $type == self::WIKI_SHORT ) || ( $type == self::HTML_SHORT ) ) ? '' : $this->dataValue->getErrorText();
		}

		// For the inverse case, return the subject that contains the reference
		// for Foo annotated with [[Bar::abc@en]] -> [[-Bar::Foo]]
		if ( $this->dataValue->getProperty() !== null && $this->dataValue->getProperty()->isInverse() ) {

			$dataItems = $this->dataValue->getDataItem()->getSemanticData()->getPropertyValues(
				new DIProperty(  $this->dataValue->getProperty()->getKey() )
			);

			$dataItem = reset( $dataItems );

			if ( !$dataItem ) {
				return '';
			}

			return $dataItem->getDBKey();
		}

		return $this->doFormatFinalOutputFor( $type, $linker );
	}

	private function doFormatFinalOutputFor( $type, $linker ) {

		$text = '';
		$languagecode = '';

		foreach ( $this->dataValue->getPropertyDataItems() as $property ) {

			// If we wanted to omit the language code display for some outputs then
			// this is the point to make it happen
			if ( ( $type == self::HTML_LONG || $type == self::WIKI_SHORT ) && $property->getKey() === '_LCODE' ) {
			//continue;
			}

			$dataItems = $this->dataValue->getDataItem()->getSemanticData()->getPropertyValues(
				$property
			);

			// Should not happen but just in case
			if ( !$dataItems === [] ) {
				$this->dataValue->addErrorMsg( 'smw-datavalue-monolingual-dataitem-missing' );
				continue;
			}

			$dataItem = reset( $dataItems );

			if ( $dataItem === false ) {
				continue;
			}

			$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
				$dataItem,
				$property
			);

			$result = $this->findValueOutputFor(
				$type,
				$dataValue,
				$linker
			);

			if ( $property->getKey() === '_LCODE' && $type !== self::VALUE ) {
				$languagecode = ' ' . Message::get( [ 'smw-datavalue-monolingual-lcode-parenthesis', $result ] );
			} elseif ( $property->getKey() === '_LCODE' && $type === self::VALUE ) {
				$languagecode = '@' . $result;
			} else {
				$text = $result;
			}
		}

		return $text . $languagecode;
	}

	private function findValueOutputFor( $type, $dataValue, $linker ) {
		switch ( $type ) {
			case self::VALUE:
				return $dataValue->getWikiValue();
			case self::WIKI_SHORT:
				return $dataValue->getShortWikiText( $linker );
			case self::HTML_SHORT:
				return $dataValue->getShortHTMLText( $linker );
			case self::WIKI_LONG:
				return $dataValue->getShortWikiText( $linker );
			case self::HTML_LONG:
				return $dataValue->getShortHTMLText( $linker );
		}
	}

}
