<?php

namespace SMW\SQLStore\Rebuilder;

use SMW\MediaWiki\RevisionGuardAwareTrait;
use SMW\NamespaceExaminer;
use SMW\SQLStore\SQLStore;
use Title;

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

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @var array
	 */
	private $propertyInvalidCharacterList = [];

	/**
	 * @var array
	 */
	private $propertyRetiredList = [];

	/**
	 * @var array|false
	 */
	private $namespaces = false;

	/**
	 * @since 3.1
	 *
	 * @param SQLStore $store
	 * @param NamespaceExaminer $namespaceExaminer
	 */
	public function __construct( SQLStore $store, NamespaceExaminer $namespaceExaminer ) {
		$this->store = $store;
		$this->namespaceExaminer = $namespaceExaminer;
	}

	/**
	 * @since 2.3
	 *
	 * @param array $propertyInvalidCharacterList
	 */
	public function setPropertyInvalidCharacterList( array $propertyInvalidCharacterList ) {
		$this->propertyInvalidCharacterList = $propertyInvalidCharacterList;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $propertyRetiredList
	 */
	public function setPropertyRetiredList( array $propertyRetiredList ) {
		$this->propertyRetiredList = $propertyRetiredList;
	}

	/**
	 * @since 3.1
	 *
	 * @param array|false $namespaces
	 */
	public function setNamespaceRestriction( $namespaces ) {
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
	public function inNamespace( $row ) {
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
	public function isProperty( $row ) {
		return $row->smw_namespace === SMW_NS_PROPERTY && $row->smw_iw == '' && $row->smw_subobject == '';
	}

	/**
	 * @since 3.1
	 *
	 * @param $row
	 *
	 * @return bool
	 */
	public function isOutdated( $row ) {
		return $row->smw_iw == SMW_SQL3_SMWIW_OUTDATED || $row->smw_iw == SMW_SQL3_SMWDELETEIW;
	}

	/**
	 * @since 3.1
	 *
	 * @param $row
	 *
	 * @return bool
	 */
	public function isRedirect( $row ) {
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
	public function isDetachedSubobject( $title, $row ) {
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
	public function isDetachedQueryRef( $row ) {
		if ( $row->smw_subobject === '' || $row->smw_proptable_hash !== null ) {
			return false;
		}

		// Any query reference without a `proptable` map is considered
		// detached (doesn't belong to any subject, or is outdated)
		return substr( $row->smw_subobject, 0, 6 ) === \SMWQuery::ID_PREFIX;
	}

	/**
	 * @since 3.1
	 *
	 * @param $row
	 *
	 * @return bool
	 */
	public function isPlainObjectValue( $row ) {
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
	public function hasPropertyInvalidCharacter( $row ) {
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
	public function isRetiredProperty( $row ) {
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
	 * @param $row
	 *
	 * @return
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
	public function hasLatestRevID( Title $title, $row = false ) {
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
