<?php

use SMW\Exporter\Element;
use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\Element\ExpResource;

/**
 * SMWExpData is a class representing semantic data that is ready for easy
 * serialisation in OWL or RDF.
 *
 * @author Markus Krötzsch
 * @ingroup SMW
 */

/**
 * SMWExpData is a data container for export-ready semantic content. It is
 * organised as a tree-shaped data structure with one root subject and zero
 * or more children connected with labelled edges to the root. Children are
 * again SMWExpData objects, and edges are annotated with SMWExpNsElements
 * specifying properties.
 * @note We do not allow property element without namespace abbreviation
 * here. Property aabbreviations are mandatory for some serialisations.
 *
 * @ingroup SMW
 */
class SMWExpData implements Element {

	/**
	 * @var DataItem|null
	 */
	private $dataItem;

	/**
	 * The subject of the data that we store.
	 * @var ExpResource
	 */
	protected $m_subject;

	/**
	 * Array mapping property URIs to arrays their values, given as
	 * SMW\Exporter\Element\ExpElement objects.
	 * @var array of array of SMWElement
	 */
	protected $m_children = [];

	/**
	 * Array mapping property URIs to arrays their ExpResource
	 * @var array of ExpResource
	 */
	protected $m_edges = [];

	/**
	 * @var string|null
	 */
	private $hash = null;

	/**
	 * Constructor. $subject is the ExpResource for the
	 * subject about which this SMWExpData is.
	 */
	public function __construct( ExpResource $subject ) {
		$this->dataItem = $subject->getDataItem();
		$this->m_subject = $subject;
	}

