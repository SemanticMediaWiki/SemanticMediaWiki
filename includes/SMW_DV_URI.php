<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

define('SMW_URI_MODE_EMAIL',1);
define('SMW_URI_MODE_URI',3);
define('SMW_URI_MODE_ANNOURI',4);

/**
 * This datavalue implements URL/URI/ANNURI/EMAIL-Datavalues suitable for defining
 * the respective types of properties.
 *
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 * @bug Correctly create safe HTML and Wiki text.
 */
class SMWURIValue extends SMWDataValue {

	private $m_value = '';
	private $m_url = '';
	private $m_uri = '';
	private $m_mode = '';

	public function SMWURIValue($typeid) {
		SMWDataValue::__construct($typeid);
		switch ($typeid) {
			case '_ema':
				$this->m_mode = SMW_URI_MODE_EMAIL;
				break;
			case '_anu':
				$this->m_mode = SMW_URI_MODE_ANNOURI;
				break;
			case '_uri': case '_url': default:
				$this->m_mode = SMW_URI_MODE_URI;
				break;
		}	
	}

	protected function parseUserValue($value) {
		wfLoadExtensionMessages('SemanticMediaWiki');
		$value = trim($value);
		if ($value!='') { //do not accept empty strings
			$this->m_value = $value;
			switch ($this->m_mode) {
				case SMW_URI_MODE_URI: case SMW_URI_MODE_ANNOURI:
					$parts = explode(':', $value, 2); // try to split "schema:rest"
					if (count($parts) == 1) { // take "http" as default
						$value = 'http://' . $value;
						$parts[1] = $parts[0];
						$parts[0] = 'http';
					} elseif ( (count($parts) < 1) || ($parts[0] == '') || ($parts[1] == '') || (preg_match('/[^a-zA-Z]/u',$parts[0]) )) { 
						$this->addError(wfMsgForContent('smw_baduri', $value));
						return true;
					}

					// check against blacklist
					$uri_blacklist = explode("\n",wfMsgForContent('smw_uri_blacklist'));
					foreach ($uri_blacklist as $uri) {
						$uri = trim($uri);
						if ($uri == mb_substr($value,0,mb_strlen($uri))) { //disallowed URI!
							$this->addError(wfMsgForContent('smw_baduri', $uri));
							return true;
						}
					}
					// simple check for invalid characters: ' ', '{', '}'
// 					$check1 = "@(\}|\{| )+@u";
// 					if (preg_match($check1, $value, $matches)) {
// 						$this->addError(wfMsgForContent('smw_baduri', $value));
// 						break;
// 					}
/// TODO: the remaining checks need improvement
// 					// validate last part of URI (after #) if provided 
// 					$uri_ex = explode('#',$value);
// 					$check2 = "@^[a-zA-Z0-9-_\%]+$@u"; ///FIXME: why only ascii symbols?
// 					if(sizeof($uri_ex)>2 ){ // URI should only contain at most one '#'
// 						$this->addError(wfMsgForContent('smw_baduri', $value) . 'Debug3');
// 						break;
// 					} elseif ( (sizeof($uri_ex) == 2) && !(preg_match($check2, $uri_ex[1])) ) {
// 						$this->addError(wfMsgForContent('smw_baduri', $value) . 'Debug4');
// 						break;
// 					}
// 					// validate protocol + domain part of URI
// // 					$check3 = "@^([a-zA-Z]{0,6}:)[a-zA-Z0-9\.\/%]+$@";  //simple regexp for protocol+domain part of URI
// 					$check3 = "@^([a-zA-Z]:)[a-zA-Z0-9\.\/%]+$@";  //simple regexp for protocol+domain part of URI
// 					/// FIXME: why {0,6}?
// 					if (!preg_match($check3, $uri_ex[0],$matches)){
// 						$this->addError(wfMsgForContent('smw_baduri', $value) . 'Debug5');
// 						break;
// 					}

					// encode most characters, but leave special symbols as given by user:
					$this->m_uri = str_replace(array('%3A','%2F','%23','%40','%3F','%3D','%26','%25'), array(':','/','#','@','?','=','&','%'),rawurlencode($value));
					/// NOTE: we do not support raw [ (%5D) and ] (%5E), although they are needed for ldap:// (but rarely in a wiki)
					/// NOTE: we do not check the validity of the use of the raw symbols -- does RFC 3986 as such care?
					/// NOTE: "+" gets encoded, as it is interpreted as space by most browsers when part of a URL;
					///       this prevents tel: from working directly, but we should have a datatype for this anyway.
					global $wgUrlProtocols;
					foreach ($wgUrlProtocols as $prot) { // only set URL if wiki-enabled protocol
						if ( ($prot == $parts[0] . ':') || ($prot == $parts[0] . '://') ) {
							$this->m_url = $this->m_uri;
							break;
						}
					}
					break;
				case SMW_URI_MODE_EMAIL:
					$check = "#^([_a-zA-Z0-9-]+)((\.[_a-zA-Z0-9-]+)*)@([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*)\.([a-zA-Z]{2,6})$#u";
					if (!preg_match($check, $value)) {
						///TODO: introduce error-message for "bad" email
						$this->addError(wfMsgForContent('smw_baduri', $value));
						break;
					}
					$this->m_url = 'mailto:' . rawurlencode($value);
					$this->m_uri = $this->m_url;
			}
		} else {
			$this->addError(wfMsgForContent('smw_emptystring'));
		}

		if ($this->m_caption === false) {
			$this->m_caption = $this->m_value;
		}
		return true;
	}

