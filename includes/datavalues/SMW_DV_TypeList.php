<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue implements special processing suitable for defining the list
 * of types that is required for SMWRecordValue objects. The input is a plain
 * semicolon-separated list of type labels.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWTypeListValue extends SMWDataValue {

	private $m_typevalues = false;

	protected function parseUserValue( $value ) {
		$this->m_typevalues = array();
		$types = explode( ';', $value );
		foreach ( $types as $type ) {
			$tval = SMWDataValueFactory::newTypeIDValue( '__typ', $type );
			$this->m_typevalues[] = $tval;
		}
	}

	protected function parseDBkeys( $args ) {
		$this->m_typevalues = array();
		$ids = explode( ';', $args[0] );
		foreach ( $ids as $id ) {
			$this->m_typevalues[] = SMWDataValueFactory::newTypeIDValue( '__typ', SMWDataValueFactory::findTypeLabel( $id ) );
		}
	}

	/**
	 * The special feature of this implementation of getDBkeys is that it uses
	 * internal type ids to obtain a short internal value for the type. Note
	 * that this also given language independence but that this is of little
	 * use: if the value is given in another language in the wiki, then either
	 * the value is still understood, or the language-independent database
	 * entry is only of temporary use until someine edits the respective page.
	 */
	public function getDBkeys() {
		if ( $this->isvalid() ) {
			$result = '';
			foreach ( $this->m_typevalues as $tv ) {
				if ( $result != '' ) $result .= ';';
				$result .= $tv->getDBkey();
			}
			return array( $result );
		} else {
			return array( false );
		}
	}

	public function getSignature() {
		return 't';
	}

	public function getValueIndex() {
		return 0;
	}

	public function getLabelIndex() {
		return 0;
	}

	public function getShortWikiText( $linked = null ) {
		return ( $this->m_caption !== false ) ?  $this->m_caption : $this->makeOutputText( 0, $linked );
	}

	public function getShortHTMLText( $linker = null ) {
		return ( $this->m_caption !== false ) ? $this->m_caption : $this->makeOutputText( 1, $linker );
	}

	public function getLongWikiText( $linked = null ) {
		return $this->makeOutputText( 2, $linked );
	}

	public function getLongHTMLText( $linker = null ) {
		return $this->makeOutputText( 3, $linker );
	}

	public function getWikiValue() {
		return $this->makeOutputText( 4 );
	}

	public function getTypeValues() {
		$this->unstub();
		return $this->m_typevalues;
	}

////// Internal helper functions

	private function makeOutputText( $type = 0, $linker = null ) {
		if ( !$this->isValid() ) {
			return ( ( $type == 0 ) || ( $type == 1 ) ) ? '' : $this->getErrorText();
		}
		$result = '';
		$sep = ( $type == 4 ) ? '; ' : ', ';
		foreach ( $this->m_typevalues as $tv ) {
			if ( $result != '' ) $result .= $sep;
			$result .= $this->makeValueOutputText( $type, $tv, $linker );
		}
		return $result;
	}

	private function makeValueOutputText( $type, $datavalue, $linker ) {
		switch ( $type ) {
			case 0: return $datavalue->getShortWikiText( $linker );
			case 1: return $datavalue->getShortHTMLText( $linker );
			case 2: return $datavalue->getLongWikiText( $linker );
			case 3: return $datavalue->getLongHTMLText( $linker );
			case 4: return $datavalue->getWikiValue();
		}
	}

}