	/**
	 * @since 2.2
	 *
	 * @return DataItem|null
	 */
	public function getDataItem() {
		return $this->dataItem;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getHash() {
		if ( $this->hash !== null ) {
			return $this->hash;
		}

		$hashes = [];
		$hashes[] = $this->m_subject->getHash();

		foreach ( $this->getProperties() as $property ) {

			$hashes[] = $property->getHash();

			foreach ( $this->getValues( $property ) as $child ) {
				$hashes[] = $child->getHash();
			}
		}

		sort( $hashes );

		$this->hash = md5( implode( '#', $hashes ) );
		unset( $hashes );

		return $this->hash;
	}

	/**
	 * Turn an array of SMW\Exporter\Element\ExpElement into an RDF collection.
	 *
	 * @param $elements array of SMW\Exporter\Element\ExpElement
	 * @return SMWExpData
	 */
	public static function makeCollection( array $elements ) {
		$exporter = SMWExporter::getInstance();
		if ( count( $elements ) == 0 ) {
			return new SMWExpData( $exporter->newExpNsResourceById( 'rdf', 'nil' ) );
		}

		$result = new SMWExpData( new ExpResource( '' ) ); // bnode

		$result->addPropertyObjectValue(
			$exporter->newExpNsResourceById( 'rdf', 'type' ),
			new SMWExpData( $exporter->newExpNsResourceById( 'rdf', 'List' ) )
		);

		$result->addPropertyObjectValue(
			$exporter->newExpNsResourceById( 'rdf', 'first' ),
			array_shift( $elements )
		);

		$result->addPropertyObjectValue(
			$exporter->newExpNsResourceById( 'rdf', 'rest' ),
			self::makeCollection( $elements )
		);

		return $result;
	}

	/**
	 * Return subject to which the stored semantic annotation refer to.
	 *
	 * @return ExpResource
	 */
	public function getSubject() {
		return $this->m_subject;
	}

	/**
	 * Store a value for a property identified by its title object. No
	 * duplicate elimination as this is usually done in SMWSemanticData
	 * already (which is typically used to generate this object).
	 *
	 * @param ExpNsResource $property
	 * @param Element $child
	 */
	public function addPropertyObjectValue( ExpNsResource $property, Element $child ) {
		$this->hash = null;

		if ( !array_key_exists( $property->getUri(), $this->m_edges ) ) {
			$this->m_children[$property->getUri()] = [];
			$this->m_edges[$property->getUri()] = $property;
		}

		$this->m_children[$property->getUri()][] = $child;
	}

	/**
	 * Return the list of ExpResource objects for all properties for
	 * which some values have been given.
	 *
	 * @return array of ExpResource
	 */
	public function getProperties() {
		return $this->m_edges;
	}

	/**
	 * Return the list of SMW\Exporter\Element\ExpElement values associated to some property
	 * (element).
	 *
	 * @return array of SMW\Exporter\Element\ExpElement
	 */
	public function getValues( ExpResource $property ) {
		if ( array_key_exists( $property->getUri(), $this->m_children ) ) {
			return $this->m_children[$property->getUri()];
		}

		return [];
	}

	/**
	 * Return the list of SMWExpData values associated to some property that is
	 * specified by a standard namespace id and local name.
	 *
	 * @param $namespaceId string idetifying a known special namespace (e.g. "rdf")
	 * @param $localName string of local name (e.g. "type")
	 * @return array of SMWExpData
	 */
	public function getSpecialValues( $namespaceId, $localName ) {
		$pe = SMWExporter::getInstance()->newExpNsResourceById( $namespaceId, $localName );
		return $this->getValues( $pe );
	}

	/**
	 * This function finds the main type (class) element of the subject
	 * based on the current property assignments. It returns this type
	 * element (SMW\Exporter\Element\ExpElement) and removes the according type assignement
	 * from the data. If no type is assigned, the element for rdf:Resource
	 * is returned.
	 *
	 * @note Under all normal conditions, the result will be an
	 * ExpResource.
	 *
	 * @return SMW\Exporter\Element\ExpElement
	 */
	public function extractMainType() {
		$exporter = SMWExporter::getInstance();
		$pe = $exporter->newExpNsResourceById( 'rdf', 'type' );
		if ( array_key_exists( $pe->getUri(), $this->m_children ) ) {
			$result = array_shift( $this->m_children[$pe->getUri()] );
			if ( count( $this->m_children[$pe->getUri()] ) == 0 ) {
				unset( $this->m_edges[$pe->getUri()] );
				unset( $this->m_children[$pe->getUri()] );
			}
			return ( $result instanceof SMWExpData ) ? $result->getSubject() : $result;
		} else {
			return $exporter->newExpNsResourceById( 'rdf', 'Resource' );
		}
	}

	/**
	 * Check if this element encodes an RDF list, and if yes return an
	 * array of SMW\Exporter\Element\ExpElement corresponding to the collection elements in
	 * the specified order. Otherwise return false.
	 * The method only returns lists that can be encoded using
	 * parseType="Collection" in RDF/XML, i.e. only lists of non-literal
	 * resources.
	 *
	 * @return mixed array of SMW\Exporter\Element\ExpElement (but not ExpLiteral) or false
	 */
	public function getCollection() {
		$exporter = SMWExporter::getInstance();

		$rdftypeUri  = $exporter->newExpNsResourceById( 'rdf', 'type' )->getUri();
		$rdffirstUri = $exporter->newExpNsResourceById( 'rdf', 'first' )->getUri();
		$rdfrestUri  = $exporter->newExpNsResourceById( 'rdf', 'rest' )->getUri();
		$rdfnilUri   = $exporter->newExpNsResourceById( 'rdf', 'nil' )->getUri();
		// first check if we are basically an RDF List:
		if ( ( $this->m_subject->isBlankNode() ) &&
			 ( count( $this->m_children ) == 3 ) &&
			 ( array_key_exists( $rdftypeUri, $this->m_children ) ) &&
			 ( count( $this->m_children[$rdftypeUri] ) == 1 ) &&
			 ( array_key_exists( $rdffirstUri, $this->m_children ) ) &&
			 ( count( $this->m_children[$rdffirstUri] ) == 1 ) &&
			 !( end( $this->m_children[$rdffirstUri] ) instanceof ExpLiteral ) &&
			 // (parseType collection in RDF not possible with literals :-/)
			 ( array_key_exists( $rdfrestUri, $this->m_children ) ) &&
			 ( count( $this->m_children[$rdfrestUri] ) == 1 ) ) {
			$typedata = end( $this->m_children[$rdftypeUri] );
			$rdflistUri = $exporter->newExpNsResourceById( 'rdf', 'List' )->getUri();
			if ( $typedata->getSubject()->getUri() == $rdflistUri ) {
				$first = end( $this->m_children[$rdffirstUri] );
				$rest  = end( $this->m_children[$rdfrestUri] );
				if ( $rest instanceof SMWExpData ) {
					$restlist = $rest->getCollection();
					if ( $restlist === false ) {
						return false;
					} else {
						array_unshift( $restlist, $first );
						return $restlist;
					}
				} elseif ( ( $rest instanceof ExpResource ) &&
						   ( $rest->getUri() == $rdfnilUri ) ) {
					return [ $first ];
				} else {
					return false;
				}
			} else {
				return false;
			}
		} elseif ( ( count( $this->m_children ) == 0 ) && ( $this->m_subject->getUri() == $rdfnilUri ) ) {
			return [];
		} else {
			return false;
		}
	}

	/**
	 * Return an array of ternary arrays (subject predicate object) of
	 * SMW\Exporter\Element\ExpElement that represents the flattened version of this data.
	 *
	 * @return array of array of SMW\Exporter\Element\ExpElement
	 */
	public function getTripleList( ?Element $subject = null ) {
		global $smwgBnodeCount;
		if ( !isset( $smwgBnodeCount ) ) {
			$smwgBnodeCount = 0;
		}

		if ( $subject == null ) {
			$subject = $this->m_subject;
		}

		$result = [];

		foreach ( $this->m_edges as $key => $edge ) {
			foreach ( $this->m_children[$key] as $childElement ) {
				if ( $childElement instanceof SMWExpData ) {
					$childSubject = $childElement->getSubject();
				} else {
					$childSubject = $childElement;
				}

				if ( ( $childSubject instanceof ExpResource ) &&
					 ( $childSubject->isBlankNode() ) ) { // bnode, rename ID to avoid unifying bnodes of different contexts
					// TODO: should we really rename bnodes of the form "_id" here?
					$childSubject = new ExpResource( '_' . $smwgBnodeCount++, $childSubject->getDataItem() );
				}

				$result[] = [ $subject, $edge, $childSubject ];
				if ( $childElement instanceof SMWExpData ) { // recursively add child's triples
					$result = array_merge( $result, $childElement->getTripleList( $childSubject ) );
				}
			}
		}

		return $result;
	}

}