	protected function parseXSDValue($value, $unit) {
		$this->m_value = $value;
		$this->m_caption = $value;
		if ($this->m_mode == SMW_URI_MODE_EMAIL) {
			$this->m_url = 'mailto:' . $value;
		} else {
			$this->m_url = $value;
		}
		$this->m_uri = $this->m_url;
	}

	public function getShortWikiText($linked = NULL) {
		if ( ($linked === NULL) || ($linked === false) || ($this->m_url == '') || ($this->m_caption == '') ) {
			return $this->m_caption;
		} else {
			return '[' . $this->m_url . ' ' . $this->m_caption . ']';
		}
	}

	public function getShortHTMLText($linker = NULL) {
		if (($linker === NULL) || (!$this->isValid()) || ($this->m_url == '') || ($this->m_caption == '')) {
			return $this->m_caption;
		} else {
			return $linker->makeExternalLink($this->m_url, $this->m_caption);
		}
	}

	public function getLongWikiText($linked = NULL) {
		if (!$this->isValid()) {
			return $this->getErrorText();
		}
		if ( ($linked === NULL) || ($linked === false) || ($this->m_url == '') ) {
			return $this->m_value;
		} else {
			return '[' . $this->m_url . ' ' . $this->m_value . ']';
		}
	}

	public function getLongHTMLText($linker = NULL) {
		if (!$this->isValid()) {
			return $this->getErrorText();
		}
		if (($linker === NULL) || ($this->m_url == '') ) {
			return htmlspecialchars($this->m_value);
		} else {
			return $linker->makeExternalLink($this->m_url, $this->m_value);
		}
	}

	public function getXSDValue() {
		return $this->m_value;
	}

	public function getWikiValue(){
		return $this->m_value;
	}

	protected function getServiceLinkParams() {
		// Create links to mapping services based on a wiki-editable message. The parameters 
		// available to the message are:
		// $1: urlencoded version of URI/URL value (includes mailto: for emails)
		return array(rawurlencode($this->m_uri));
	}

	public function getExportData() {
		if ($this->isValid()) {
			$res = new SMWExpResource(str_replace('&','&amp;', $this->m_uri), $this);
			return new SMWExpData($res);
		} else {
			return NULL;
		}
	}

}

