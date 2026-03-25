<?php

namespace SMW\DataItems;

use MediaWiki\Json\JsonDeserializer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use SMW\Exception\DataItemDeserializationException;
use SMW\Exception\DataItemException;

/**
 * This class implements wiki page data items.
 *
 * @since 1.6
 * @ingroup SMWDataItems
 *
 * @author Markus Krötzsch
 */
class WikiPage extends DataItem {

	/**
	 * MediaWiki DB key string
	 * @var string
	 */
	protected $m_dbkey;

	/**
	 * MediaWiki namespace integer.
	 */
	protected int $m_namespace;

	/**
	 * Name for subobjects of pages, or empty string if the given object is
	 * the page itself (not a subobject).
	 * @var string
	 */
	protected $m_subobjectname;

	private ?string $sortkey = null;

	/**
	 * @var string
	 */
	private $contextReference = null;

	/**
	 * @var string
	 */
	private $pageLanguage = null;

	private int $id = 0;

	public int $recdepth;

	/**
	 * Constructor. We do not bother with too much detailed validation here,
	 * regarding the known namespaces, canonicity of the dbkey (namespace
	 * exrtacted?), validity of interwiki prefix (known?), and general use
	 * of allowed characters (may depend on MW configuration). All of this
	 * would be more work than it is worth, since callers will usually be
	 * careful and since errors here do not have major consequences.
	 */
	public function __construct(
		$dbkey,
		$namespace,
		protected $m_interwiki = '',
		$subobjectname = '',
	) {
		// Check if the provided value holds an integer
		// (it can be of type string or float as well, as long as the value is an int)
		if ( !ctype_digit( ltrim( (string)$namespace, '-' ) ) ) {
			throw new DataItemException( "Given namespace '$namespace' is not an integer." );
		}

		// Check for a potential fragment such as Foo#Bar, Bar#_49c8ab
		if ( strpos( $dbkey, '#' ) !== false ) {
			[ $dbkey, $subobjectname ] = explode( '#', $dbkey );
		}

		$this->m_dbkey = str_replace( ' ', '_', $dbkey );
		$this->m_namespace = (int)$namespace;
		$this->m_subobjectname = $subobjectname;
	}

	public function getDIType(): int {
		return DataItem::TYPE_WIKIPAGE;
	}

	public function getDBkey() {
		return $this->m_dbkey;
	}

	public function getNamespace(): int {
		return $this->m_namespace;
	}

	public function getInterwiki(): string {
		return $this->m_interwiki;
	}

	public function getSubobjectName() {
		return $this->m_subobjectname;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $prefix
	 *
	 * @return bool
	 */
	public function isSubEntityOf( string $prefix ): bool {
		if (
			$this->m_dbkey === '' ||
			$this->m_subobjectname === '' ||
			$prefix === '' ) {
			return false;
		}

		return substr( $this->m_subobjectname, 0, strlen( $prefix ) ) === $prefix;
	}

	/**
	 * @since 3.2
	 *
	 * @param int $namespace
	 *
	 * @return bool
	 */
	public function inNamespace( int $namespace ): bool {
		return $this->m_dbkey !== '' && $this->m_namespace === $namespace;
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getSha1(): string {
		return sha1( json_encode( [ $this->m_dbkey, $this->m_namespace, $this->m_interwiki, $this->m_subobjectname ] ) );
	}

	/**
	 * @since  2.1
	 *
	 * @param string $sortkey
	 */
	public function setSortKey( $sortkey ): void {
		$this->sortkey = str_replace( '_', ' ', $sortkey ?? '' );
	}

	/**
	 * Get the sortkey of the wiki page data item. Note that this is not
	 * the sortkey that might have been set for the corresponding wiki
	 * page. To obtain the latter, query for the values of the property
	 * "new SMW\DataItems\Property( '_SKEY' )".
	 */
	public function getSortKey(): string {
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
	public function setContextReference( $contextReference ): void {
		$this->contextReference = $contextReference;
	}

	/**
	 * Returns a reference for the processing context (parser etc.).
	 *
	 * @since 2.3
	 *
	 * @return string
	 */
	public function getContextReference(): ?string {
		return $this->contextReference;
	}

	/**
	 * Returns the page content language
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getPageLanguage(): string|false {
		if ( $this->pageLanguage === null ) {
			$this->pageLanguage = false;

			if ( ( $title = $this->getTitle() ) !== null ) {
				$this->pageLanguage = $title->getPageLanguage()->getCode();
			}
		}

		return $this->pageLanguage;
	}

	/**
	 * @since  2.5
	 *
	 * @param int $id
	 */
	public function setId( $id ): void {
		$this->id = (int)$id;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * Create a MediaWiki Title object for this WikiPage. The result
	 * can be null if an error occurred.
	 *
	 * @return Title|null
	 */
	public function getTitle(): ?Title {
		return MediaWikiServices::getInstance()->getTitleFactory()->makeTitleSafe(
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
	 * @return WikiPage
	 */
	public function asBase(): self {
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
	public function getSerialization(): string {
		$segments = [
			$this->m_dbkey,
			$this->m_namespace,
			$this->m_interwiki
		];

		$segments[] = $this->m_subobjectname;

		return implode( '#', $segments );
	}

	/**
	 * Create a data item from the provided serialization string and type ID.
	 *
	 * @param string $serialization
	 *
	 * @return WikiPage
	 * @throws DataItemDeserializationException
	 */
	public static function doUnserialize( $serialization ): self {
		$parts = explode( '#', $serialization, 4 );

		if ( count( $parts ) == 3 ) {
			return new self( $parts[0], intval( $parts[1] ), $parts[2] );
		} elseif ( count( $parts ) == 4 ) {
			return new self( $parts[0], intval( $parts[1] ), $parts[2], $parts[3] );
		} else {
			throw new DataItemDeserializationException( "Unserialization failed: the string \"$serialization\" was not understood." );
		}
	}

	/**
	 * Create a data item from a MediaWiki Title.
	 *
	 * @param Title $title
	 * @return WikiPage
	 */
	public static function newFromTitle( Title $title ): self {
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
	 * @return WikiPage
	 */
	public static function newFromText( $text, $namespace = NS_MAIN ): self {
		return new self( $text, $namespace );
	}

	public function equals( DataItem $di ): bool {
		if ( $di->getDIType() !== DataItem::TYPE_WIKIPAGE ) {
			return false;
		}

		return $di->getSerialization() === $this->getSerialization();
	}

	/**
	 * Implements JsonSerializable.
	 *
	 * @since 4.0.0
	 *
	 * @return array
	 */
	public function jsonSerialize(): array {
		$json = parent::jsonSerialize();
		$json['sortkey'] = $this->sortkey;
		$json['contextReference'] = $this->contextReference;
		$json['pageLanguage'] = $this->pageLanguage;
		$json['id'] = $this->id;
		return $json;
	}

	/**
	 * Implements JsonDeserializable.
	 *
	 * @since 4.0.0
	 *
	 * @param JsonDeserializer $deserializer
	 * @param array $json JSON to be unserialized
	 *
	 * @return self
	 */
	public static function newFromJsonArray( JsonDeserializer $deserializer, array $json ) {
		$obj = parent::newFromJsonArray( $deserializer, $json );
		$obj->sortkey = $json['sortkey'];
		$obj->contextReference = $json['contextReference'];
		$obj->pageLanguage = $json['pageLanguage'];
		$obj->id = $json['id'];
		return $obj;
	}

}

/**
 * @deprecated since 7.0.0
 */
class_alias( WikiPage::class, 'SMW\DIWikiPage' );
