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
	
	public function SMWErrorValue($errormsg = '', $uservalue = '', $caption = false) {
		$this->m_error = $errormsg;
		$this->setUserValue($uservalue, $caption);
	}

	protected function parseUserValue($value) {
		if ($this->m_caption === false) {
			$this->m_caption = $value;
		}
		$this->m_value = $value;
		return true;
	}

	protected function parseXSDValue($value, $unit) {
		$this->setUserValue($value); // no units, compatible syntax
	}

	public function setOutputFormat($formatstring){
		// no output formats
	}
	public function setError($errormsg){
		$this->m_error = $errormsg;
	}

	public function getShortWikiText($linked = NULL) {
		//TODO: support linking?
		return $this->m_caption;
	}

	public function getShortHTMLText($linker = NULL) {
		return htmlspecialchars($this->getShortWikiText($linker));
	}

	public function getLongWikiText($linked = NULL) {
		//TODO: support linking?
		return '<span class="smwwarning">'.$this->m_error.'</span>';
	}

	public function getLongHTMLText($linker = NULL) {
		return $this->getLongWikiText($linker);
	}

	public function getXSDValue() {
		return $this->getShortWikiText(); ///TODO: really? (errors are not meant to be saved, or are they?)
	}
	
	public function getWikiValue(){
		return $this->getShortWikiText(); /// FIXME: wikivalue must not be influenced by the caption
	}
	
	public function getNumericValue() {
		return NULL;
	}

	public function getUnit() {
		return ''; // empty unit
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
