<?php

/**
 * This datavalue implements URL/URI/ANNURI/EMAIL-Datavalues suitable for defining
 * the respective types of properties.
 *
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 * @note AUTOLOADED
 */

define('SMW_URI_MODE_EMAIL',1);
define('SMW_URI_MODE_URI',3);
define('SMW_URI_MODE_ANNOURI',4);

/**
 * FIXME: correctly create safe HTML and Wiki text.
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
		if ($value!='') { //do not accept empty strings
			switch ($this->m_mode) {
				case SMW_URI_MODE_URI: case SMW_URI_MODE_ANNOURI:
					// check against blacklist
					$uri_blacklist = explode("\n",wfMsgForContent('smw_uri_blacklist'));
					foreach ($uri_blacklist as $uri) {
						if (' ' == $uri[0]) $uri = mb_substr($uri,1); //tolerate beautification space
						if ($uri == mb_substr($value,0,mb_strlen($uri))) { //disallowed URI!
							$this->addError(wfMsgForContent('smw_baduri', $uri));
							return true;
						}
					}
					// simple check for invalid characters: '?', ' ', '{', '}'
					$check1 = "@(\?|\}|\{| )+@";
					if (preg_match($check1, $value, $matches)) {
						$this->addError(wfMsgForContent('smw_baduri', $value));
						break;
					}
/// TODO: the remaining checks need improvement
// 					// validate last part of URI (after #) if provided 
// 					$uri_ex = explode('#',$value);
// 					$check2 = "@^[a-zA-Z0-9-_\%]+$@"; ///FIXME: why only ascii symbols?
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
					$this->m_value =$value;
					$this->m_url =$value;
					$this->m_uri =$value;
					break;
				case SMW_URI_MODE_EMAIL:
					$check = "#^([_a-z0-9-]+)((\.[_a-z0-9-]+)*)@([_a-z0-9-]+(\.[_a-z0-9-]+)*)\.([a-z]{2,3})$#";
					if (!preg_match($check, $value)) {
						///TODO: introduce error-message for "bad" email
						$this->addError(wfMsgForContent('smw_baduri', $value));
						break;
					}
					$this->m_value = $value;
					$this->m_url = 'mailto:' . $value;
					$this->m_uri = 'mailto:' . $value;
			}
		} else {
			$this->addError(wfMsgForContent('smw_emptystring'));
		}

		if ($this->m_caption === false) {
			$this->m_caption = $value;
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
		if ( ($linked === NULL) || ($linked === false) || ($this->m_url == '') ) {
			return $this->m_caption;
		} else {
			return '[' . $this->m_url . ' ' . $this->m_caption . ']';
		}
	}

	public function getShortHTMLText($linker = NULL) {
		if (($linker === NULL) || (!$this->isValid()) || ($this->m_url == '')) {
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
			return $linker->makeExternalLink($this->m_url, $this->m_caption);
		}
	}

	public function getXSDValue() {
		return $this->m_value;
	}

	public function getWikiValue(){
		return $this->m_value;
	}

	public function exportToRDF($QName, ExportRDF $exporter) {
		return "\t\t<$QName rdf:resource=\"$this->m_uri\" />\n";
	}

}

