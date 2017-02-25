<?php

namespace SMW\DataValues;

use SMW\Localizer;
use SMWStringValue as StringValue;
use SMW\Message;

/**
 * To suppport regular expressions in connection with the `Allows pattern`
 * property.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsPatternValue extends StringValue {

	/**
	 * DV identifier
	 */
	const TYPE_ID = '__pvap';

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( self::TYPE_ID );
	}

	/**
	 * @see DataValue::parseUserValue
	 *
	 * @param string $value
	 */
	protected function parseUserValue( $value ) {

		if ( $value === '' ) {
			$this->addErrorMsg( 'smw_emptystring' );
		}

		if ( ( $this->getOption( 'smwgDVFeatures' ) & SMW_DV_PVAP ) == 0 && $value !== '' ) {
			$this->addErrorMsg( array( 'smw-datavalue-feature-not-supported', 'Allows pattern (SMW_DV_PVAP)' ) );
		}

		$allowsPatternValueParser = $this->dataValueServiceFactory->getValueParser( $this );

		$content = $allowsPatternValueParser->parse(
			$value
		);

		if ( !$content ) {
			$this->addErrorMsg( array( 'smw-datavalue-allows-pattern-reference-unknown', $value ), Message::ESCAPED );
		}

		parent::parseUserValue( $value );
	}

	/**
	 * @see DataValue::getShortWikiText
	 *
	 * @param string $value
	 */
	public function getShortWikiText( $linker = null ) {

		if ( !$this->isValid() ) {
			return '';
		}

		return '[['. Localizer::getInstance()->getNamespaceTextById( NS_MEDIAWIKI ) . ':smw allows pattern' . '|' . $this->getDataItem()->getString() .' ]]';
	}

}
