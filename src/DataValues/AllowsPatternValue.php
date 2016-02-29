<?php

namespace SMW\DataValues;

use SMWStringValue as StringValue;
use SMW\Message;
use SMWDataValue as DataValue;
use SMW\DIProperty;
use SMW\ApplicationFactory;
use SMW\Localizer;

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
	 * @var AllowsPatternContentParser
	 */
	private $allowsPatternContentParser;

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( '__pvap' );
		$this->allowsPatternContentParser = ValueParserFactory::getInstance()->newAllowsPatternContentParser();
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

		if ( ( $this->getOptionValueFor( 'smwgDVFeatures' ) & SMW_DV_PVAP ) == 0 && $value !== '' ) {
			$this->addErrorMsg( array( 'smw-datavalue-feature-not-supported', 'Allows pattern (SMW_DV_PVAP)' ) );
		}

		$content = $this->allowsPatternContentParser->parse(
			$value
		);

		if ( !$content ) {
			$this->addErrorMsg( array( 'smw-datavalue-allows-pattern-reference-unknown', $value ) );
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

	/**
	 * @since 2.4
	 *
	 * @param DataValue $dataValue
	 *
	 * @return boolean
	 */
	public function doCheckAllowedPatternFor( DataValue $dataValue ) {

		if ( ( $dataValue->getOptionValueFor( 'smwgDVFeatures' ) & SMW_DV_PVAP ) == 0 ) {
			return false;
		}

		$key = $dataValue->getProperty()->getKey();

		if ( ( $reference = $this->getPropertySpecificationLookup()->getAllowedPatternFor( $dataValue->getProperty() ) ) === '' ) {
			return false;
		}

		$content = $this->allowsPatternContentParser->parse(
			$reference
		);

		if ( !$content ) {
			return false;
		}

		// Prevent a possible remote code execution vulnerability in connection
		// with PCRE
		$pattern = str_replace( array( '/e' ), array( '' ), trim( $content ) );

		// Add a mandatory backslash
		if ( $pattern !== '' && $pattern{0} !== '/' ) {
			$pattern = '/' . $pattern;
		}

		if ( substr( $pattern, -1 ) !== '/' ) {
			$pattern = $pattern . '/';
		}

		if ( !preg_match( $pattern, $dataValue->getDataItem()->getSortKey() ) ) {
			$this->addErrorMsg(
				array(
					'smw-datavalue-allows-pattern-mismatch',
					$dataValue->getWikiValue(),
					$reference
				)
			);
			return false;
		}

		return true;
	}

}
