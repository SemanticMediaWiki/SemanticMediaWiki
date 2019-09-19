<?php

namespace SMW\DataValues;

use SMW\DataTypeRegistry;
use SMW\Localizer;
use SMWDataItem as DataItem;
use SMWDataItemException as DataItemException;
use SMWDataValue as DataValue;
use SMWDIUri as DIUri;
use SpecialPageFactory;
use Title;

/**
 * This datavalue implements special processing suitable for defining types of
 * properties. Types behave largely like values of type SMWWikiPageValue
 * with three main differences. First, they actively check if a value is an
 * alias for another type, modifying the internal representation accordingly.
 * Second, they have a modified display for emphasizing if some type is defined
 * in SMW (built-in). Third, they use type ids for storing data (DB keys)
 * instead of using page titles.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class TypesValue extends DataValue {

	/**
	 * DV identifier
	 */
	const TYPE_ID = '__typ';

	/**
	 * @var string
	 */
	private $typeLabel;

	/**
	 * @var string
	 */
	private $givenLabel;

	/**
	 * @var string
	 */
	private $m_typeId;

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( self::TYPE_ID );
	}

	/**
	 * @since 1.6
	 *
	 * @param string $typeId
	 *
	 * @return TypesValue
	 */
	public static function newFromTypeId( $typeId ) {
		$result = new TypesValue( self::TYPE_ID );

		try {
			$dataItem = self::getTypeUriFromTypeId( $typeId );
		} catch ( DataItemException $e ) {
			$dataItem = self::getTypeUriFromTypeId( 'notype' );
		}

		$result->setDataItem( $dataItem );

		return $result;
	}

	/**
	 * @since 1.6
	 *
	 * @param string $typeId
	 *
	 * @return DIUri
	 */
	public static function getTypeUriFromTypeId( $typeId ) {
		return new DIUri( 'http', 'semantic-mediawiki.org/swivt/1.0', '', $typeId );
	}

	/**
	 * @see DataValue::getShortWikiText
	 *
	 * {@inheritDoc}
	 */
	public function getShortWikiText( $linker = null ) {

		if ( !$linker || $this->m_outformat === '-' || $this->m_caption === '' ) {
			return $this->m_caption;
		}

		$titleText = $this->makeSpecialPageTitleText();

		$contentLanguage = Localizer::getInstance()->getLanguage(
			$this->getOption( self::OPT_CONTENT_LANGUAGE )
		);

		$namespace = $contentLanguage->getNsText(
			NS_SPECIAL
		);

		return "[[$namespace:$titleText|{$this->m_caption}]]";
	}

	/**
	 * @see DataValue::getShortHTMLText
	 *
	 * {@inheritDoc}
	 */
	public function getShortHTMLText( $linker = null ) {

		if ( !$linker || $this->m_outformat === '-' || $this->m_caption === ''  ) {
			return htmlspecialchars( $this->m_caption );
		}

		$title = Title::makeTitle(
			NS_SPECIAL,
			$this->makeSpecialPageTitleText()
		);

		return $linker->link( $title, htmlspecialchars( $this->m_caption ) );
	}

	/**
	 * @see DataValue::getLongWikiText
	 *
	 * {@inheritDoc}
	 */
	public function getLongWikiText( $linker = null ) {

		if ( !$linker || $this->typeLabel === '' ) {
			return $this->typeLabel;
		}

		$titleText = $this->makeSpecialPageTitleText();

		$contentLanguage = Localizer::getInstance()->getLanguage(
			$this->getOption( self::OPT_CONTENT_LANGUAGE )
		);

		$namespace = $contentLanguage->getNsText(
			NS_SPECIAL
		);

		return "[[$namespace:$titleText|{$this->typeLabel}]]";
	}

	/**
	 * @see DataValue::getLongHTMLText
	 *
	 * {@inheritDoc}
	 */
	public function getLongHTMLText( $linker = null ) {

		if ( !$linker || $this->typeLabel === '' ) {
			return htmlspecialchars( $this->typeLabel );
		}

		$title = Title::makeTitle(
			NS_SPECIAL,
			$this->makeSpecialPageTitleText()
		);

		return $linker->link( $title, htmlspecialchars( $this->typeLabel ) );
	}

	/**
	 * @see DataValue::getWikiValue
	 *
	 * {@inheritDoc}
	 */
	public function getWikiValue() {
		return $this->typeLabel;
	}

	/**
	 * @see DataValue::loadDataItem
	 *
	 * {@inheritDoc}
	 */
	protected function parseUserValue( $value ) {

		if ( $this->m_caption === false ) {
			$this->m_caption = $value;
		}

		$valueParts = explode( ':', $value, 2 );
		$contentLanguage = $this->getOption( self::OPT_CONTENT_LANGUAGE );

		if ( $value !== '' && $value[0] === '_' ) {
			$this->m_typeId = $value;
		} else {
			$this->givenLabel = smwfNormalTitleText( $value );
			$this->m_typeId = DataTypeRegistry::getInstance()->findTypeByLabelAndLanguage( $this->givenLabel, $contentLanguage );
		}

		if ( $this->m_typeId === '' ) {
			$this->addErrorMsg( [ 'smw_unknowntype', $this->givenLabel ] );
			$this->typeLabel = $this->givenLabel;
		} else {
			$this->typeLabel = DataTypeRegistry::getInstance()->findTypeLabel( $this->m_typeId );
		}

		try {
			$this->m_dataitem = self::getTypeUriFromTypeId( $this->m_typeId );
		} catch ( DataItemException $e ) {
			$this->m_dataitem = self::getTypeUriFromTypeId( 'notype' );
			$this->addErrorMsg( [ 'smw-datavalue-type-invalid-typeuri', $this->m_typeId ] );
		}
	}

	/**
	 * @see DataValue::loadDataItem
	 *
	 * {@inheritDoc}
	 */
	protected function loadDataItem( DataItem $dataItem ) {

		if ( ( $dataItem instanceof DIUri ) && ( $dataItem->getScheme() == 'http' ) &&
			( $dataItem->getHierpart() == 'semantic-mediawiki.org/swivt/1.0' ) &&
			( $dataItem->getQuery() === '' ) ) {

			$this->m_typeId = $dataItem->getFragment();
			$this->typeLabel = DataTypeRegistry::getInstance()->findTypeLabel( $this->m_typeId );
			$this->m_caption = $this->givenLabel = $this->typeLabel;
			$this->m_dataitem = $dataItem;

			return true;
		}

		return false;
	}

	protected function makeSpecialPageTitleText() {
		return SpecialPageFactory::getLocalNameFor( 'Types', $this->typeLabel );
	}

}
