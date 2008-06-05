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

	protected function parseUserValue($value) {
		$value = ltrim(rtrim($value,' ]'),' ['); // support inputs like " [[Test]] "
		if ($value != '') {
			$this->m_value = $value;
			$this->m_title = NULL;
			$this->m_dbkeyform = NULL;
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

	protected function parseXSDValue($value, $unit) { // (ignore "unit")
		// This method in its current for is not really useful for init, since the XSD value is just
		// the (dbkey) title string without the namespace.
		/// FIXME: change this to properly use a prefixed title string, in case someone wants to use this
		$this->m_stubdata = array($value,$this->m_namespace,false);
	}

	protected function unstub() {
		if (is_array($this->m_stubdata)) {
			global $wgContLang;
			$this->m_dbkeyform = $this->m_stubdata[0];
			$this->m_namespace = $this->m_stubdata[1];
			$this->m_textform = str_replace('_', ' ', $this->m_dbkeyform);
			$nstext = $wgContLang->getNSText($this->m_namespace);
			if ($nstext !== '') {
				$nstext .= ':';
			}
			$this->m_prefixedtext = $nstext . $this->m_textform;
			$this->m_caption = $this->m_prefixedtext;
			$this->m_value = $this->m_prefixedtext;
			$this->m_title = Title::makeTitle($this->m_namespace, $this->m_dbkeyform);
			if ($this->m_stubdata[2] === NULL) {
				$this->m_id = 0;
				$linkCache =& LinkCache::singleton();
				$linkCache->addBadLinkObj($this->m_title); // prefill link cache, save lookups
			} elseif ($this->m_stubdata[2] === false) {
				$this->m_id = false;
			} else {
				$this->m_id = $this->m_stubdata[2];
				$linkCache =& LinkCache::singleton();
				$linkCache->addGoodLinkObj($this->m_id, $this->m_title); // prefill link cache, save lookups
			}
			$this->m_stubdata = false;
		}
	}

	public function getShortWikiText($linked = NULL) {
		$this->unstub();
		if ( ($linked === NULL) || ($linked === false) || (!$this->isValid()) || ($this->m_caption == '') ) {
			return $this->m_caption;
		} else {
			return '[[:' . str_replace("'", '&#x0027;', $this->m_prefixedtext) . '|' . $this->m_caption . ']]';
		}
	}

	public function getShortHTMLText($linker = NULL) {
		$this->unstub();
		if ( ($linker === NULL) || (!$this->isValid()) || ($this->m_caption == '') ) {
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
		$this->unstub();
		if (!$this->isValid()) {
			return $this->getErrorText();
		}
		if ( ($linked === NULL) || ($linked === false) ) {
			return $this->m_prefixedtext;
		} elseif ($this->m_namespace == NS_IMAGE) {
			 return '[[' . str_replace("'", '&#x0027;', $this->m_prefixedtext) . '|' . $this->m_textform . '|frameless|border|text-top]]';
		} else {
			return '[[:' . str_replace("'", '&#x0027;', $this->m_prefixedtext) . '|' . $this->m_textform . ']]';
		}
	}

	public function getLongHTMLText($linker = NULL) {
		$this->unstub();
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
		$this->unstub();
		return $this->m_dbkeyform;
	}

	public function getWikiValue() {
		$this->unstub();
		if ($this->m_namespace == NS_CATEGORY) {
			// escape to enable use in links; todo: not generally required/suitable :-/
			return ':' . $this->m_prefixedtext;
		} else {
			return $this->m_prefixedtext;
		}
	}

	public function getHash() {
		$this->unstub();
		if ($this->isValid()) { // assume that XSD value + unit say all
			return $this->m_prefixedtext;
		} else {
			return implode("\t", $this->m_errors);
		}
	}

	protected function getServiceLinkParams() {
		$this->unstub();
		// Create links to mapping services based on a wiki-editable message. The parameters 
		// available to the message are:
		// $1: urlencoded article name (no namespace)
		return array(rawurlencode(str_replace('_',' ',$this->m_dbkeyform)));
	}

	public function getExportData() {
		$this->unstub();
		if (!$this->isValid()) return NULL;
		switch ($this->getNamespace()) {
			case NS_MEDIA: // special handling for linking media files directly
				$file = wfFindFile( $this->getTitle() );
				if ($file) {
					//$name = $file->getFullURL();
					/// TODO: the following just emulates getFullURL() which is not yet available in MW1.11:
					$uri = $file->getUrl();
					if( substr( $uri, 0, 1 ) == '/' ) {
						global $wgServer;
						$uri = $wgServer . $uri;
					}
					return new SMWExpData(new SMWExpResource($uri, $this));
				} else { // Medialink to non-existing file :-/
					return NULL;
				}
			break;
			default: // some true wiki page
				return new SMWExpData(SMWExporter::getResourceElement($this));
			break;
		}
	}

///// special interface for wiki page values

	/**
	 * Return according Title object or NULL if no valid value was set.
	 */
	public function getTitle() {
		$this->unstub();
		if ($this->m_title === NULL){
			if ($this->m_dbkeyform != '') {
				$this->m_title = Title::makeTitle($this->m_namespace, $this->m_dbkeyform);
			} elseif ($this->m_value != ''){
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
		$this->unstub();
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
		$this->unstub();
		if (!$this->isValid()) {
			return false;
		}
		return $this->m_namespace;
	}

	/**
	 * Get DBKey for this value.
	 */
	public function getDBkey() {
		$this->unstub();
		return $this->m_dbkeyform;
	}

	/**
	 * Set all basic values for this datavalue to the extent these are
	 * available. Simplifies and speeds up creation from stored data.
	 */
	public function setValues($dbkey, $namespace, $id = false) {
		$this->setXSDValue($dbkey,''); // just used to trigger standard parent class methods!
		/// TODO: rethink our standard set interfaces for datavalues to make wikipage fit better with the rest
		$this->m_stubdata = array($dbkey, $namespace, $id);
		$this->unstub();
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
