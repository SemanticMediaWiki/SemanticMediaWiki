<?php

namespace SMW\DataValues;

use SMW\Localizer;
use SMW\Message;

/**
 * To support regular expressions in connection with the `Allows pattern`
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
	 * Fixed Mediawiki page
	 */
	const REFERENCE_PAGE_ID = 'Smw_allows_pattern';

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
			$this->addErrorMsg( [ 'smw-datavalue-feature-not-supported', 'Allows pattern (SMW_DV_PVAP)' ] );
		}

		$allowsPatternValueParser = $this->dataValueServiceFactory->getValueParser( $this );

		$content = $allowsPatternValueParser->parse(
			$value
		);

		if ( !$content ) {
			$this->addErrorMsg( [ 'smw-datavalue-allows-pattern-reference-unknown', $value ], Message::ESCAPED );
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

		$id = $this->getDataItem()->getString();

		return '[['. Localizer::getInstance()->getNamespaceTextById( NS_MEDIAWIKI ) . ':' . self::REFERENCE_PAGE_ID . '|' . $id .']]';
	}

	/**
	 * @see DataValue::getLongHtmlText
	 *
	 * @param string $value
	 */
	public function getLongHtmlText( $linker = null ) {
		return $this->getShortHtmlText( $linker );
	}

	/**
	 * @see DataValue::getShortHtmlText
	 *
	 * @param string $value
	 */
	public function getShortHtmlText( $linker = null ) {

		if ( !$this->isValid() ) {
			return '';
		}

		$id = $this->getDataItem()->getString();
		$title = \Title::newFromText( self::REFERENCE_PAGE_ID, NS_MEDIAWIKI );

		return \Html::rawElement(
			'a',
			[
				'href'   => $title->getLocalUrl(),
				'target' => '_blank'
			],
			$id
		);
	}

}
