<?php

namespace SMW;

use SMWDataItem;
use Title;

/**
 * This class implements wiki page data items.
 *
 * @since 1.6
 * @ingroup SMWDataItems
 *
 * @author Markus Krötzsch
 */
class DIWikiPage extends SMWDataItem {

	/**
	 * MediaWiki DB key string
	 * @var string
	 */
	protected $m_dbkey;

	/**
	 * MediaWiki namespace integer.
	 * @var integer
	 */
	protected $m_namespace;

	/**
	 * MediaWiki interwiki prefix.
	 * @var string
	 */
	protected $m_interwiki;

	/**
	 * Name for subobjects of pages, or empty string if the given object is
	 * the page itself (not a subobject).
	 * @var string
	 */
	protected $m_subobjectname;

	/**
	 * @var string
	 */
	private $sortkey = null;

	/**
	 * @var string
	 */
	private $contextReference = null;

	/**
	 * Contructor. We do not bother with too much detailed validation here,
	 * regarding the known namespaces, canonicity of the dbkey (namespace
	 * exrtacted?), validity of interwiki prefix (known?), and general use
	 * of allowed characters (may depend on MW configuration). All of this
	 * would be more work than it is worth, since callers will usually be
	 * careful and since errors here do not have major consequences.
	 *
	 * @param string $dbkey
	 * @param integer $namespace
	 * @param string $interwiki
	 * @param string $subobjectname
	 */
	public function __construct( $dbkey, $namespace, $interwiki = '', $subobjectname = '' ) {
		// Check if the provided value holds an integer
		// (it can be of type string or float as well, as long as the value is an int)
		if ( !ctype_digit( ltrim( (string)$namespace, '-' ) ) ) {
			throw new DataItemException( "Given namespace '$namespace' is not an integer." );
		}

		// Check for a potential fragment such as Foo#Bar, Bar#_49c8ab
		if ( strpos( $dbkey, '#' ) !== false ) {
			list( $dbkey, $subobjectname ) = explode( '#', $dbkey );
		}

		$this->m_dbkey = $dbkey;
		$this->m_namespace = (int)$namespace; // really make this an integer
		$this->m_interwiki = $interwiki;
		$this->m_subobjectname = $subobjectname;
	}

	public function getDIType() {
		return SMWDataItem::TYPE_WIKIPAGE;
	}

	public function getDBkey() {
		return $this->m_dbkey;
	}

	public function getNamespace() {
		return $this->m_namespace;
	}

	public function getInterwiki() {
		return $this->m_interwiki;
	}

	public function getSubobjectName() {
		return $this->m_subobjectname;
	}

	/**
	 * @since  2.1
	 *
	 * @param string $sortkey
	 */
	public function setSortKey( $sortkey ) {
		$this->sortkey = str_replace( '_', ' ', $sortkey );
	}

	/**
	 * Get the sortkey of the wiki page data item. Note that this is not
	 * the sortkey that might have been set for the corresponding wiki
	 * page. To obtain the latter, query for the values of the property
	 * "new SMW\DIProperty( '_SKEY' )".
	 */
	public function getSortKey() {

		if ( $this->sortkey === null || $this->sortkey === '' ) {
			$this->sortkey = str_replace( '_', ' ', $this->m_dbkey );
		}

		return $this->sortkey;
	}

	/**
	 * @since  2.3
	 *
	 * @param string $contextReference
	 */
	public function setContextReference( $contextReference ) {
		$this->contextReference = $contextReference;
	}

	/**
	 * Returns a reference for the processing context (parser etc.).
	 *
	 * @since 2.3
	 *
	 * @return string
	 */
	public function getContextReference() {
		return $this->contextReference;
	}

	/**
	 * Create a MediaWiki Title object for this DIWikiPage. The result
	 * can be null if an error occurred.
	 *
	 * @return Title|null
	 */
	public function getTitle() {
		return Title::makeTitleSafe(
			$this->m_namespace,
			$this->m_dbkey,
			$this->m_subobjectname,
			$this->m_interwiki
		);
	}

	/**
	 * Returns the base part (without a fragment) of a wikipage representation.
	 *
	 * @since 2.4
	 *
	 * @return DIWikiPage
	 */
	public function asBase() {
		return new self (
			$this->m_dbkey,
			$this->m_namespace,
			$this->m_interwiki
		);
	}

	/**
	 * @since 1.6
	 *
	 * @return string
	 */
	public function getSerialization() {
		$segments = array(
			$this->m_dbkey,
			$this->m_namespace,
			$this->m_interwiki
		);

		if ( $this->m_subobjectname !== '' ) {
			$segments[] = $this->m_subobjectname;
		}

		return implode( '#', $segments );
	}

	/**
	 * Create a data item from the provided serialization string and type ID.
	 *
	 * @param string $serialization
	 *
	 * @return DIWikiPage
	 * @throws DataItemException
	 */
	public static function doUnserialize( $serialization ) {
		$parts = explode( '#', $serialization, 4 );

		if ( count( $parts ) == 3 ) {
			return new self( $parts[0], intval( $parts[1] ), $parts[2] );
		} elseif ( count( $parts ) == 4 ) {
			return new self( $parts[0], intval( $parts[1] ), $parts[2], $parts[3] );
		} else {
			throw new DataItemException( "Unserialization failed: the string \"$serialization\" was not understood." );
		}
	}

	/**
	 * Create a data item from a MediaWiki Title.
	 *
	 * @param $title Title
	 * @return DIWikiPage
	 */
	public static function newFromTitle( Title $title ) {
		return new self(
			$title->getDBkey(),
			$title->getNamespace(),
			$title->getInterwiki(),
			str_replace( ' ', '_', $title->getFragment() )
		);
	}

	/**
	 * @since 2.1
	 *
	 * @param string $text
	 * @param integer namespace
	 *
	 * @return DIWikiPage
	 */
	public static function newFromText( $text, $namespace = NS_MAIN ) {
		return new self(
			str_replace( ' ', '_', $text ),
			$namespace
		);
	}

	public function equals( SMWDataItem $di ) {
		if ( $di->getDIType() !== SMWDataItem::TYPE_WIKIPAGE ) {
			return false;
		}

		return $di->getSerialization() === $this->getSerialization();
	}
}

