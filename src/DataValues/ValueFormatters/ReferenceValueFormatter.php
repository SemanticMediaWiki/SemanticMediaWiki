<?php

namespace SMW\DataValues\ValueFormatters;

use RuntimeException;
use SMW\DataValueFactory;
use SMW\DataValues\ExternalIdentifierValue;
use SMW\DataValues\ReferenceValue;
use SMW\DIWikiPage;
use SMW\Message;
use SMWDataValue as DataValue;
use SMWDITime as DITime;
use SMWDIUri as DIUri;
use SMWPropertyValue as PropertyValue;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ReferenceValueFormatter extends DataValueFormatter {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isFormatterFor( DataValue $dataValue ) {
		return $dataValue instanceof ReferenceValue;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function format( $type, $linker = null ) {

		if ( !$this->dataValue instanceof ReferenceValue ) {
			throw new RuntimeException( "The formatter is missing a valid ReferenceValue object" );
		}

		if ( $this->dataValue->getCaption() !== false &&
			( $type === self::WIKI_SHORT || $type === self::HTML_SHORT ) ) {
			return $this->dataValue->getCaption();
		}

		return $this->getOutputText( $type, $linker );
	}

	protected function getOutputText( $type, $linker = null ) {

		if ( !$this->dataValue->isValid() ) {
			return ( ( $type == self::WIKI_SHORT ) || ( $type == self::HTML_SHORT ) ) ? '' : $this->dataValue->getErrorText();
		}

		return $this->createOutput( $type, $linker );
	}

	private function createOutput( $type, $linker ) {

		$results = $this->getListOfFormattedPropertyDataItems(
			$type,
			$linker,
			$this->dataValue->getPropertyDataItems()
		);

		if ( $type == self::VALUE || $linker === null ) {
			return implode( ';', $results );
		}

		$result = array_shift( $results );
		$class = 'smw-reference-otiose';

		// "smw-highlighter smwttinline" signals to invoke the tooltip
		if ( count( $results ) > 0 ) {
			$class = 'smw-reference smw-reference-indicator smw-highlighter smwttinline';
		}

		// Add an extra "title" attribute to support nojs environments by allowing
		// it to display references even without JS, it will be removed when JS is available
		// to show the "normal" tooltip
		$result .= \Html::rawElement(
			'span',
			[
				'class' => $class,
				'data-title'   =>  Message::get( 'smw-ui-tooltip-title-reference', Message::TEXT, Message::USER_LANGUAGE ),
				'data-content' => '<ul><li>' . implode( '</li><li>', $results ) . '</li></ul>',
				'title' => strip_tags( implode( ', ', $results ) )
			]
		);

		return $result;
	}

	private function getListOfFormattedPropertyDataItems( $type, $linker, $propertyDataItems ) {

		$results = [];

		foreach ( $propertyDataItems as $propertyDataItem ) {

			$propertyValues = $this->dataValue->getDataItem()->getSemanticData()->getPropertyValues( $propertyDataItem );
			$dataItem = reset( $propertyValues );

			// By definition the first element in the list is the VALUE other
			// members are referencing to
			$isValue = $results === [];
			$dataValue = null;

			if ( $dataItem !== false ) {
				$dataValue = DataValueFactory::getInstance()->newDataValueByItem( $dataItem, $propertyDataItem );
				$output = $this->findValueOutputFor( $isValue, $type, $dataValue, $linker );
			} else {
				$output = '?';
			}

			// Return a plain value in case no linker object is available
			if ( $dataValue !== null && $linker === null ) {
				return [ $dataValue->getWikiValue() ];
			}

			$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
				$propertyDataItem
			);

			// Tooltip in tooltip isn't expected to work therefore avoid them
			// when generating property labels in a reference output
			$dataValue->setOption( PropertyValue::OPT_NO_HIGHLIGHT, true );

			if ( !$isValue && $type !== self::VALUE ) {
				$output = Message::get(
					[
						'smw-datavalue-reference-outputformat',
						$dataValue->getShortHTMLText( smwfGetLinker() ),
						$output
					],
					Message::TEXT
				);
			}

			$results[] = $output;
		}

		return $results;
	}

	private function findValueOutputFor( $isValue, $type, $dataValue, $linker ) {

		$dataItem = $dataValue->getDataItem();

		// Turn Uri/Page links into a href representation when not used as value
		if ( !$isValue &&
			( $dataItem instanceof DIUri || $dataItem instanceof DIWikiPage ) &&
			$type !== self::VALUE || $dataValue->getTypeID() === ExternalIdentifierValue::TYPE_ID ) {
			return $dataValue->getShortHTMLText( smwfGetLinker() );
		}

		// Dates and times are to be displayed in a localized format
		if ( !$isValue && $dataItem instanceof DITime && $type !== self::VALUE ) {
			$dataValue->setOutputFormat( 'LOCL' );
		}

		switch ( $type ) {
			case self::VALUE:
				return $dataValue->getWikiValue();
			case self::WIKI_SHORT:
				return $dataValue->getShortWikiText( $linker );
			case self::HTML_SHORT:
				return $dataValue->getShortHTMLText( $linker );
			case self::WIKI_LONG:
				return $dataValue->getLongWikiText( $linker );
			case self::HTML_LONG:
				return $dataValue->getLongHTMLText( $linker );
		}
	}

}
