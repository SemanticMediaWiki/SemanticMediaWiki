<?php

/**
 * This datavalue implements String-Datavalues suitable for defining
 * String-types of properties.
 * 
 * @author: Nikolas Iwan
 */
class SMWStringValue extends SMWDataValue {

	private $m_attribute = null;
	private $m_error = '';
	private $m_value = '';
	private $m_xsdvalue = '';
	private $m_infolinks = Array();

	/*********************************************************************/
	/* Set methods                                                       */
	/*********************************************************************/

	public function setUserValue($value) {
		if ($value!='') {
			$this->m_xsdvalue = smwfXMLContentEncode($value);
			// 255 below matches smw_attributes.value_xsd definition in smwfMakeSemanticTables()
			if (strlen($this->m_xsdvalue) > 255) {
				$this->m_error = wfMsgForContent('smw_maxstring', $this->m_xsdvalue);
				$this->m_value = $this->m_xsdvalue;			
			} else {
				$this->m_value = $this->m_xsdvalue;
				$this->m_infolinks[] = SMWInfolink::newAttributeSearchLink('+', $this->m_attribute, $this->m_value);
			}
		} else {
			$this->m_error = wfMsgForContent('smw_emptystring');
		}
		return true;
	}

	public function setXSDValue($value, $unit) {
		$this->setUserValue($value); // no units, XML compatible syntax
	}

	public function setAttribute($attribute) {
		$this->m_attribute = $attribute;
	}

	public function setOutputFormat($formatstring){
		//ToDo
	}

	/*********************************************************************/
	/* Get methods                                                       */
	/*********************************************************************/

	public function getShortWikiText($linked = NULL) {
		//TODO: Support linking
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
		return $this->getLongWikiText($linker);
	}

	public function getXSDValue() {
		return $this->getShortWikiText();
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
		return 'string';
	}

	public function getInfolinks() {
		return $this->m_infolinks;
	}

	public function getHash() {
		return $this->getLongWikiText(false) . $this->m_xsdvalue ;
	}

	public function isValid() {
		return (($this->m_error == '') && ($this->m_value !== '') );
	}

	public function isNumeric() {
		return false;
	}


}

?>