<?php

/**
 * This datavalue implements special processing suitable for defining
 * wikipages as values of properties. This value container currently
 * behaves somewhat special in that its xsdvalue is not contained all
 * relevant information (it just gives the DB-Key, not the namespace).
 * TODO: This should change, but is not really critical now.
 *
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 * @note AUTOLOADED
 */
class SMWWikiPageValue extends SMWDataValue {

	private $m_value = '';
	private $m_textform = '';
	private $m_dbkeyform = '';
	private $m_prefixedtext = '';
	private $m_namespace = NS_MAIN;
	private $m_id; // false if unset
	private $m_title = NULL;

	public function parseUserValue($value) {
		$value = ltrim(rtrim($value,' ]'),' ['); // support inputs like " [[Test]] "
		if ($value != '') {
			$this->m_value = $value;
			$this->m_title = NULL;
			if ($this->getTitle() !== NULL) {
				$this->m_textform = $this->m_title->getText();
				$this->m_dbkeyform = $this->m_title->getDBkey();
				$this->m_prefixedtext = $this->m_title->getPrefixedText();
				$this->m_namespace = $this->m_title->getNamespace();
				$this->m_id = false; // unset id
				if ($this->m_caption === false) {
					$this->m_caption = $value;
				}
			} else {
				$this->addError(wfMsgForContent('smw_notitle', $value));
				# TODO: Escape the text so users can see any punctuation problems (bug 11666).
			}
		} else {
			$this->addError(wfMsgForContent('smw_notitle', $value));
		}
		if ($this->m_caption === false) {
			$this->m_caption = '';
		}
	}

	public function parseXSDValue($value, $unit) { // (ignore "unit")
		global $wgContLang;
		$this->m_dbkeyform = $value;
		$this->m_textform = str_replace('_', ' ', $value);
		$nstext = $wgContLang->getNSText($this->m_namespace);
		if ($nstext !== '') {
			$nstext .= ':';
		}
		$this->m_prefixedtext = $nstext . $this->m_textform;
		$this->m_caption = $this->m_prefixedtext;
		$this->m_value = $this->m_prefixedtext;
		$this->m_id = false; // unset id
		$this->m_title = NULL; // unset title
	}

	public function getShortWikiText($linked = NULL) {
		if ( ($linked === NULL) || ($linked === false) || (!$this->isValid()) ) {
			return $this->m_caption;
		} else {
			return '[[:' . str_replace("'", '&#x0027;', $this->m_prefixedtext) . '|' . $this->m_caption . ']]';
		}
	}

	public function getShortHTMLText($linker = NULL) {
		if (($linker === NULL) || (!$this->isValid())) {
			return htmlspecialchars($this->m_caption);
		} else {
			if ($this->getArticleID() !== 0) { // aritcle ID might be cached already, save DB calls
				return $linker->makeKnownLinkObj($this->getTitle(), $this->m_caption);
			} else {
				return $linker->makeBrokenLinkObj($this->getTitle(), $this->m_caption);
			}
		}
	}

	public function getLongWikiText($linked = NULL) {
		if (!$this->isValid()) {
			return $this->getErrorText();
		}
		if ( ($linked === NULL) || ($linked === false) ) {
			return $this->m_prefixedtext;
		} else {
			return '[[:' . str_replace("'", '&#x0027;', $this->m_prefixedtext) . '|' . $this->m_textform . ']]';
		}
	}

	public function getLongHTMLText($linker = NULL) {
		if (!$this->isValid()) {
			return $this->getErrorText();
		}
		if ($linker === NULL) {
			return htmlspecialchars($this->m_prefixedtext);
		} else {
			if ($this->getNamespace() == NS_MEDIA) {
				return $linker->makeMediaLinkObj($this->getTitle(), $this->m_textform);
			} elseif ($this->getArticleID() !== 0) { // aritcle ID might be cached already, save DB calls
				return $linker->makeKnownLinkObj($this->getTitle(), $this->m_textform);
			} else {
				return $linker->makeBrokenLinkObj($this->getTitle(), $this->m_textform);
			}
		}
	}

