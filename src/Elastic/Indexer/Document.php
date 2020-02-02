<?php

namespace SMW\Elastic\Indexer;

use JsonSerializable;
use SMW\DIWikiPage;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class Document implements JsonSerializable {

	const TYPE_INSERT = 'type/insert';
	const TYPE_UPSERT = 'type/upsert';
	const TYPE_DELETE = 'type/delete';

	/**
	 * @var integer
	 */
	private $id = 0;

	/**
	 * @var array
	 */
	private $data = [];

	/**
	 * @var string
	 */
	private $type = self::TYPE_INSERT;

	/**
	 * @var array
	 */
	private $subDocuments = [];

	/**
	 * @var array
	 */
	private $priorityDeleteList = [];

	/**
	 * @since 3.2
	 *
	 * @param integer $id
	 * @param array $data
	 * @param string $type
	 */
	public function __construct( int $id, array $data = [], string $type = self::TYPE_INSERT ) {
		$this->id = $id;
		$this->data = $data;
		$this->type = $type;
	}

	/**
	 * @since 3.2
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @since 3.2
	 *
	 * @return DIWikiPage
	 */
	public function getSubject() {
		return DIWikiPage::doUnserialize( $this->data['subject']['serialization'] );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $type
	 *
	 * @return boolean
	 */
	public function isType( $type ) {
		return $this->type === $type;
	}

	/**
	 * @since 3.2
	 *
	 * @param array $priorityDeleteList
	 */
	public function setPriorityDeleteList( array $priorityDeleteList ) {
		$this->priorityDeleteList = $priorityDeleteList;
	}

	/**
	 * @since 3.2
	 *
	 * @return []
	 */
	public function getPriorityDeleteList() {
		return $this->priorityDeleteList;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function setField( $key, $value ) {
		$this->data[$key] = $value;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $text
	 */
	public function setTextBody( string $text ) {
		if ( $text !== '' ) {
			$this->data['text_raw'] = TextSanitizer::removeLinks( $text );
		}
	}

	/**
	 * @since 3.2
	 *
	 * @param Document $document
	 */
	public function addSubDocument( Document $document ) {
		$this->subDocuments[$document->getId()] = $document;
	}

	/**
	 * @since 3.2
	 *
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @since 3.2
	 *
	 * @param integer $id
	 *
	 * @return boolean
	 */
	public function hasSubDocumentById( $id ) {
		return isset( $this->subDocuments[$id] );
	}

	/**
	 * @since 3.2
	 *
	 * @param integer $id
	 *
	 * @return Document
	 */
	public function getSubDocumentById( $id ) {
		return $this->subDocuments[$id];
	}

	/**
	 * @since 3.2
	 *
	 * @return Document[]|[]
	 */
	public function getSubDocuments() {
		return $this->subDocuments;
	}

	/**
	 * @since 3.2
	 *
	 * @return []
	 */
	public function toArray() {
		return [
			'id'   => $this->id,
			'type' => $this->type,
			'data' => $this->data,
			'sub_docs' => array_map(
				function( $v ) { return $v->toArray(); },
				$this->subDocuments
			)
		];
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function jsonSerialize() {
		return json_encode( $this->toArray() );
	}

}
