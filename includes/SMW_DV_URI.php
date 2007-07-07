<?php

/**
 * This datavalue implements URI-Datavalues suitable for defining
 * URI-types of properties.
 * 
 * @author: Nikolas Iwan
 */
 

define('SMW_URI_MODE_URL',1);
define('SMW_URI_MODE_URI',2);
define('SMW_URI_MODE_ANNOURI',3);
 
class SMWURIValue extends SMWDataValue {

	private $m_attribute = null;
	private $m_error = '';
	private $m_value = '';
	private $m_xsdvalue = '';
	private $m_infolinks = Array();
	private $m_mode = '';

	/*********************************************************************/
	/* Set methods                                                       */
	/*********************************************************************/
	
	function SMWURIValue($mode) {
		switch ($mode) {
		default: case 'url':
			$this->m_mode = SMW_URI_MODE_URL; 
			break;
		case 'uri':
			$this->m_mode = SMW_URI_MODE_URI; 
			break;
		case 'annouri':
			$this->m_mode = SMW_URI_MODE_ANNOURI;
			break;
		}
	}
	
	public function setUserValue($value) {
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
							$this->m_error = wfMsgForContent('smw_baduri', $uri);
							return true;
						}
					}
					$this->m_value = $value;
					break;
			}
			$this->m_value = str_replace(array('&','<',' '),array('&amp;','&lt;','_'),$value); // TODO: spaces are just not allowed and should lead to an error
			$this->m_infolinks[] = SMWInfolink::newAttributeSearchLink('+', $this->m_attribute, $this->m_value);		
		} else {
			$this->m_error = (wfMsgForContent('smw_emptystring'));
		}
		
		return true;

	}

	public function setXSDValue($value, $unit) {
		$this-> setUserValue($value);
	}

	public function setAttribute($attribute) { 
		$this->m_attribute = $attribute;
	}

	 public function setOutputFormat($formatstring){
	 	//TODO
	 }
	/*********************************************************************/
	/* Get methods                                                       */
	/*********************************************************************/

	public function getShortWikiText($linked = NULL) {		
		//TODO: Support linking
		wfDebug("\r\n getShortWikiText:  ".$this->m_value);
		return $this->m_value;
	}

	public function getShortHTMLText($linker = NULL) {
		return $this->getShortWikiText($linker);
	}

	public function getLongWikiText($linked = NULL) {				
			if (! ($this->m_error === '')){
				return ('<span class="smwwarning">' . $this->m_error  . '</span>');			
			}else {
				return $this->getShortWikiText($linked);	
			}		
	}

	public function getLongHTMLText($linker = NULL) {
		return '<span class="external free">'.$this->m_value.'</span>';
	}

	public function getXSDValue() {
		return $this->getShortWikiText(false);
	}

	public function getWikiValue(){
		return $this->getShortWikiText(false);
	}
	
	public function getNumericValue() {
		return NULL;
	}

	public function getUnit() {
		return array('STDUNIT'=>false, 'ALLUNITS'=>array());
	}

	public function getError() {
		return $this->m_error;
	}

	public function getTypeID(){
		switch ($this->m_mode) {
			case SMW_URI_MODE_URL: return 'url';
			case SMW_URI_MODE_URI: return 'uri';
			case SMW_URI_MODE_ANNOURI: return 'annouri';
		}		
		return 'uri';
	}

	public function getInfolinks() {
		return $this->m_infolinks;
	}

	public function getHash() {
		return $this->getShortWikiText(false);
	}

	public function isValid() {
		return (($this->m_error == '') && ($this->m_value !== '') );
	}

	public function isNumeric() {
		return false;
	}
}

?>