<?php

namespace SMW\Elastic\Indexer\Replication;

use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Exception;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
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
	 * @var
	 */
	private $replicationStatusResponse = [];

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
	 *
	 * @param WikiPage $subject
	 * @param array $params
	 *
	 * @return ReplicationError|null
	 */
	public function check( WikiPage $subject, array $params = [] ): ?ReplicationError {
		$id = $this->store->getObjectIds()->getSMWPageID(
			$subject->getDBKey(),
			$subject->getNamespace(),
			$subject->getInterwiki(),
			$subject->getSubobjectname()
		);

		$subject->setId( $id );

		$replicationError = $this->isMissingDocument( $params, $id );

		if ( $replicationError instanceof ReplicationError ) {
			return $replicationError;
		}

		$this->replicationStatusResponse = $this->runCheck(
			'modification_date_associated_revision',
			$id
		);

		if ( $this->replicationStatusResponse instanceof ReplicationError ) {
			return $this->replicationStatusResponse;
		}

		// What is stored in the DB
		$dataItems = $this->store->getPropertyValues(
			$subject,
			new Property( '_MDAT' )
		);

		return $this->findError( $subject, $params, $dataItems, $id );
	}

	private function findError( WikiPage $subject, array $params, $dataItems, $id ): ?ReplicationError {
		$replicationError = $this->hasMissingModificationDate( $dataItems, $id );

		if ( $replicationError instanceof ReplicationError ) {
			return $replicationError;
		}

		$replicationError = $this->hasModificationDateDiff( $dataItems, $id );

		if ( $replicationError instanceof ReplicationError ) {
			return $replicationError;
		}

		$replicationError = $this->hasAssociatedRevisionDiff( $id );

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
		} catch ( BadRequest400Exception $e ) {
			return $this->newReplicationError( ReplicationError::TYPE_EXCEPTION, [ 'exception_error' => 'BadRequest400Exception' ] );
		} catch ( Exception $e ) {
			return $this->newReplicationError( ReplicationError::TYPE_EXCEPTION, [ 'exception_error' => $e->getMessage() ] );
		}

		return $replicationStatusResponse;
	}

	private function hasMissingModificationDate( $dataItems, $id ) {
		if ( $this->replicationStatusResponse['modification_date'] !== false && $dataItems !== [] ) {
			return false;
		}

		return $this->newReplicationError( ReplicationError::TYPE_MODIFICATION_DATE_MISSING, [ 'id' => $id ] );
	}

	private function hasModificationDateDiff( $dataItems, $id ) {
		$dataItem = end( $dataItems );

		if ( $dataItem->equals( $this->replicationStatusResponse['modification_date'] ) ) {
			return false;
		}

		$data = [
			'id' => $id,
			'time_es' => $this->replicationStatusResponse['modification_date']->asDateTime()->format( 'Y-m-d H:i:s' ),
			'time_store' => $dataItem->asDateTime()->format( 'Y-m-d H:i:s' )
		];

		return $this->newReplicationError( ReplicationError::TYPE_MODIFICATION_DATE_DIFF, $data );
	}

	private function hasAssociatedRevisionDiff( $id ) {
		$associatedRev = $this->store->getObjectIds()->findAssociatedRev(
			$id
		);

		if ( $this->replicationStatusResponse['associated_revision'] == $associatedRev ) {
			return false;
		}

		$data = [
			'id' => $id,
			'rev_es' => $this->replicationStatusResponse['associated_revision'],
			'rev_store' => $associatedRev
		];

		return $this->newReplicationError( ReplicationError::TYPE_ASSOCIATED_REVISION_DIFF, $data );
	}

	private function hasMissingFileAttachment( array $params, WikiPage $subject ) {
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
