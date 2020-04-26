<?php

namespace SMW\Elastic\Indexer;

use SMW\Store;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SemanticData;
use SMW\DataTypeRegistry;
use SMW\MediaWiki\Collator;
use SMW\Parser\LinksEncoder;
use SMWDIBlob as DIBlob;
use SMWDITime as DITime;
use SMWDataItem as DataItem;

/**
 * @private
 *
 * The structure for a document to represent Semantic MediaWiki facts and statements
 * in Elasticsearch should be similar to:
 *
 * "subject": {
 *    "title": "Foaf:knows",
 *    "subobject": "",
 *    "namespace": 102,
 *    "interwiki": "",
 *    "sortkey": "Foaf:knows"
 * },
 * "P:8": {
 *    "txtField": [
 *       "foaf knows http://xmlns.com/foaf/0.1/ Type:Page"
 *    ]
 * },
 * "P:29": {
 *    "datField": [
 *       2458150.6958333
 *    ]
 * },
 * "P:1": {
 *    "uriField": [
 *       "http://semantic-mediawiki.org/swivt/1.0#_wpg"
 *    ]
 * }
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class DocumentCreator {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var boolean
	 */
	private $compatibilityMode = true;

	/**
	 * @var integer
	 */
	private $documentCreationDuration = 0;

	/**
	 * @var array
	 */
	private $subEntities = [];

	/**
	 * @since 3.2
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.2
	 *
	 * @param boolean $compatibilityMode
	 */
	public function setCompatibilityMode( $compatibilityMode ) {
		$this->compatibilityMode = $compatibilityMode;
	}

	/**
	 * @since 3.2
	 *
	 * @return integer
	 */
	public function getDocumentCreationDuration() : int {
		return $this->documentCreationDuration;
	}

	/**
	 * @since 3.2
	 *
	 * @param SemanticData $semanticData
	 *
	 * @return Document
	 */
	public function newFromSemanticData( SemanticData $semanticData ) : Document {

		$time = microtime( true );
		$this->subEntities = [];

		$document = $this->newFromData( $semanticData );
		$id = $document->getId();

		foreach ( $semanticData->getSubSemanticData() as $subSemanticData ) {
			$document->addSubDocument( $this->newFromData( $subSemanticData, $id ) );
		}

		// Adding a link between subentities can only be done after all items
		// have been processed because pending entities don't follow any order
		// in `SemanticData::SubSemanticData` and have therefore to be resolved
		// first.
		foreach ( $this->subEntities as $oid => $value ) {

			if ( !$document->hasSubDocumentById( $oid ) ) {
				continue;
			}

			$pid = $value['pid'];

			// We use an auxiliary field marker to indicate an artificial extension
			// and avoid any confusion with a "normal" field type
			$value = [ 'parent_id' => $value['sid'] ];

			$document->getSubDocumentById( $oid )->setField( "P:$pid", $value );
		}

		$this->documentCreationDuration = ( microtime( true ) - $time );

		return $document;
	}

	private function newFromData( SemanticData $semanticData, $parent_id = null ) {

		$subject = $semanticData->getSubject();
		$dataTypeRegistry = DataTypeRegistry::getInstance();

		$id = (int)$this->store->getObjectIds()->getSMWPageID(
			$subject->getDBKey(),
			$subject->getNamespace(),
			$subject->getInterwiki(),
			$subject->getSubobjectName(),
			false
		);

		$data = [
			'subject' => $this->makeSubject( $subject )
		];

		if ( ( $rev_id = $semanticData->getExtensionData( 'revision_id' ) ) !== null ) {
			$data['subject']['rev_id'] = (int)$rev_id;
		}

		if ( $parent_id !== null ) {
			$data['subject']['parent_id'] = $parent_id;
		}

		$type = Document::TYPE_INSERT;

		// Remove any document that has been identified as redirect to avoid
		// having Elasticsearch to match those documents and create a subject
		// match similar to `[[::smw-redi:Issue/1286|Issue/1286]]` (#P0904)
		if ( $semanticData->hasProperty( new DIProperty( '_REDI' ) ) ) {
			$type = Document::TYPE_DELETE;
		}

		$document = new Document(
			$id,
			$data,
			$type
		);

		$properties = $semanticData->getProperties();

		foreach ( $properties as $property ) {
			$values = [];

			$pid = (int)$this->store->getObjectIds()->getSMWPropertyID(
				$property
			);

			// Could be `_SKEY` ....
			if ( $pid == 0 ) {
				continue;
			}

			$valueType = $property->findPropertyValueType();

			// Optimize terms lookup for subentities
			$isSubDataType = $dataTypeRegistry->isSubDataType(
				$valueType
			);

			$fieldType = $dataTypeRegistry->getFieldType(
				$valueType
			);

			$fieldType = str_replace( '_', '', $fieldType ) . 'Field';
			$values["$fieldType"] = [];

			foreach ( $semanticData->getPropertyValues( $property ) as $dataItem ) {

				$type = $dataItem->getDIType();

				if ( $type === DataItem::TYPE_NUMBER ) {
					$values["$fieldType"][] = $dataItem->getNumber();
				} elseif ( $type === DataItem::TYPE_BOOLEAN ) {
					$values["$fieldType"][] = $dataItem->getBoolean();
				} elseif ( $type === DataItem::TYPE_TIME ) {
					// `datField` (time value) is a numeric field (JD number) to
					// enable us to use ranges on dates with values being representable
					// from January 1, 4713 BC (proleptic Julian calendar)
					$values["$fieldType"][] = $dataItem->getJD();

					if ( !isset( $values["dat_raw"] ) ) {
						$values["dat_raw"] = [];
					}

					// Replicate dates in the serialized raw_format to give
					// aggregations a chance to filter dates by term
					$values["dat_raw"][] = $dataItem->getSerialization();
				} elseif ( $type === DataItem::TYPE_WIKIPAGE ) {

					// T:P0434 (reference, record chain sorting)
					// Used for `compatibilityMode`
					$values["$fieldType"][] = mb_convert_encoding( $dataItem->getSortKey(), 'UTF-8', 'UTF-8' );

					$oid = (int)$this->store->getObjectIds()->getSMWPageID(
						$dataItem->getDBKey(),
						$dataItem->getNamespace(),
						$dataItem->getInterwiki(),
						$dataItem->getSubobjectName(),
						true
					);

					if ( !isset( $values["wpgID"] ) ) {
						$values["wpgID"] = [];
					}

					$values["wpgID"][] = $oid;

					// Memorize the parent of a subentity (subobject) as an inverse
					// relation. This allows to restrict a condition to a subentity
					// while building a subquery construct and avoids adding a filter
					// during a later operation stage.
					if ( $isSubDataType ) {
						$this->subEntities[$oid] = [ 'pid' => $pid, 'sid' => $id ];
					}

					// Create a minimal body for an inverted relation
					//
					// When a query `[[-Has mother::Michael]]` inquiries a relationship
					// on the fact of `Michael` -> `[[Has mother::Carol]] with
					// `Carol` being redlinked (not exists as page) the query can
					// match the object.
					//
					// @see also T:Q0105#8
					if ( !$document->hasSubDocumentById( $oid ) ) {
						$document->addSubDocument( $this->newHead( $oid, $dataItem, Document::TYPE_UPSERT ) );
					}
				} elseif ( $type === DataItem::TYPE_BLOB ) {

					// Used for `compatibilityMode`
					$val = htmlspecialchars_decode( trim( $dataItem->getString() ), ENT_QUOTES );

					// #3020, 3035
					if ( $valueType === '_keyw' ) {
						$val = $dataItem->normalize( $val );
					}

					// Remove control chars and avoid Elasticsearch to throw a
					// "SmartSerializer.php: Failed to JSON encode: 5" since JSON requires
					// valid UTF-8
					$values["$fieldType"][] = TextSanitizer::removeLinks( mb_convert_encoding( $val, 'UTF-8', 'UTF-8' ) );
				} elseif ( $type === DataItem::TYPE_URI ) {

					// Used for `compatibilityMode`
					$values["$fieldType"][] = rawurldecode( $dataItem->getSerialization() );

					if ( $property->getKey() === '_TYPE' ) {
						$values["typeField"] = [ $dataItem->getFragment() ];
					}
				} else {
					$values["$fieldType"][] = $dataItem->getSerialization();
				}
			}

			$document->setField( "P:$pid", $values );
		}

		return $document;
	}

	private function newHead( $id, DIWikiPage $subject, $type ) {
		return new Document( $id, [ 'subject' => $this->makeSubject( $subject ) ], $type );
	}

	private function makeSubject( DIWikiPage $subject ) {

		$title = $subject->getDBKey();

		if ( $subject->getNamespace() !== SMW_NS_PROPERTY || $title[0] !== '_' ) {
			$title = str_replace( '_', ' ', $title );
		}

		$sort = $subject->getSortKey();
		$sort = Collator::singleton()->getSortKey( $sort );

		// Use collated sort field if available
		if ( $subject->getOption( 'sort', '' ) !== '' ) {
			$sort = $subject->getOption( 'sort' );
		}

		// This may loose some non valif UTF-8 characters as it is required by ES
		// to be strict UTF-8 otherwise the ES indexer will fail with a serialization
		// error because ES only allows UTF-8 but when the collator applies something
		// like `uca-default-u-kn` it can produce characters not valid for/by
		// ES hence the sorting compared to the SQLStore will be different (given
		// the DB stores the byte representation)
		$sort = mb_convert_encoding( $sort, 'UTF-8', 'UTF-8' );

		return [
			'title'     => $title,
			'subobject' => $subject->getSubobjectName(),
			'namespace' => $subject->getNamespace(),
			'interwiki' => $subject->getInterwiki(),
			'sortkey'   => $sort,
			'serialization' => $subject->getSerialization(),
			'sha1' => $subject->getSha1()
		];
	}

}
