<?php

global $smwgIP;
include_once($smwgIP . '/includes/SMW_DataValue.php');

/**
 * This datavalue implements URI-Datavalues suitable for defining
 * URI-types of properties.
 * 
 * @author: Nikolas Iwan
 */
 

define('SMW_URI_MODE_URL',1);
define('SMW_URI_MODE_URI',2);
define('SMW_URI_MODE_ANNOURI',3);


/**
 * FIXME: correctly support caption ($this->m_caption).
 * FIXME: correctly create safe HTML and Wiki text.
 */
class SMWURIValue extends SMWDataValue {

	private $m_value = '';
	private $m_xsdvalue = '';
	private $m_infolinks = Array();
	private $m_mode = '';

	public function SMWURIValue($typeid) {
		SMWDataValue::__construct($typeid);
		switch ($mode) {
		/// TODO: support email type
		case '_uri':
			$this->m_mode = SMW_URI_MODE_URI; 
			break;
		case '_anu':
			$this->m_mode = SMW_URI_MODE_ANNOURI;
			break;
		case '_url': default:
			$this->m_mode = SMW_URI_MODE_URL; 
			break;
		}
	}
	
	protected function parseUserValue($value) {
		if ($this->m_caption === false) {
			$this->m_caption = $value;
		}
		if ($value!='') { //do not accept empty strings
			switch ($this->m_mode) {
				case SMW_URI_MODE_URL: 
					$this->m_value = $value;
					break;
				case SMW_URI_MODE_URI: case SMW_URI_MODE_ANNOURI:
					$uri_blacklist = explode("\n",wfMsgForContent('smw_uri_blacklist'));
					foreach ($uri_blacklist as $uri) {
						if (' ' == $uri[0]) $uri = mb_substr($uri,1); //tolerate beautification space
						if ($uri == mb_substr($value,0,mb_strlen($uri))) { //disallowed URI!
							$this->addError(wfMsgForContent('smw_baduri', $uri));
							return true;
						}
					}
					$this->m_value = $value;
					break;
			}
			$this->m_value = str_replace(array('&','<',' '),array('&amp;','&lt;','_'),$value); // TODO: spaces are just not allowed and should lead to an error
		} else {
			$this->addError(wfMsgForContent('smw_emptystring'));
		}
		return true;

	}

	protected function parseXSDValue($value, $unit) {
		$this->setUserValue($value);
	}

	public function setOutputFormat($formatstring){
		//TODO
	}

	public function getShortWikiText($linked = NULL) {
		wfDebug("\r\n getShortWikiText:  ".$this->m_caption);
		return $this->m_caption; // TODO: support linking with caption as alternative text, depending on type of DV
	}

	public function getShortHTMLText($linker = NULL) {
		return htmlspecialchars($this->getShortWikiText($linker)); /// TODO: support linking
	}

	public function getLongWikiText($linked = NULL) {
		if ($this->isValid()){
			return $this->getErrorText();
		} else {
			return $this->m_value;
		}
	}

	public function getLongHTMLText($linker = NULL) {
		if ($this->isValid()){
			return $this->getErrorText();
		} else {
			return '<span class="external free">' . htmlspecialchars($this->m_value) . '</span>'; /// TODO support linking
		}
	}

	public function getXSDValue() {
		return $this->getShortWikiText(false); ///FIXME
	}

	public function getWikiValue(){
		return $this->getShortWikiText(false); /// FIXME (wikivalue must not be influenced by the caption)
	}
	
	public function getNumericValue() {
		return NULL;
	}

	public function getUnit() {
		return ''; // empty unit
	}

	public function getInfolinks() {
		return $this->m_infolinks;
	}

	public function getHash() {
		return $this->getShortWikiText(false);
	}

	public function isNumeric() {
		return false;
	}
}

