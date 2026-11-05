<?php

namespace SMW\Elastic\Indexer\Replication;

use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Exception;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\RequestOptions;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class DocumentReplicationExaminer {

	/**
	 * Checks whether the requested document exists or not.
	 */
	const CHECK_DOCUMENT_EXISTS = 'check/document/exists';

	/**
	 * Checks whether the file entity has a `File attachment` property or
	 * not which only makes sense if the file ingest support is enabled.
	 */
	const CHECK_MISSING_FILE_ATTACHMENT = 'check/missing/file_attachment';

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly Store $store,
		private readonly ReplicationStatus $replicationStatus,
	) {
	}

	/**
	 * @since 3.1
	 */
	public function check( WikiPage $subject, array $params = [] ): ?ReplicationError {
		$id = $this->findId( $subject );

		$replicationError = $this->isMissingDocument( $params, $id );

		if ( $replicationError instanceof ReplicationError ) {
			return $replicationError;
		}

		$status = $this->runCheck(
			'modification_date_associated_revision',
			$id
		);

		if ( $status instanceof ReplicationError ) {
			return $status;
		}

		// What is stored in the DB
		$dataItems = $this->store->getPropertyValues(
			$subject,
			new Property( '_MDAT' )
		);

		return $this->findError(
			$subject,
			$params,
			$dataItems,
			$id,
			$status,
			$this->store->getObjectIds()->findAssociatedRev( $id )
		);
	}

	/**
	 * Checks the subjects as check() does, reading the documents, modification dates and revisions of all of them
	 * at once
	 *
	 * @since 7.4.1
	 *
	 * @param WikiPage[] $subjects
	 *
	 * @return array<int|string, ReplicationError|null> The error found for each subject, keyed like $subjects
	 */
	public function checkAll( array $subjects, array $params = [] ): array {
		$this->store->getObjectIds()->warmUpCache( $subjects );
		$ids = array_map( [ $this, 'findId' ], $subjects );

		try {
			$statuses = $this->replicationStatus->getStatusOfDocuments( $ids );
		} catch ( Exception ) {
			// A failed request is no finding about its documents, so each is checked on its own as check() would
			return array_map( fn ( WikiPage $subject ) => $this->check( $subject, $params ), $subjects );
		}

		$property = new Property( '_MDAT' );
		$requestOptions = new RequestOptions();
		$prefetchCache = $this->store->service( 'PrefetchCache' );
		$prefetchCache->prefetch( array_values( $subjects ), $property, $requestOptions );
		$revisions = $this->store->getObjectIds()->findAssociatedRevs( $ids );
		$errors = [];

		foreach ( $subjects as $key => $subject ) {
			$id = $ids[$key];

			if ( !$statuses[$id]['exists'] && ( $params[self::CHECK_DOCUMENT_EXISTS] ?? false ) ) {
				$errors[$key] = $this->newReplicationError( ReplicationError::TYPE_DOCUMENT_MISSING, [ 'id' => $id ] );
				continue;
			}

			$errors[$key] = $this->findError(
				$subject,
				$params,
				$prefetchCache->getPropertyValues( $subject, $property, $requestOptions ),
				$id,
				$statuses[$id],
				$revisions[$id]
			);
		}

		return $errors;
	}

	private function findId( WikiPage $subject ): int {
		$id = $this->store->getObjectIds()->getSMWPageID(
			$subject->getDBKey(),
			$subject->getNamespace(),
			$subject->getInterwiki(),
			$subject->getSubobjectname()
		);

		$subject->setId( $id );

		return $id;
	}

	/**
	 * @param WikiPage $subject
	 * @param array $params
	 * @param DataItem[] $dataItems
	 * @param int $id
	 * @param array{modification_date: \SMW\DataItems\Time|false, associated_revision: mixed} $status
	 * @param int $associatedRev The revision recorded in the store
	 */
	private function findError(
		WikiPage $subject,
		array $params,
		$dataItems,
		$id,
		array $status,
		int $associatedRev
	): ?ReplicationError {
		$replicationError = $this->hasMissingModificationDate( $dataItems, $id, $status );

		if ( $replicationError instanceof ReplicationError ) {
			return $replicationError;
		}

		$replicationError = $this->hasModificationDateDiff( $dataItems, $id, $status );

		if ( $replicationError instanceof ReplicationError ) {
			return $replicationError;
		}

		$replicationError = $this->hasAssociatedRevisionDiff( $id, $status, $associatedRev );

		if ( $replicationError instanceof ReplicationError ) {
			return $replicationError;
		}

		$replicationError = $this->hasMissingFileAttachment( $params, $subject );

		if ( $replicationError instanceof ReplicationError ) {
			return $replicationError;
		}

		return null;
	}

	private function newReplicationError( string $type, array $data ): ReplicationError {
		return new ReplicationError( $type, $data );
	}

	private function isMissingDocument( array $params, $id ) {
		if ( !isset( $params[self::CHECK_DOCUMENT_EXISTS] ) || $params[self::CHECK_DOCUMENT_EXISTS] === false ) {
			return false;
		}

		$response = $this->runCheck( 'exists', $id );

		if ( $response instanceof ReplicationError ) {
			return $response;
		} elseif ( $response === false ) {
			return $this->newReplicationError( ReplicationError::TYPE_DOCUMENT_MISSING, [ 'id' => $id ] );
		}
	}

	private function runCheck( string $method, $id ) {
		try {
			$replicationStatusResponse = $this->replicationStatus->get( $method, $id );
		// @phan-suppress-next-line PhanUndeclaredClassCatch
		} catch ( BadRequest400Exception ) {
			return $this->newReplicationError( ReplicationError::TYPE_EXCEPTION, [ 'exception_error' => 'BadRequest400Exception' ] );
		} catch ( Exception $e ) {
			return $this->newReplicationError( ReplicationError::TYPE_EXCEPTION, [ 'exception_error' => $e->getMessage() ] );
		}

		return $replicationStatusResponse;
	}

	private function hasMissingModificationDate( $dataItems, $id, array $status ): false|ReplicationError {
		if ( $status['modification_date'] !== false && $dataItems !== [] ) {
			return false;
		}

		return $this->newReplicationError( ReplicationError::TYPE_MODIFICATION_DATE_MISSING, [ 'id' => $id ] );
	}

	private function hasModificationDateDiff( $dataItems, $id, array $status ): false|ReplicationError {
		$dataItem = end( $dataItems );

		if ( $dataItem->equals( $status['modification_date'] ) ) {
			return false;
		}

		$data = [
			'id' => $id,
			'time_es' => $status['modification_date']->asDateTime()->format( 'Y-m-d H:i:s' ),
			'time_store' => $dataItem->asDateTime()->format( 'Y-m-d H:i:s' )
		];

		return $this->newReplicationError( ReplicationError::TYPE_MODIFICATION_DATE_DIFF, $data );
	}

	private function hasAssociatedRevisionDiff( $id, array $status, int $associatedRev ): false|ReplicationError {
		if ( (int)$status['associated_revision'] === $associatedRev ) {
			return false;
		}

		$data = [
			'id' => $id,
			'rev_es' => $status['associated_revision'],
			'rev_store' => $associatedRev
		];

		return $this->newReplicationError( ReplicationError::TYPE_ASSOCIATED_REVISION_DIFF, $data );
	}

	private function hasMissingFileAttachment( array $params, WikiPage $subject ): false|ReplicationError {
		if ( !isset( $params[self::CHECK_MISSING_FILE_ATTACHMENT] ) || $params[self::CHECK_MISSING_FILE_ATTACHMENT] === false ) {
			return false;
		}

		if ( $subject->getNamespace() !== NS_FILE ) {
			return false;
		}

		$config = $this->store->getConnection( 'elastic' )->getConfig();

		if ( $config->dotGet( 'indexer.experimental.file.ingest', false ) === false ) {
			return false;
		}

		$property = new Property( '_FILE_ATTCH' );

		if ( $this->store->getPropertyValues( $subject, $property ) !== [] ) {
			return false;
		}

		$data = [ 'id' => $subject->getId() ];

		return $this->newReplicationError( ReplicationError::TYPE_FILE_ATTACHMENT_MISSING, $data );
	}

}
