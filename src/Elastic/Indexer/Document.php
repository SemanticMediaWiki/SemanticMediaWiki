<?php

namespace SMW\Elastic\Indexer;

use JsonSerializable;
use SMW\DataItems\WikiPage;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class Document implements JsonSerializable {

	const TYPE_INSERT = 'type/insert';
	const TYPE_UPSERT = 'type/upsert';
	const TYPE_DELETE = 'type/delete';

	private array $subDocuments = [];

	private array $priorityDeleteList = [];

	/**
	 * @since 3.2
	 */
	public function __construct(
		private readonly int $id,
		private array $data = [],
		private readonly string $type = self::TYPE_INSERT,
	) {
	}

	/**
	 * @since 3.2
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * @since 3.2
	 */
	public function getSubject(): WikiPage {
		return WikiPage::doUnserialize( $this->data['subject']['serialization'] );
	}

	/**
	 * @since 3.2
	 */
	public function isType( string $type ): bool {
		return $this->type === $type;
	}

	/**
	 * @since 3.2
	 */
	public function setPriorityDeleteList( array $priorityDeleteList ): void {
		$this->priorityDeleteList = $priorityDeleteList;
	}

	/**
	 * @since 3.2
	 */
	public function getPriorityDeleteList(): array {
		return $this->priorityDeleteList;
	}

	/**
	 * @since 3.2
	 */
	public function setField( string $key, mixed $value ): void {
		$this->data[$key] = $value;
	}

	/**
	 * @since 3.2
	 */
	public function setTextBody( string $text ): void {
		if ( $text !== '' ) {
			$this->data['text_raw'] = TextSanitizer::removeLinks( $text );
		}
	}

	/**
	 * @since 3.2
	 */
	public function addSubDocument( Document $document ): void {
		$this->subDocuments[$document->getId()] = $document;
	}

	/**
	 * @since 3.2
	 *
	 * @return array
	 */
	public function getData(): array {
		return $this->data;
	}

	/**
	 * @since 3.2
	 */
	public function hasSubDocumentById( int $id ): bool {
		return isset( $this->subDocuments[$id] );
	}

	/**
	 * @since 3.2
	 */
	public function getSubDocumentById( int $id ): Document {
		return $this->subDocuments[$id];
	}

	/**
	 * @since 3.2
	 *
	 * @return Document[]
	 */
	public function getSubDocuments(): array {
		return $this->subDocuments;
	}

	/**
	 * @since 3.2
	 */
	public function toArray(): array {
		return [
			'id'   => $this->id,
			'type' => $this->type,
			'data' => $this->data,
			'sub_docs' => array_map(
				static function ( $v ) { return $v->toArray();
				},
				$this->subDocuments
			)
		];
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function jsonSerialize(): string {
		return json_encode( $this->toArray() );
	}

}
