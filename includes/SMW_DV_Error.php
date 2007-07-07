<?php

/**
 * This datavalue implements Error-Datavalues.
 * 
 * @author: Nikolas Iwan
 */
class SMWErrorValue extends SMWDataValue {

	private $m_error;
	private $m_value;
	private $m_infolinks = Array();
	
	public function SMWErrorValue($errormsg = '', $uservalue = '') {
		$this->m_error = $errormsg;
		$this->m_value = $uservalue;
	}

	public function setUserValue($value) {
		$this->m_value = $value;
		return true;
	}

	public function setXSDValue($value, $unit) {
		$this->setUserValue($value); // no units, compatible syntax
	}

	public function setOutputFormat($formatstring){
		//do nothing
	}
	public function setError($errormsg){
		$this->m_error = $errormsg;
	}

	public function getShortWikiText($linked = NULL) {
		//TODO: support linking
		return $this->m_value;
	}

	public function getShortHTMLText($linker = NULL) {
		return $this->getShortWikiText($linker);
	}

	public function getLongWikiText($linked = NULL) {
		//TODO: support linking
		return '<span class="smwwarning">'.$this->m_error.'</span>';
		
	}

	public function getLongHTMLText($linker = NULL) {		
		return $this->getLongWikiText($linker);
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
		return 'error';
	}

	public function getInfolinks() {
		return $this->m_infolinks;
	}

	public function getHash() {
		return $this->getLongWikiText() . $this->m_value;
	}

	public function isValid() {
		return (($this->m_error == '') && ($this->m_value !== '') );
	}

	public function isNumeric() {
		return false;
	}
}
