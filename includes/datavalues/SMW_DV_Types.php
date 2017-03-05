<?php

use SMW\DataTypeRegistry;
use SMW\Message;

/**
 * This datavalue implements special processing suitable for defining types of
 * properties. Types behave largely like values of type SMWWikiPageValue
 * with three main differences. First, they actively check if a value is an
 * alias for another type, modifying the internal representation accordingly.
 * Second, they have a modified display for emphasizing if some type is defined
 * in SMW (built-in). Third, they use type ids for storing data (DB keys)
 * instead of using page titles.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWTypesValue extends SMWDataValue {
	protected $m_isAlias; // record whether this is an alias to another type, used to avoid duplicates when listing page types
	protected $m_realLabel;
	protected $m_givenLabel;
	protected $m_typeId;

	public static function newFromTypeId( $typeId ) {
		$result = new SMWTypesValue( '__typ' );
		try {
			$dataItem = self::getTypeUriFromTypeId( $typeId );
		} catch ( SMWDataItemException $e ) {
			$dataItem = self::getTypeUriFromTypeId( 'notype' );
		}
		$result->setDataItem( $dataItem );
		return $result;
	}

	public static function getTypeUriFromTypeId( $typeId ) {
		return new SMWDIUri( 'http', 'semantic-mediawiki.org/swivt/1.0', '', $typeId );
	}

	protected function parseUserValue( $value ) {
		global $smwgHistoricTypeNamespace;

		if ( $this->m_caption === false ) {
			$this->m_caption = $value;
		}

		$valueParts = explode( ':', $value, 2 );
		$contentLanguage = $this->getOption( self::OPT_CONTENT_LANGUAGE );

		if ( $smwgHistoricTypeNamespace && count( $valueParts ) > 1 ) {
			$namespace = smwfNormalTitleText( $valueParts[0] );
			$value = $valueParts[1];
			$typeNamespace = Localizer::getInstance()->getLanguage( $contentLanguage )->getNsText( SMW_NS_TYPE );
			if ( $namespace != $typeNamespace ) {
				$this->addErrorMsg( array( 'smw_wrong_namespace', $typeNamespace ) );
			}
		}

		if ( $value !== '' && $value{0} === '_' ) {
			$this->m_typeId = $value;
		} else {
			$this->m_givenLabel = smwfNormalTitleText( $value );
			$this->m_typeId = DataTypeRegistry::getInstance()->findTypeByLanguage( $this->m_givenLabel, $contentLanguage );
		}

		if ( $this->m_typeId === '' ) {
			$this->addErrorMsg( array( 'smw_unknowntype', $this->m_givenLabel ) );
			$this->m_realLabel = $this->m_givenLabel;
		} else {
			$this->m_realLabel = DataTypeRegistry::getInstance()->findTypeLabel( $this->m_typeId );
		}
		$this->m_isAlias = ( $this->m_realLabel === $this->m_givenLabel ) ? false : true;

		try {
			$this->m_dataitem = self::getTypeUriFromTypeId( $this->m_typeId );
		} catch ( SMWDataItemException $e ) {
			$this->m_dataitem = self::getTypeUriFromTypeId( 'notype' );
			$this->addErrorMsg( array( 'smw-datavalue-type-invalid-typeuri', $this->m_typeId ) );
		}
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 * @param $dataitem SMWDataItem
	 * @return boolean
	 */
	protected function loadDataItem( SMWDataItem $dataItem ) {
		if ( ( $dataItem instanceof SMWDIUri ) && ( $dataItem->getScheme() == 'http' ) &&
		     ( $dataItem->getHierpart() == 'semantic-mediawiki.org/swivt/1.0' ) &&
		     ( $dataItem->getQuery() === '' ) ) {
			$this->m_isAlias = false;
			$this->m_typeId = $dataItem->getFragment();
			$this->m_realLabel = DataTypeRegistry::getInstance()->findTypeLabel( $this->m_typeId );
			$this->m_caption = $this->m_givenLabel = $this->m_realLabel;
			$this->m_dataitem = $dataItem;
			return true;
		} else {
			return false;
		}
	}

	public function getShortWikiText( $linker = null ) {
		global $wgContLang;
		if ( !$linker || $this->m_outformat === '-' || $this->m_caption === '' ) {
			return $this->m_caption;
		} else {
			$titleText = $this->getSpecialPageTitleText();
			$namespace = $wgContLang->getNsText( NS_SPECIAL );
			return "[[$namespace:$titleText|{$this->m_caption}]]";
		}
	}

	public function getShortHTMLText( $linker = null ) {
		if ( !$linker || $this->m_outformat === '-' || $this->m_caption === ''  ) {
			return htmlspecialchars( $this->m_caption );
		} else {
			$title = Title::makeTitle( NS_SPECIAL, $this->getSpecialPageTitleText() );
			return $linker->link( $title, htmlspecialchars( $this->m_caption ) );
		}
	}

	public function getLongWikiText( $linker = null ) {
		global $wgContLang;
		if ( !$linker || $this->m_realLabel === '' ) {
			return $this->m_realLabel;
		} else {
			$titleText = $this->getSpecialPageTitleText();
			$namespace = $wgContLang->getNsText( NS_SPECIAL );
			return "[[$namespace:$titleText|{$this->m_realLabel}]]";
		}
	}

	public function getLongHTMLText( $linker = null ) {
		if ( !$linker || $this->m_realLabel === '' ) {
			return htmlspecialchars( $this->m_realLabel );
		} else {
			$title = Title::makeTitle( NS_SPECIAL, $this->getSpecialPageTitleText() );
			return $linker->link( $title, htmlspecialchars( $this->m_realLabel ) );
		}
	}

	/**
	 * Gets the title text for the types special page.
	 *
	 * @since 1.6
	 *
	 * @return string
	 */
	protected function getSpecialPageTitleText() {
		return SpecialPageFactory::getLocalNameFor( 'Types', $this->m_realLabel );
	}

	public function getWikiValue() {
		return $this->m_realLabel;
	}

	public function getHash() {
		return $this->m_realLabel;
	}

	/**
	 * This class uses type ids as DB keys.
	 *
	 * @return string
	 */
	public function getDBkey() {
		return ( $this->isValid() ) ? DataTypeRegistry::getInstance()->findTypeID( $this->m_realLabel ) : '';
	}

	/**
	 * Is this a built-in datatype shipped with SMW (or an extension of SMW)?
	 * (Alternatively it would be a user-defined derived datatype.)
	 *
	 * @deprecated As of SMW 1.6, there are no more user-defined datatypes, making this method useless. Will vanish in SMW 1.6.
	 */
	public function isBuiltIn() {
		return true;
	}

	/**
	 * Is this an alias for another datatype in SMW? This information is used to
	 * explain entries in Special:Types that are found since they have pages.
	 *
	 * @return boolean
	 */
	public function isAlias() {
		return $this->m_isAlias;
	}

}

