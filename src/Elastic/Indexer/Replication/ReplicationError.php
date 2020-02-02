<?php

namespace SMW\Elastic\Indexer\Replication;

use InvalidArgumentException;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ReplicationError {

	/**
	 * Whether the replication/client communication encountered an exception or
	 * not.
	 */
	const TYPE_EXCEPTION = 'exception/error';

	/**
	 * Indicating that the ES document is missing.
	 */
	const TYPE_DOCUMENT_MISSING = 'document/missing';

	/**
	 * Indicating that the ES document is missing a modification date.
	 */
	const TYPE_MODIFICATION_DATE_MISSING = 'document/modification_date/missing';

	/**
	 * Indicating that the ES document has a different modification date than the
	 * SQL backend.
	 */
	const TYPE_MODIFICATION_DATE_DIFF = 'document/modification_date/diff';

	/**
	 * Indicating that the ES document has a different associated revision than
	 * the SQL backend.
	 */
	const TYPE_ASSOCIATED_REVISION_DIFF = 'document/associated_revision/diff';

	/**
	 * Indicating that ...
	 */
	const TYPE_FILE_ATTACHMENT_MISSING = 'document/file_attachment/missing';

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var array
	 */
	private $data = [];

	/**
	 * @since 3.2
	 *
	 * @param string $type
	 * @param array $data
	 */
	public function __construct( $type, array $data = [] ) {
		$this->type = $type;
		$this->data = $data;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $type
	 *
	 * @return boolean
	 */
	public function is( $type ) : bool {
		return $this->type === $type;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 *
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public function get( $key ) {

		if ( !isset( $this->data[$key] ) ) {
			throw new InvalidArgumentException( "Key: `$key` is unknown!" );
		}

		return $this->data[$key];
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getType() : string {
		return $this->type;
	}

	/**
	 * @since 3.2
	 *
	 * @return mixed
	 */
	public function getData() : array {
		return $this->data;
	}

}
