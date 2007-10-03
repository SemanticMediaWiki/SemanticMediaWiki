<?php
/**
 * This file contains a typehandler for accepting URIs (and URLs) of various
 * kinds.
 * 
 * @DEPRECATED
 * This file is klept only for temporary reference. The current implementation 
 * is in SMW_DV_URI.php.
 *
 * @author Markus Krötzsch
 */

define('SMW_URI_MODE_EMAIL',1);
define('SMW_URI_MODE_URL',2);
define('SMW_URI_MODE_URI',3);
define('SMW_URI_MODE_ANNOURI',4);

/**
 * This typehandler accepts URI strings. It still is different from Sting:
 * results are usually auto-linked (also in the factbox), and it can be used
 * to create ObjectProperties (i.e. like a Relation to an external URI). For
 * this purpose, it uses a blacklist to disallow certain URIs (belonging to
 * the ontology languages we already use). So you cannot enforce OWL Full by
 * giving a stupid URI.
 */
class SMWURITypeHandler implements SMWTypeHandler {

	private $mMode;

	/**
	 * Constructor.
	 * 
	 * @param $mode determines the exact behaviour, possible values are 
	 * SMW_URI_MODE_EMAIL, SMW_URI_MODE_URL, SMW_URI_MODE_URI, SMW_URI_MODE_ANNOURI
	 */
	function SMWURITypeHandler($mode) {
		switch ($mode) {
		case 'email': 
			$this->mMode = SMW_URI_MODE_EMAIL; 
			break;
		default: case 'url':
			$this->mMode = SMW_URI_MODE_URL; 
			break;
		case 'uri':
			$this->mMode = SMW_URI_MODE_URI; 
			break;
		case 'annouri':
			$this->mMode = SMW_URI_MODE_ANNOURI;
			break;
		}
	}

	function getID() {
		switch ($this->mMode) {
			case SMW_URI_MODE_EMAIL: return '_ema';
			case SMW_URI_MODE_URL: return '_url';
			case SMW_URI_MODE_URI: return '_uri';
			case SMW_URI_MODE_ANNOURI: return '_anu';
		}
	}

	function getXSDType() {
		switch ($this->mMode) {
			case SMW_URI_MODE_EMAIL:
				// Bug 8956 if this had mailto: in front in ExportRDF then it could be #anyURI
				return 'http://www.w3.org/2001/XMLSchema#string';
			case SMW_URI_MODE_URL:
				return 'http://www.w3.org/2001/XMLSchema#anyURI';
			case SMW_URI_MODE_URI: case SMW_URI_MODE_ANNOURI:
				return ''; // no type -> objectProperty
		}
	}

	function getUnits() { //no units for strings
		return array('STDUNIT'=>false, 'ALLUNITS'=>array());
	}

	function processValue($value,&$datavalue) {
		if ($value!='') { //do not accept empty strings
			switch ($this->mMode) {
				case SMW_URI_MODE_EMAIL: 
					$user = "[mailto:$value $value]";
					break;
				case SMW_URI_MODE_URL: 
					$user = $value;
					break;
				case SMW_URI_MODE_URI: case SMW_URI_MODE_ANNOURI:
					$uri_blacklist = explode("\n",wfMsgForContent('smw_uri_blacklist'));
					foreach ($uri_blacklist as $uri) {
						if (' ' == $uri[0]) $uri = mb_substr($uri,1); //tolerate beautification space
						if ($uri == mb_substr($value,0,mb_strlen($uri))) { //disallowed URI!
							$datavalue->setError(wfMsgForContent('smw_baduri', $uri));
							return true;
						}
					}
					$user = $value;
					break;
			}
			$value = str_replace(array('&','<',' '),array('&amp;','&lt;','_'),$value); // TODO: spaces are just not allowed and should lead to an error
			$datavalue->setProcessedValues($user, $value);
			$datavalue->setPrintoutString($user);
			$datavalue->addQuicksearchLink();
			$datavalue->addServiceLinks(urlencode($value)); //even URLs can be parameters to other URLs, e.g. for Wayback Machine
		} else {
			$datavalue->setError(wfMsgForContent('smw_emptystring'));
		}
		return true;
	}

	function processXSDValue($value,$unit,&$datavalue) {
		return $this->processValue($value,$datavalue);
	}

	function isNumeric() {
		return false;
	}
}

