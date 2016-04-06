<?php

namespace SMW\DataValues;

use SMWDataItem as DataItem;
use SMWDataValue as DataValue;
use SMWDIBlob as DIBlob;

/**
 * This datavalue implements datavalues used by special property '_IMPO' used
 * for assigning imported vocabulary to some page of the wiki. It looks up a
 * MediaWiki message to find out whether a user-supplied vocabulary name can be
 * imported in the wiki, and whether its declaration is correct (to the extent
 * that this can be checked).
 *
 * @author Fabian Howahl
 * @author Markus Krötzsch
 */
class ImportValue extends DataValue {

	/**
	 * @var ImportValueParser|null
	 */
	private $importValueParser = null;

	/**
	 * @var string
	 */
	private $termType = '';

	protected $m_qname = ''; // string provided by user which is used to look up data on Mediawiki:*-Page
	protected $m_uri = ''; // URI of namespace (without local name)
	protected $m_namespace = ''; // namespace id (e.g. "foaf")
	protected $m_section = ''; // local name (e.g. "knows")
	protected $m_name = ''; // wiki name of the vocab (e.g. "Friend of a Friend")l might contain wiki markup

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid  ) {
		parent::__construct( $typeid );
		$this->importValueParser = ValueParserFactory::getInstance()->newImportValueParser();
	}

	protected function parseUserValue( $value ) {
		$this->m_qname = $value;

		list( $this->m_namespace, $this->m_section, $this->m_uri, $this->m_name, $this->termType ) = $this->importValueParser->parse(
			$value
		);

		if ( $this->importValueParser->getErrors() !== array() ) {

			foreach ( $this->importValueParser->getErrors() as $message ) {
				$this->addError( call_user_func_array( 'wfMessage', $message )->inContentLanguage()->text() );
			}

			$this->m_dataitem = new DIBlob( 'ERROR' );
			return;
		}

		// Encoded string for DB storage
		$this->m_dataitem = new DIBlob(
			$this->m_namespace . ' ' .
			$this->m_section . ' ' .
			$this->m_uri . ' ' .
			$this->termType
		);

		// check whether caption is set, otherwise assign link statement to caption
		if ( $this->m_caption === false ) {
			$this->m_caption = "[" . $this->m_uri . " " . $this->m_qname . "] " . $this->modifyToIncludeParentheses( $this->m_name );
		}
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 * @param $dataitem DataItem
	 * @return boolean
	 */
	protected function loadDataItem( DataItem $dataItem ) {

		if ( !$dataItem instanceof DIBlob ) {
			return false;
		}

		$this->m_dataitem = $dataItem;
		$parts = explode( ' ', $dataItem->getString(), 4 );

		if ( count( $parts ) != 4 ) {
			$this->addError( wfMessage( 'smw_parseerror' )->inContentLanguage()->text() );
		} else {
			$this->m_namespace = $parts[0];
			$this->m_section = $parts[1];
			$this->m_uri = $parts[2];
			$this->termType = $parts[3];
			$this->m_qname = $this->m_namespace . ':' . $this->m_section;
			$this->m_caption = "[" . $this->m_uri . " " . $this->m_qname . "] " . " | " . wfMessage( 'smw-datavalue-import-link', $this->m_namespace )->escaped();
		}

		return true;
	}

	public function getShortWikiText( $linked = null ) {
		return $this->m_caption;
	}

	public function getShortHTMLText( $linker = null ) {
		return htmlspecialchars( $this->m_qname );
	}

	public function getLongWikiText( $linked = null ) {
		if ( !$this->isValid() ) {
			return $this->getErrorText();
		}

		return "[" . $this->m_uri . " " . $this->m_qname . "] " . " | " . wfMessage( 'smw-datavalue-import-link', $this->m_namespace )->escaped();
	}

	public function getLongHTMLText( $linker = null ) {
		if ( !$this->isValid() ) {
			return $this->getErrorText();
		} else {
			return htmlspecialchars( $this->m_qname );
		}
	}

	public function getWikiValue() {
		return $this->m_qname;
	}

	public function getNS() {
		return $this->m_uri;
	}

	public function getNSID() {
		return $this->m_namespace;
	}

	public function getLocalName() {
		return $this->m_section;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getTermType() {
		return $this->termType;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getImportReference() {
		return $this->m_namespace . ' ' . $this->m_section . ' ' . $this->m_uri;
	}

	private function modifyToIncludeParentheses( $name ) {
		return $name !== '' ? wfMessage( 'parentheses', $name )->parse() : '';
	}

}
