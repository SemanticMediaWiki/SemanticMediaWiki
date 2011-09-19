<?php
/**
 * The class in this file acts as an in-memory cache for managing object ids in
 * SMWSQLStore2.
 *
 * @file
 * @ingroup SMWStore
 *
 * @author Markus Krötzsch
 */


/**
 * An in-memory cache for managing object ids in SMWSQLStore2.
 *
 * @since 1.6
 *
 * @ingroup SMWStore
 */
class SMWSqlStore2IdCache {
	protected $m_db;
	protected $m_data = array();

	/**
	 * Constructor.
	 *
	 * @param $tableName string name of the table that is buffered
	 *
	 * @param $db Database handler
	 */
	public function __construct( /*MW 1.17: DatabaseType*/ $db ) {
		$this->m_db = $db;
	}

	public function getId( $title, $namespace, $interwiki, $subobject ) {
		$hashKey = self::getHashKey( $title, $namespace, $interwiki, $subobject );
		if ( array_key_exists( $hashKey, $this->m_data ) ) {
			return $this->m_data[$hashKey];
		} else {
			$condition = array( 'smw_title' => $title,
					'smw_namespace' => $namespace,
					'smw_iw' => $interwiki,
					'smw_subobject' => $subobject );
			$row = $this->m_db->selectRow( 'smw_ids', 'smw_id', $condition, __METHOD__);

			$this->checkForSizeLimit();
			if ( $row !== false ) {
				$this->m_data[$hashKey] = $row->smw_id;
				return $row->smw_id;
			} else {
				$this->m_data[$hashKey] = 0;
				return 0;
			}
		}
	}

	public function getCachedId( $title, $namespace, $interwiki, $subobject ) {
		$hashKey = self::getHashKey( $title, $namespace, $interwiki, $subobject );
		if ( array_key_exists( $hashKey, $this->m_data ) ) {
			return $this->m_data[$hashKey];
		} else {
			return false;
		}
	}

	public function setId( $title, $namespace, $interwiki, $subobject, $id ) {
		$hashKey = self::getHashKey( $title, $namespace, $interwiki, $subobject );
		$this->checkForSizeLimit();
		$this->m_data[$hashKey] = $id;
		if ( $interwiki == SMW_SQL2_SMWREDIIW ) {
			$hashKey = self::getHashKey( $title, $namespace, '', $subobject );
			$this->m_data[$hashKey] = 0;
		} // could do this for $interwiki == '' too, but the SMW_SQL2_SMWREDIIW would be useless
	}

	public function deleteId( $title, $namespace, $interwiki, $subobject ) {
		$hashKey = self::getHashKey( $title, $namespace, $interwiki, $subobject );
		unset( $this->m_data[$hashKey] );
	}

	public function moveSubobjects( $oldtitle, $oldnamespace, $newtitle, $newnamespace ) {
		// Currently we have no way to change title and namespace across all entries.
		// Best we can do is clear the cache to avoid wrong hits:
		$this->clear();
	}

	public function clear() {
		$this->m_data = array();
	}

	protected function checkForSizeLimit() {
		if ( count( $this->m_data ) > 1000 ) {
			$this->clear();
		}
	}

	protected static function getHashKey( $title, $namespace, $interwiki, $subobject ) {
		return "$title#$namespace#$interwiki#$subobject";
	}

}