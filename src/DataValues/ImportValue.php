<?php

namespace SMW\DataValues;

use SMW\Message;
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
	 * DV identifier
	 */
	const TYPE_ID = '__imp';

	/**
	 * Fixed Mediawiki import prefix
	 */
	const IMPORT_PREFIX = 'Smw_import_';

	/**
	 * Type string assigned by the import declaration
	 *
	 * @var string
	 */
	private $termType = '';

	/**
	 * String provided by user which is used to look up data on Mediawiki:*-Page
	 *
	 * @var string
	 */
	private $qname = '';

	/**
	 * URI of namespace (without local name)
	 *
	 * @var string
	 */
	private $uri = '';

	/**
	 * Namespace id (e.g. "foaf")
	 *
	 * @var string
	 */
	private $namespace = '';

	/**
	 * Local name (e.g. "knows")
	 *
	 * @var string
	 */
	private $term = '';

	/**
	 * Wiki name of the vocab (e.g. "Friend of a Friend"), might contain wiki markup
	 *
	 * @var string
	 */
	private $declarativeName = '';

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = self::TYPE_ID ) {
		parent::__construct( $typeid );
	}

	/**
	 * @see DataValue::parseUserValue
	 *
	 * @param string $value
	 */
	protected function parseUserValue( $value ) {
		$this->qname = $value;

		$importValueParser = $this->dataValueServiceFactory->getValueParser(
			$this
		);

		list( $this->namespace, $this->term, $this->uri, $this->declarativeName, $this->termType ) = $importValueParser->parse(
			$value
		);

		if ( $importValueParser->getErrors() !== [] ) {

			foreach ( $importValueParser->getErrors() as $message ) {
				$this->addErrorMsg( $message );
			}

			$this->m_dataitem = new DIBlob( 'ERROR' );
			return;
		}

		// Encoded string for DB storage
		$this->m_dataitem = new DIBlob(
			$this->namespace . ' ' .
			$this->term . ' ' .
			$this->uri . ' ' .
			$this->termType
		);

		// check whether caption is set, otherwise assign link statement to caption
		if ( $this->m_caption === false ) {
			$this->m_caption = $this->createCaption( $this->namespace, $this->qname, $this->uri, $this->declarativeName );
		}
	}

	/**
	 * @see SMWDataValue::loadDataItem
	 *
	 * @param DataItem $dataitem
	 *
	 * @return boolean
	 */
	protected function loadDataItem( DataItem $dataItem ) {

		if ( !$dataItem instanceof DIBlob ) {
			return false;
		}

		$this->m_dataitem = $dataItem;
		$parts = explode( ' ', $dataItem->getString(), 4 );

		if ( count( $parts ) != 4 ) {
			$this->addErrorMsg( [ 'smw-datavalue-import-invalid-format', $dataItem->getString() ] );
		} else {
			$this->namespace = $parts[0];
			$this->term = $parts[1];
			$this->uri = $parts[2];
			$this->termType = $parts[3];
			$this->qname = $this->namespace . ':' . $this->term;
			$this->declarativeName = '';
			$this->m_caption = $this->createCaption( $this->namespace, $this->qname, $this->uri, $this->declarativeName );
		}

		return true;
	}

	/**
	 * @see DataValue::getShortWikiText
	 */
	public function getShortWikiText( $linked = null ) {
		return $this->m_caption;
	}

	/**
	 * @see DataValue::getShortHTMLText
	 */
	public function getShortHTMLText( $linker = null ) {
		return htmlspecialchars( $this->qname );
	}

	/**
	 * @see DataValue::getLongWikiText
	 */
	public function getLongWikiText( $linked = null ) {

		if ( !$this->isValid() ) {
			return $this->getErrorText();
		}

		return "[[MediaWiki:" . self::IMPORT_PREFIX . $this->namespace . "|" . $this->qname . "]]";
	}

	/**
	 * @see DataValue::getLongHTMLText
	 */
	public function getLongHTMLText( $linker = null ) {

		if ( !$this->isValid() ) {
			return $this->getErrorText();
		}

		return htmlspecialchars( $this->qname );
	}

	/**
	 * @see DataValue::getWikiValue
	 */
	public function getWikiValue() {
		return $this->qname;
	}

	public function getNS() {
		return $this->uri;
	}

	public function getNSID() {
		return $this->namespace;
	}

	public function getLocalName() {
		return $this->term;
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
		return $this->namespace . ':' . $this->term . '|' . $this->uri;
	}

	private function createCaption( $namespace, $qname, $uri, $declarativeName ) {
		return "[[MediaWiki:" . self::IMPORT_PREFIX . $namespace . "|" . $qname . "]] " .  Message::get( [ 'parentheses', "[$uri $namespace] | " . $declarativeName ], Message::PARSE );
	}

}
