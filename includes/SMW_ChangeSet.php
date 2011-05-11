<?php

/**
 * 
 * 
 * @since 1.6
 * 
 * @file SMW_ChangeSet.php
 * @ingroup SMW
 * 
 * @licence GNU GPL v3 or later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SMWChangeSet {
	
	/**
	 * The subject the changes apply to.
	 * 
	 * @var SMWDIWikiPage
	 */
	protected $subject;
	
	/**
	 * Object holding semantic data that got inserted.
	 * 
	 * @var SMWSemanticData
	 */
	protected $insertions;
	
	/**
	 * Object holding semantic data that got deleted.
	 * 
	 * @var SMWSemanticData
	 */	
	protected $deletions;
	
	/**
	 * List of all changes(, not including insertions and deletions).
	 * 
	 * @var SMWPropertyChanges
	 */
	protected $changes;
	
	/**
	 * Creates and returns a new SMWChangeSet from 2 SMWSemanticData objects.
	 * 
	 * @param SMWSemanticData $old
	 * @param SMWSemanticData $new
	 * 
	 * @return SMWChangeSet
	 */
	public static function newFromSemanticData( SMWSemanticData $old, SMWSemanticData $new ) {
		$subject = $old->getSubject();
		
		if ( $subject != $new->getSubject() ) {
			return new self( $subject );
		}
		
		$changes = new SMWPropertyChanges();
		$insertions = new SMWSemanticData( $subject );
		$deletions = new SMWSemanticData( $subject );
		
		$oldProperties = $old->getProperties();
		$newProperties = $new->getProperties();
		
		// Find the deletions.
		self::findSingleDirectionChanges( $deletions, $oldProperties, $newProperties );
		
		// Find the insertions.
		self::findSingleDirectionChanges( $insertions, $newProperties, $oldProperties );
		
		// TODO: find one-to-one changes 
		
		return new self( $subject, $changes, $insertions, $deletions );
	}
	
	/**
	 * Finds the inserts or deletions and adds them to the passed SMWSemanticData object.
	 * These values will also be removed from the first list of properties and their values,
	 * so it can be used for one-to-one change finding later on.  
	 * 
	 * @param SMWSemanticData $changeSet
	 * @param array $oldProperties
	 * @param array $newProperties
	 */
	protected static function findSingleDirectionChanges( SMWSemanticData &$changeSet, array &$oldProperties, array $newProperties ) {
		$deletionKeys = array();
		
		foreach ( $oldProperties as $propertyKey => /* SMWDIProperty */ $diProperty ) {
			if ( !array_key_exists( $propertyKey, $newProperties ) ) {
				foreach ( $old->getPropertyValues( $diProperty ) as /* SMWDataItem */ $dataItem ) {
					$changeSet->addPropertyObjectValue( $diProperty, $dataItem );
				}
				$deletionKeys[] = $propertyKey;
			}
		}
		
		foreach ( $deletionKeys as $key ) {
			unset( $oldProperties[$propertyKey] );
		}
		
		// TODO: handle props with multiple values (of which only some got inserted/removed) correctly
	}
	
	/**
	 * Create a new instance of a change set.
	 * 
	 * @param SMWDIWikiPage $subject
	 * @param array $changes Can be null
	 * @param SMWSemanticData $insertions Can be null
	 * @param SMWSemanticData $deletions Can be null
	 */
	public function __construct( SMWDIWikiPage $subject,
		/* SMWSemanticData */ SMWPropertyChanges $changes = null, $insertions = null, /* SMWSemanticData */ $deletions = null ) {
	
		$this->subject = $subject;
		$this->changes = is_null( $changes ) ? new SMWPropertyChanges() : $changes;
		$this->insertions = is_null( $insertions ) ? new SMWSemanticData( $subject ): $insertions;
		$this->deletions = is_null( $deletions ) ? new SMWSemanticData( $subject ): $deletions; 
	}
	
	/**
	 * Returns a list of ALL changes, including isertions and deletions.
	 * 
	 * @return array of 
	 */
	public function getAllChanges() {
		return array(); // TODO: implement
	}
	
	/**
	 * Returns the subject these changes apply to.
	 * 
	 * @return SMWDIWikiPage
	 */
	public function getSubject() {
		return $this->subject;		
	}
	
}
