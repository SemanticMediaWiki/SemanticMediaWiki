<?php

namespace SMW\SQLStore\Rebuilder;

use MediaWiki\Title\Title;
use SMW\MediaWiki\RevisionGuardAwareTrait;
use SMW\NamespaceExaminer;
use SMW\Query\Query;
use SMW\SQLStore\SQLStore;
use stdClass;
use Wikimedia\Rdbms\ResultWrapper;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class EntityValidator {

	use RevisionGuardAwareTrait;

	private array $propertyInvalidCharacterList = [];

	private array $propertyRetiredList = [];

	/**
	 * @var array|false
	 */
	private $namespaces = false;

	/**
	 * @since 3.1
	 */
	public function __construct(
		private SQLStore $store,
		private NamespaceExaminer $namespaceExaminer,
	) {
	}

	/**
	 * @since 2.3
	 *
	 * @param array $propertyInvalidCharacterList
	 */
	public function setPropertyInvalidCharacterList( array $propertyInvalidCharacterList ): void {
		$this->propertyInvalidCharacterList = $propertyInvalidCharacterList;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $propertyRetiredList
	 */
	public function setPropertyRetiredList( array $propertyRetiredList ): void {
		$this->propertyRetiredList = $propertyRetiredList;
	}

	/**
	 * @since 3.1
	 *
	 * @param array|false $namespaces
	 */
	public function setNamespaceRestriction( $namespaces ): void {
		$this->namespaces = $namespaces;
	}

	/**
	 * @since 3.1
	 *
	 * @param $row
	 *
	 * @return bool
	 */
	public function isSemanticEnabled( $row ) {
		return $this->namespaceExaminer->isSemanticEnabled( (int)$row->smw_namespace );
	}

	/**
	 * @since 3.1
	 *
	 * @param $row
	 *
	 * @return bool
	 */
	public function inNamespace( $row ): bool {
		if ( $this->namespaces === false ) {
			return true;
		}

		return in_array( (int)$row->smw_namespace, $this->namespaces );
	}

	/**
	 * @since 3.1
	 *
	 * @param $row
	 *
	 * @return bool
	 */
	public function isProperty( $row ): bool {
		return $row->smw_namespace === SMW_NS_PROPERTY && $row->smw_iw == '' && $row->smw_subobject == '';
	}

	/**
	 * @since 3.1
	 *
	 * @param $row
	 *
	 * @return bool
	 */
	public function isOutdated( $row ): bool {
		return $row->smw_iw == SMW_SQL3_SMWIW_OUTDATED || $row->smw_iw == SMW_SQL3_SMWDELETEIW;
	}

	/**
	 * @since 3.1
	 *
	 * @param $row
	 *
	 * @return bool
	 */
	public function isRedirect( $row ): bool {
		return $row->smw_iw == SMW_SQL3_SMWREDIIW;
	}

	/**
	 * @since 3.1
	 *
	 * @param $title
	 * @param $row
	 *
	 * @return bool
	 */
	public function isDetachedSubobject( $title, $row ): bool {
		if ( $row->smw_subobject === '' ) {
			return false;
		}

		// Has subobject or fragment but doesn't contain a `proptable` map
		// so it is conceived to represent something like `[[Has page::Foo#bar]]`
		if ( $row->smw_proptable_hash === null ) {
			return false;
		}

		// Is it a detached subobject? Meaning without a real page (for example
		// created by a page preview etc.)
		return $title !== null && !$title->exists();
	}

	/**
	 * @since 3.1
	 *
	 * @param $row
	 *
	 * @return bool
	 */
	public function isDetachedQueryRef( $row ): bool {
		if ( $row->smw_subobject === '' || $row->smw_proptable_hash !== null ) {
			return false;
		}

		// Any query reference without a `proptable` map is considered
		// detached (doesn't belong to any subject, or is outdated)
		return substr( $row->smw_subobject, 0, 6 ) === Query::ID_PREFIX;
	}

	/**
	 * @since 3.1
	 *
	 * @param $row
	 *
	 * @return bool
	 */
	public function isPlainObjectValue( $row ): bool {
		// A rogue title should never happen
		if ( $row->smw_title === '' && $row->smw_proptable_hash === null ) {
			return true;
		}

		return $row->smw_iw != SMW_SQL3_SMWDELETEIW &&
			$row->smw_iw != SMW_SQL3_SMWREDIIW &&
			$row->smw_iw != SMW_SQL3_SMWIW_OUTDATED &&
			// Leave any pre-defined property (_...) untouched
			$row->smw_title != '' &&
			$row->smw_title[0] != '_' &&
			// smw_proptable_hash === null means it is not a subject but an object value
			$row->smw_proptable_hash === null;
	}

	/**
	 * @since 3.1
	 *
	 * @param $row
	 *
	 * @return bool
	 */
	public function hasPropertyInvalidCharacter( $row ): bool {
		if ( $row->smw_namespace !== SMW_NS_PROPERTY ) {
			return false;
		}

		foreach ( $this->propertyInvalidCharacterList as $v ) {
			if ( strpos( $row->smw_title, $v ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @since 3.1
	 *
	 * @param $row
	 *
	 * @return bool
	 */
	public function isRetiredProperty( $row ): bool {
		if ( $row->smw_namespace !== SMW_NS_PROPERTY ) {
			return false;
		}

		foreach ( $this->propertyRetiredList as $v ) {

			// Check if both make a reference to a predefined representation
			// and if not, skip
			if ( $v[0] === '_' && $row->smw_title[0] !== '_' ) {
				continue;
			}

			if ( strpos( $row->smw_title, $v ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @since 3.1
	 *
	 * @param stdClass $row
	 *
	 * @return ResultWrapper
	 */
	public function findDuplicates( $row ) {
		$connection = $this->store->getConnection( 'mw.db' );

		// Use the sortkey (comparing the label and not the "_..." key) in order
		// to match possible duplicate properties by label (not by key)
		$duplicates = $connection->select(
			SQLStore::ID_TABLE,
			[
				'smw_id',
				'smw_title'
			],
			[
				"smw_id !=" . $connection->addQuotes( $row->smw_id ),
				"smw_sortkey =" . $connection->addQuotes( $row->smw_sortkey ),
				"smw_namespace =" . $row->smw_namespace,
				"smw_subobject =" . $connection->addQuotes( $row->smw_subobject )
			],
			__METHOD__,
			[
				'ORDER BY' => "smw_id ASC"
			]
		);

		return $duplicates;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 * @param $row
	 *
	 * @return bool
	 */
	public function hasLatestRevID( Title $title, $row = false ): bool {
		$latestRevID = $this->revisionGuard->getLatestRevID( $title );

		if ( $row !== false ) {
			return $latestRevID == $row->smw_rev;
		}

		$connection = $this->store->getConnection( 'mw.db' );

		$rev = $this->store->getObjectIds()->findAssociatedRev(
			$title->getDBKey(),
			$title->getNamespace(),
			$title->getInterwiki()
		);

		return $latestRevID == $rev;
	}

}
