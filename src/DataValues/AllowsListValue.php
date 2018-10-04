<?php

namespace SMW\DataValues;

use SMW\Localizer;

/**
 * To support value list via the NS_MEDIAWIKI namespace
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class AllowsListValue extends StringValue {

	/**
	 * DV identifier
	 */
	const TYPE_ID = '__pvali';

	/**
	 * Fixed Mediawiki NS prefix
	 */
	const LIST_PREFIX = 'Smw_allows_list_';

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

		$allowsListValueParser = $this->dataValueServiceFactory->getValueParser( $this );

		$allowsListValueParser->parse( $value );

		if ( $allowsListValueParser->getErrors() !== [] ) {
			foreach ( $allowsListValueParser->getErrors() as $error ) {
				$this->addErrorMsg( $error );
			}
		} else {
			parent::parseUserValue( $value );
		}
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

		return '[['. Localizer::getInstance()->getNamespaceTextById( NS_MEDIAWIKI ) . ':' . self::LIST_PREFIX . $id . '|' . $id .']]';
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
		$title = \Title::newFromText( self::LIST_PREFIX . $id, NS_MEDIAWIKI );

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
