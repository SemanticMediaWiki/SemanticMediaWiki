<?php

namespace SMW\DataValues;

use SMW\DataValueFactory;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyChainValue extends StringValue {

	/**
	 * DV identifier
	 */
	const TYPE_ID = '__pchn';

	/**
	 * @var PropertyValue[]
	 */
	private $propertyValues = [];

	/**
	 * @var PropertyValue
	 */
	private $lastPropertyChainValue;

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( self::TYPE_ID );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $value
	 *
	 * @return boolean
	 */
	public static function isChained( $value ) {
		return strpos( $value, '.' ) !== false;
	}

	/**
	 * @since 2.5
	 *
	 * @return PropertyValue
	 */
	public function getLastPropertyChainValue() {
		return $this->lastPropertyChainValue;
	}

	/**
	 * @since 2.5
	 *
	 * @return PropertyValue[]
	 */
	public function getPropertyChainValues() {
		return $this->propertyValues;
	}

	/**
	 * @see DataValue::getShortWikiText
	 */
	public function setCaption( $caption ) {
		$this->m_caption = $caption;

		if ( $this->lastPropertyChainValue !== null ) {
			$this->lastPropertyChainValue->setCaption( $caption );
		}
	}

	/**
	 * @see DataValue::getShortWikiText
	 */
	public function getShortWikiText( $linker = null ) {

		if ( $this->lastPropertyChainValue !== null ) {
			return $this->lastPropertyChainValue->getShortWikiText( $linker ) . $this->doHintPropertyChainMembers();
		}

		return '';
	}

	/**
	 * @see DataValue::getLongWikiText
	 */
	public function getLongWikiText( $linker = null ) {

		if ( $this->lastPropertyChainValue !== null ) {
			return $this->lastPropertyChainValue->getLongWikiText( $linker ) . $this->doHintPropertyChainMembers();
		}

		return '';
	}

	/**
	 * @see DataValue::getShortHTMLText
	 */
	public function getShortHTMLText( $linker = null ) {

		if ( $this->lastPropertyChainValue !== null ) {
			return $this->lastPropertyChainValue->getShortHTMLText( $linker ) . $this->doHintPropertyChainMembers();
		}

		return '';
	}

	/**
	 * @see DataValue::getLongHTMLText
	 */
	public function getLongHTMLText( $linker = null ) {

		if ( $this->lastPropertyChainValue !== null ) {
			return $this->lastPropertyChainValue->getLongHTMLText( $linker ) . $this->doHintPropertyChainMembers();
		}

		return '';
	}

	/**
	 * @see DataValue::getWikiValue
	 */
	public function getWikiValue() {
		return $this->lastPropertyChainValue !== null ? $this->lastPropertyChainValue->getWikiValue() : '';
	}

	/**
	 * @see PropertyValue::isVisible
	 */
	public function isVisible() {
		return $this->isValid() && ( $this->lastPropertyChainValue->getDataItem()->isUserDefined() || $this->lastPropertyChainValue->getDataItem()->getLabel() !== '' );
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 *
	 * @param $dataitem SMWDataItem
	 *
	 * @return boolean
	 */
	protected function loadDataItem( DataItem $dataItem ) {

		if ( !$dataItem instanceof DIBlob ) {
			return false;
		}

		$this->m_caption = false;
		$this->m_dataitem = $dataItem;

		$this->initPropertyChain( $dataItem->getString() );

		return true;
	}

	/**
	 * @see DataValue::parseUserValue
	 * @note called by DataValue::setUserValue
	 *
	 * @param string $userValue
	 */
	protected function parseUserValue( $value ) {

		if ( $value === '' ) {
			$this->addErrorMsg( 'smw_emptystring' );
		}

		if ( !$this->isChained( $value ) ) {
			$this->addErrorMsg( 'smw-datavalue-propertychain-missing-chain-indicator' );
		}

		$this->initPropertyChain( $value );

		$this->m_dataitem = new DIBlob( $value );
	}

	private function initPropertyChain( $value ) {

		$chain = explode( '.', $value );

		// Get the last which represents the final output
		// Foo.Bar.Foobar.Baz
		$last = array_pop( $chain );

		$this->lastPropertyChainValue = DataValueFactory::getInstance()->newPropertyValueByLabel( $last );

		if ( !$this->lastPropertyChainValue->isValid() ) {
			return $this->addError( $this->lastPropertyChainValue->getErrors() );
		}

		$this->lastPropertyChainValue->copyOptions( $this->getOptions() );

		// Generate a forward list from the remaining property labels
		// Foo.Bar.Foobar
		foreach ( $chain as $value ) {
			$propertyValue = DataValueFactory::getInstance()->newPropertyValueByLabel( $value );

			if ( !$propertyValue->isValid() ) {
				continue;
			}

			$propertyValue->copyOptions( $this->getOptions() );

			$this->propertyValues[] = $propertyValue;
		}
	}

	private function doHintPropertyChainMembers() {
		return '&nbsp;' . \Html::rawElement( 'span', [ 'title' => $this->m_dataitem ], '⠉' );
	}

}
