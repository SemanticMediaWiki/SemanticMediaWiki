<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue implements datavalues used by special property '_IMPO' used for assigning
 * imported vocabulary to some page of the wiki.
 * It looks up a MediaWiki message to find out whether a user-supplied vocabulary name
 * can be imported in the wiki, and whether its declaration is correct (to the extend
 * that this can be checked).
 *
 * @author Fabian Howahl
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWImportValue extends SMWDataValue {

	protected $m_value = ''; // stores string provided by user which is used to look up data on Mediawiki:*-Page
	protected $m_uri = ''; // URI of namespace (without local name)
	protected $m_namespace = ''; // namespace id (e.g. "foaf")
	protected $m_section = ''; // stores local name (e.g. "knows")
	protected $m_name = ''; // stores wiki name of the vocab (e.g. "Friend of a Friend")
	protected $m_wikilink= ''; // store string to be displayed in factbox

	protected function parseUserValue($value) {
		global $wgContLang;

		$this->m_value = $value;
		list($onto_ns,$onto_section) = explode(':',$value,2);

		$msglines = preg_split("([\n][\s]?)",wfMsgForContent("smw_import_$onto_ns")); // get the definition for "$namespace:$section"

		if ( count($msglines) < 2 ) { //error: no elements for this namespace
			$this->addError(wfMsgForContent('smw_unknown_importns',$onto_ns));
			return true;
		}

		//browse list in smw_import_* for section
		list($onto_uri,$onto_name) = explode('|',array_shift($msglines),2);

		if ( ' ' == $onto_uri[0]) $onto_uri = mb_substr($onto_uri,1); // tolerate initial space

		$this->m_uri = $onto_uri;
		$this->m_namespace = $onto_ns;
		$this->m_section = $onto_section;
		$this->m_name = $onto_name;

		$elemtype = -1;
		foreach ( $msglines as $msgline ) {
			list($secname,$typestring) = explode('|',$msgline,2);
			if ( $secname === $onto_section ) {
				list($namespace, ) = explode(':',$typestring,2);
				// check whether type matches
				switch ($namespace) {
					case $wgContLang->getNsText(SMW_NS_TYPE):
						$elemtype = SMW_NS_PROPERTY;
						break;
					case $wgContLang->getNsText(SMW_NS_PROPERTY):
						$elemtype = SMW_NS_PROPERTY;
						break;
					case $wgContLang->getNsText(NS_CATEGORY):
						$elemtype = NS_CATEGORY;
						break;
					case $wgContLang->getNsText(SMW_NS_CONCEPT):
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
// 			$error = NULL;
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
// 			if (NULL != $error) {
// 				$this->addError($error);
// 				return true;
// 			}
// 		}

		//create String to be returned by getShort/LongWikiText
		$this->m_wikilink = "[".$this->m_uri." ".$this->m_value."] (".$this->m_name.")";

		//check whether caption is set, otherwise assign link statement to caption
		if($this->m_caption===false){
			$this->m_caption = $this->m_wikilink;
		}

		return true;
	}

	protected function parseDBkeys($args) {
		$parts = explode(' ', $args[0], 3);
		if (array_key_exists(0,$parts)) {
			$this->m_namespace = $parts[0];
		}
		if (array_key_exists(1,$parts)) {
			$this->m_section = $parts[1];
		}
		if (array_key_exists(2,$parts)) {
			$this->m_uri = $parts[2];
		}
		$this->m_value = $this->m_namespace . ':' . $this->m_section;
		$this->m_caption = $this->m_value; // not as pretty as on input, don't care
		$this->m_wikilink = $this->m_value; // not as pretty as on input, don't care
	}

	public function getShortWikiText($linked = NULL) {
		$this->unstub();
		return $this->m_caption;
	}

	public function getShortHTMLText($linker = NULL) {
		$this->unstub();
		return htmlspecialchars($this->m_value);
	}

	public function getLongWikiText($linked = NULL) {
		if (!$this->isValid()) {
			return $this->getErrorText();
		} else {
			return $this->m_wikilink;
		}
	}

	public function getLongHTMLText($linker = NULL) {
		if (!$this->isValid()) {
			return $this->getErrorText();
		} else {
			return htmlspecialchars($this->m_value);
		}
	}

	public function getDBkeys() {
		$this->unstub();
		return array($this->m_namespace . ' ' . $this->m_section . ' ' . $this->m_uri);
	}

	public function getWikiValue(){
		$this->unstub();
		return $this->m_value;
	}

	public function getNS(){
		$this->unstub();
		return $this->m_uri;
	}

	public function getNSID(){
		$this->unstub();
		return $this->m_namespace;
	}

	public function getLocalName(){
		$this->unstub();
		return $this->m_section;
	}
}