	public function getXSDValue() {
		return $this->m_dbkeyform;
	}

	public function getWikiValue() {
		if ($this->m_namespace == NS_CATEGORY) {
			// escape to enable use in links; todo: not generally required/suitable :-/
			return ':' . $this->m_prefixedtext;
		} else {
			return $this->m_prefixedtext;
		}
	}

	public function getHash() {
		if ($this->isValid()) { // assume that XSD value + unit say all
			return $this->m_prefixedtext;
		} else {
			return implode("\t", $this->m_errors);
		}
	}

	protected function getServiceLinkParams() {
		// Create links to mapping services based on a wiki-editable message. The parameters 
		// available to the message are:
		// $1: urlencoded article name (no namespace)
		return array(rawurlencode(str_replace('_',' ',$this->m_dbkeyform)));
	}

	/**
	 * Creates the export line for the RDF export
	 *
	 * @param string $QName The element name of this datavalue
	 * @param ExportRDF $exporter the exporter calling this function
	 * @return the line to be exported
	 */
	public function exportToRDF($QName, ExportRDF $exporter) {
		if (!$this->isValid()) return '';

		switch ($this->getNamespace()) {
			case NS_MEDIA: // special handling for linking media files directly
				$file = wfFindFile( $this->getTitle() );
				if ($file) {
					//$obj = $file->getFullURL();
					/// TODO: the following just emulates getFullURL() which is not yet available in MW1.11:
					$obj = $file->getUrl();
					if( substr( $obj, 0, 1 ) == '/' ) {
						global $wgServer;
						$obj = $wgServer . $obj;
					}
				} else { // Medialink to non-existing file :-/
					return "\t\t <!-- $QName points to the media object " . $this->getXSDValue() . " but no such file was uploaded. -->\n";
				}
			break;
			case SMW_NS_PROPERTY: case NS_CATEGORY: // export would be OWL Full, check if this is desired
				if (!$exporter->owlfull) {
					return '';
				} // < very bad coding: we omit the break deliberately :-o
			default:
				$obj = $exporter->getURI( $this->getTitle() );
			break;
		}

		return "\t\t<$QName rdf:resource=\"$obj\"/>\n";
	}

///// special interface for wiki page values

	/**
	 * Return according Title object or NULL if no valid value was set.
	 */
	public function getTitle() {
		if ($this->m_title === NULL){
			if ($this->m_value != ''){
				$this->m_title = Title::newFromText($this->m_value);
			} else {
				return NULL; //not possible to create title from empty string
			}
		}
		return $this->m_title;
	}

	/**
	 * Get MediaWiki's ID for this value, if any.
	 */
	public function getArticleID() {
		if ($this->m_id === false) {
			if ($this->getTitle() !== NULL) {
				$this->m_id = $this->m_title->getArticleID();
			} else {
				$this->m_id = 0;
			}
		}
		return $this->m_id;
	}

	/**
	 * Get namespace constant for this value, if any. Otherwise
	 * return FALSE.
	 */
	public function getNamespace() {
		if (!$this->isValid()) {
			return false;
		}
		return $this->m_namespace;
	}

	/**
	 * Get DBKey for this value.
	 */
	public function getDBKey() {
		return $this->m_dbkeyform;
	}

	/**
	 * Set all basic values for this datavalue to the extent these are
	 * available. Simplifies and speeds up creation from stored data.
	 */
	public function setValues($dbkey, $namespace, $id = false) {
		$this->m_namespace = $namespace;
		$this->setXSDValue($dbkey);
		$this->m_id = $id ? $id : false;
	}

///// Legacy methods for compatibility

	/**
	 *  @DEPRECATED
	 */
	public function getPrefixedText(){
		trigger_error("The function SMWWikiPageValue::getPrefixedText) is deprecated.", E_USER_NOTICE);
		return $this->getLongWikiText(false);
	}

}

?>
