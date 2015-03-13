<?php
/**
 * @ingroup SMWDataValues
 */

use SMW\ControlledVocabularyImportFetcher;
use SMW\ControlledVocabularyContentMapper;

/**
 * This datavalue implements datavalues used by special property '_IMPO' used
 * for assigning imported vocabulary to some page of the wiki. It looks up a
 * MediaWiki message to find out whether a user-supplied vocabulary name can be
 * imported in the wiki, and whether its declaration is correct (to the extent
 * that this can be checked).
 *
 * @author Fabian Howahl
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWImportValue extends SMWDataValue {

	/**
	 * @var ControlledVocabularyImportFetcher|null
	 */
	private $controlledVocabularyImportFetcher = null;

	/**
	 * @var ControlledVocabularyContentMapper|null
	 */
	private $controlledVocabularyContentMapper = null;

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
		$this->controlledVocabularyImportFetcher = new ControlledVocabularyImportFetcher();
		$this->controlledVocabularyContentMapper = new ControlledVocabularyContentMapper();
	}

	protected function parseUserValue( $value ) {
		$this->m_qname = $value;

		list( $onto_ns, $onto_section ) = explode( ':', $this->m_qname, 2 );

		if ( !$this->controlledVocabularyImportFetcher->contains( $onto_ns ) ) { // error: no elements for this namespace
			$this->addError( wfMessage( 'smw-datavalue-import-unknownns', $onto_ns )->inContentLanguage()->text() );
			$this->m_dataitem = new SMWDIBlob( 'ERROR' );
			return;
		}

		$this->controlledVocabularyContentMapper->parse( $this->controlledVocabularyImportFetcher->fetch( $onto_ns ) );

		$this->m_uri = $this->controlledVocabularyContentMapper->getUri();

		if ( $this->m_uri === '' ) {
			$this->addError( wfMessage( 'smw-datavalue-import-missing-nsuri', $onto_ns )->inContentLanguage()->text() );
			$this->m_dataitem = new SMWDIBlob( 'ERROR' );
			return;
		}

		$this->termType = $this->controlledVocabularyContentMapper->getTypeForTerm( $onto_section );

		if ( $this->termType === '' ) {
			$this->addError( wfMessage( 'smw-datavalue-import-missing-type', $onto_section, $onto_ns )->inContentLanguage()->text() );
			$this->m_dataitem = new SMWDIBlob( 'ERROR' );
			return;
		}

		$this->m_name = $this->controlledVocabularyContentMapper->getName();

		$this->m_namespace = $onto_ns;
		$this->m_section = $onto_section;

		$this->m_dataitem = new SMWDIBlob( $this->m_namespace . ' ' . $this->m_section . ' ' . $this->m_uri );

		// check whether caption is set, otherwise assign link statement to caption
		if ( $this->m_caption === false ) {
			$this->m_caption = "[" . $this->m_uri . " " . $this->m_qname . "] " . $this->modifyToIncludeParentheses( $this->m_name );
		}
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 * @param $dataitem SMWDataItem
	 * @return boolean
	 */
	protected function loadDataItem( SMWDataItem $dataItem ) {
		if ( $dataItem instanceof SMWDIBlob ) {
			$this->m_dataitem = $dataItem;
			$parts = explode( ' ', $dataItem->getString(), 3 );
			if ( count( $parts ) != 3 ) {
				$this->addError( wfMessage( 'smw_parseerror' )->inContentLanguage()->text() );
			} else {
				$this->m_namespace = $parts[0];
				$this->m_section = $parts[1];
				$this->m_uri = $parts[2];
				$this->m_qname = $this->m_namespace . ':' . $this->m_section;
				$this->m_caption = "[" . $this->m_uri . " " . $this->m_qname . "] " . $this->modifyToIncludeParentheses( $this->m_name );
			}
			return true;
		} else {
			return false;
		}
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

		return "[" . $this->m_uri . " " . $this->m_qname . "] " . $this->modifyToIncludeParentheses( $this->m_name );
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

	private function modifyToIncludeParentheses( $name ) {
		return $name !== '' ? wfMessage( 'parentheses', $name )->parse() : '';
	}

}
