<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

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
class SMWTypesValue extends SMWWikiPageValue {

	private $m_isalias; // record whether this is an alias to another type, used to avoid duplicates when listing page types
	protected $m_reallabel;

	protected function parseUserValue( $value ) {
		parent::parseUserValue( $value );
		$this->m_reallabel = SMWDataValueFactory::findTypeLabel( SMWDataValueFactory::findTypeID( $this->m_textform ) );
		$this->m_isalias = ( $this->m_reallabel === $this->m_textform ) ? false : true;
	}

	protected function parseDBkeys( $args ) {
		$pagedbkey = str_replace( ' ', '_', SMWDataValueFactory::findTypeLabel( $args[0] ) );
		parent::parseDBkeys( array( $pagedbkey, $this->m_fixNamespace, '', $this->m_typeid ) );
		$this->m_reallabel = $this->m_textform;
		$this->m_isalias = false;
	}

	/**
	 * @see SMWDataValue::setDataItem()
	 * @param $dataitem SMWDataItem
	 * @return boolean
	 */
	public function setDataItem( SMWDataItem $dataItem ) {
		if ( parent::setDataItem( $dataItem ) ) {
			$this->m_reallabel = $this->m_textform;
			$this->m_isalias = false;
			return true;
		} else {
			return false;
		}
	}

	public function getLongWikiText( $linked = null ) {
		$this->unstub();
		if ( ( $linked === null ) || ( $linked === false ) ) {
			return $this->m_reallabel;
		} else {
			global $wgContLang;
			$typenamespace = $wgContLang->getNsText( SMW_NS_TYPE );
			$id = SMWDataValueFactory::findTypeID( $this->m_reallabel );
			if ( $id { 0 } == '_' ) { // builtin
				smwfLoadExtensionMessages( 'SemanticMediaWiki' );
				SMWOutputs::requireHeadItem( SMW_HEADER_TOOLTIP );
				return '<span class="smwttinline"><span class="smwbuiltin">[[' . $typenamespace . ':' . $this->m_reallabel . '|' . $this->m_reallabel . ']]</span><span class="smwttcontent">' . wfMsgForContent( 'smw_isknowntype' ) . '</span></span>';
			} else {
				return '[[' . $typenamespace . ':' . $this->m_reallabel . '|' . $this->m_reallabel . ']]';
			}
		}
	}

	public function getLongHTMLText( $linker = null ) {
		$this->unstub();
		if ( ( $linker === null ) || ( $linker === false ) ) {
			return $this->m_reallabel;
		} else {
			$title = $this->m_isalias ? Title::newFromText( $this->m_reallabel, SMW_NS_TYPE ) : $this->getTitle();
			$id = SMWDataValueFactory::findTypeID( $this->m_reallabel );
			if ( $id { 0 } == '_' ) { // builtin
				smwfLoadExtensionMessages( 'SemanticMediaWiki' );
				SMWOutputs::requireHeadItem( SMW_HEADER_TOOLTIP );
				return '<span class="smwttinline"><span class="smwbuiltin">' .
				$linker->makeLinkObj( $title, $this->m_reallabel ) . '</span><span class="smwttcontent">' .
				wfMsgForContent( 'smw_isknowntype' ) . '</span></span>';
			} else {
				return $linker->makeLinkObj( $title, $this->m_reallabel );
			}
		}
	}

	public function getDBkeys() {
		return ( $this->isValid() ) ? array( $this->getDBkey() ):array( false );
	}

	public function getSignature() {
		return 't';
	}

	public function getValueIndex() {
		return 0;
	}

	public function getLabelIndex() {
		return 0;
	}

	public function getWikiValue() {
		$this->unstub();
		return $this->m_reallabel;
	}

	public function getHash() {
		$this->unstub();
		return $this->m_reallabel;
	}

	/**
	 * This class uses type ids as DB keys.
	 */
	public function getDBkey() {
		return ( $this->isValid() ) ? SMWDataValueFactory::findTypeID( $this->m_reallabel ) : '';
	}

	/**
	 * Is this a built-in datatype shipped with SMW (or an extension of SMW)?
	 * (Alternatively it would be a user-defined derived datatype.)
	 */
	public function isBuiltIn() {
		$v = $this->getDBkey();
		return ( $v { 0 } == '_' );
	}

	/**
	 * Is this an alias for another datatype in SMW? This information is used to
	 * explain entries in Special:Types that are found since they have pages.
	 */
	public function isAlias() {
		$this->unstub();
		return $this->m_isalias;
	}

}

