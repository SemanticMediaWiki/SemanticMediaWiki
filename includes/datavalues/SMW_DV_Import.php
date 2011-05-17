<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue implements datavalues used by special property '_IMPO' used
 * for assigning imported vocabulary to some page of the wiki. It looks up a
 * MediaWiki message to find out whether a user-supplied vocabulary name can be
 * imported in the wiki, and whether its declaration is correct (to the extent
 * that this can be checked).
 *
 * @author Fabian Howahl
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWImportValue extends SMWDataValue {

	protected $m_qname = ''; // string provided by user which is used to look up data on Mediawiki:*-Page
	protected $m_uri = ''; // URI of namespace (without local name)
	protected $m_namespace = ''; // namespace id (e.g. "foaf")
	protected $m_section = ''; // local name (e.g. "knows")
	protected $m_name = ''; // wiki name of the vocab (e.g. "Friend of a Friend")l might contain wiki markup

	protected function parseUserValue( $value ) {
		global $wgContLang;

		$this->m_qname = $value;

		list( $onto_ns, $onto_section ) = explode( ':', $this->m_qname, 2 );
		$msglines = preg_split( "([\n][\s]?)", wfMsgForContent( "smw_import_$onto_ns" ) ); // get the definition for "$namespace:$section"

		if ( count( $msglines ) < 2 ) { // error: no elements for this namespace
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			$this->addError( wfMsgForContent( 'smw_unknown_importns', $onto_ns ) );
			$this->m_dataitem = new SMWDIString( 'ERROR' );
			return;
		}

		// browse list in smw_import_* for section
		list( $onto_uri, $onto_name ) = explode( '|', array_shift( $msglines ), 2 );
		if ( $onto_uri[0] == ' ' ) $onto_uri = mb_substr( $onto_uri, 1 ); // tolerate initial space

		$this->m_uri = $onto_uri;
		$this->m_namespace = $onto_ns;
		$this->m_section = $onto_section;
		$this->m_name = $onto_name;

		foreach ( $msglines as $msgline ) {
			list( $secname, $typestring ) = explode( '|', $msgline, 2 );
			if ( $secname === $onto_section ) {
				list( $namespace, ) = explode( ':', $typestring, 2 );
				// check whether type matches
				switch ( $namespace ) {
					case $wgContLang->getNsText( SMW_NS_TYPE ):
						$elemtype = SMW_NS_PROPERTY;
						break;
					case $wgContLang->getNsText( SMW_NS_PROPERTY ):
						$elemtype = SMW_NS_PROPERTY;
						break;
					case $wgContLang->getNsText( NS_CATEGORY ):
						$elemtype = NS_CATEGORY;
						break;
					case $wgContLang->getNsText( SMW_NS_CONCEPT ):
						$elemtype = NS_CATEGORY;
						break;
					default: // match all other namespaces
						$elemtype = NS_MAIN;
				}
				break;
			}
		}

		// check whether element of correct type was found (extracts data from factbox)
		///TODO: parser needed to do that
// 		if(SMWParseData::getSMWData($parser) instanceof SMWSemanticData) {
// 			$this_ns = SMWParseData::getSMWData($parser)->getSubject()->getNamespace();
// 			$error = null;
// 			switch ($elemtype) {
// 				case SMW_NS_PROPERTY: case NS_CATEGORY:
// 					if ($this_ns != $elemtype) {
// 						$error = wfMsgForContent('smw_nonright_importtype',$value, $wgContLang->getNsText($elemtype));
// 					}
// 					break;
// 				case NS_MAIN:
// 					if ( (SMW_NS_PROPERTY == $this_ns) || (NS_CATEGORY == $this_ns)) {
// 						$error = wfMsgForContent('smw_wrong_importtype',$value, $wgContLang->getNsText($this_ns));
// 					}
// 					break;
// 				case -1:
// 					$error = wfMsgForContent('smw_no_importelement',$value);
// 			}
//
// 			if (null != $error) {
// 				$this->addError($error);
// 				return;
// 			}
// 		}

		try {
			$this->m_dataitem = new SMWDIString( $this->m_namespace . ' ' . $this->m_section . ' ' . $this->m_uri );
		} catch ( SMWStringLengthException $e ) {
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			$this->addError( wfMsgForContent( 'smw_maxstring', '"' . $this->m_namespace . ' ' . $this->m_section . ' ' . $this->m_uri . '"' ) );
			$this->m_dataitem = new SMWDIString( 'ERROR' );
		}

		// check whether caption is set, otherwise assign link statement to caption
		if ( $this->m_caption === false ) {
			$this->m_caption = "[" . $this->m_uri . " " . $this->m_qname . "] (" . $this->m_name . ")";
		}
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 * @param $dataitem SMWDataItem
	 * @return boolean
	 */
	protected function loadDataItem( SMWDataItem $dataItem ) {
		if ( $dataItem->getDIType() == SMWDataItem::TYPE_STRING ) {
			$this->m_dataitem = $dataItem;
			$parts = explode( ' ', $dataItem->getString(), 3 );
			if ( count( $parts ) != 3 ) {
				smwfLoadExtensionMessages( 'SemanticMediaWiki' );
				$this->addError( wfMsgForContent( 'smw_parseerror' ) );
			} else {
				$this->m_namespace = $parts[0];
				$this->m_section = $parts[1];
				$this->m_uri = $parts[2];
				$this->m_qname = $this->m_namespace . ':' . $this->m_section;
				$this->m_caption = "[" . $this->m_uri . " " . $this->m_qname . "] (" . $this->m_name . ")";
			}
			return true;
		} else {
			return false;
		}
	}

	public function getShortWikiText( $linked = null ) {
		return $this->m_caption;
	}

	public function getShortHTMLText( $linker = null ) {
		return htmlspecialchars( $this->m_qname );
	}

	public function getLongWikiText( $linked = null ) {
		if ( !$this->isValid() ) {
			return $this->getErrorText();
		} else {
			return "[" . $this->m_uri . " " . $this->m_qname . "] (" . $this->m_name . ")";
		}
	}

	public function getLongHTMLText( $linker = null ) {
		if ( !$this->isValid() ) {
			return $this->getErrorText();
		} else {
			return htmlspecialchars( $this->m_qname );
		}
	}

	public function getWikiValue() {
		return $this->m_qname;
	}

	public function getNS() {
		return $this->m_uri;
	}

	public function getNSID() {
		return $this->m_namespace;
	}

	public function getLocalName() {
		return $this->m_section;
	}
}
