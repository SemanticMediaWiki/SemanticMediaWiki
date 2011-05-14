<?php

/**
 * This class represents a semantic property diff between 2 versions
 * of a single page.
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
		self::findSingleDirectionChanges( $deletions, $oldProperties, $old, $newProperties );
		
		// Find the insertions.
		self::findSingleDirectionChanges( $insertions, $newProperties, $new, $oldProperties );
		
		foreach ( $oldProperties as $propertyKey => /* SMWDIProperty */ $diProperty ) {
			$oldDataItems = array();
			$newDataItems = array();
			
			// Populate the data item arrays using keys that are their hash, so matches can be found.
			// Note: this code assumes there are no duplicates.
			foreach ( $old->getPropertyValues( $diProperty ) as /* SMWDataItem */ $dataItem ) {
				$oldDataItems[$dataItem->getHash()] = $dataItem;
			}
			foreach ( $new->getPropertyValues( $diProperty ) as /* SMWDataItem */ $dataItem ) {
				$newDataItems[$dataItem->getHash()] = $dataItem;
			}			
			
			$foundMatches = array();
			
			// Find values that are both in the old and new version.
			foreach ( array_keys( $oldDataItems ) as $hash ) {
				if ( array_key_exists( $hash, $newDataItems ) ) {
					$foundMatches[] = $hash;
				}
			}
			
			// Remove the values occuring in both sets, so only changes remain.
			foreach ( $foundMatches as $foundMatch ) {
				unset( $oldDataItems[$foundMatch] );
				unset( $newDataItems[$foundMatch] );
			}
			
			// Find which group is biggest, so it's easy to loop over all values of the smallest.
			$oldIsBigger = count( $oldDataItems ) > count ( $newDataItems );
			$bigGroup = $oldIsBigger ? $oldDataItems : $newDataItems;
			$smallGroup = $oldIsBigger ? $newDataItems : $oldDataItems;
			
			// Add all one-to-one changes.
			while ( $dataItem = array_shift( $smallGroup ) ) {
				$changes->addPropertyObjectChange( $diProperty, new SMWPropertyChange( $dataItem, array_shift( $bigGroup ) ) );
			}
			
			// If the bigger group is not-equal to the smaller one, items will be left,
			// that are either insertions or deletions, depending on the group.
			if ( count( $bigGroup > 0 ) ) {
				$semanticData = $oldIsBigger ? $deletions : $insertions;
				
				foreach ( $bigGroup as /* SMWDataItem */ $dataItem ) {
					$semanticData->addPropertyObjectValue( $diProperty, $dataItem );
				}				
			}
		}
		
		return new self( $subject, $changes, $insertions, $deletions );
	}
	
	/**
	 * Finds the inserts or deletions and adds them to the passed SMWSemanticData object.
	 * These values will also be removed from the first list of properties and their values,
	 * so it can be used for one-to-one change finding later on.  
	 * 
	 * @param SMWSemanticData $changeSet
	 * @param array $oldProperties
	 * @param SMWSemanticData $oldData
	 * @param array $newProperties
	 */
	protected static function findSingleDirectionChanges( SMWSemanticData &$changeSet,
		array &$oldProperties, SMWSemanticData $oldData, array $newProperties ) {
			
		$deletionKeys = array();
		
		foreach ( $oldProperties as $propertyKey => /* SMWDIProperty */ $diProperty ) {
			if ( !array_key_exists( $propertyKey, $newProperties ) ) {
				foreach ( $oldData->getPropertyValues( $diProperty ) as /* SMWDataItem */ $dataItem ) {
					$changeSet->addPropertyObjectValue( $diProperty, $dataItem );
				}
				$deletionKeys[] = $propertyKey;
			}
		}
		
		foreach ( $deletionKeys as $key ) {
			unset( $oldProperties[$propertyKey] );
		}
	}
	
	/**
	 * Create a new instance of a change set.
	 * 
	 * @param SMWDIWikiPage $subject
	 * @param SMWPropertyChanges $changes Can be null
	 * @param SMWSemanticData $insertions Can be null
	 * @param SMWSemanticData $deletions Can be null
	 */
	public function __construct( SMWDIWikiPage $subject, /* SMWPropertyChanges */ $changes = null,
		/* SMWSemanticData */ $insertions = null, /* SMWSemanticData */ $deletions = null ) {
	
		$this->subject = $subject;
		$this->changes = is_null( $changes ) ? new SMWPropertyChanges() : $changes;
		$this->insertions = is_null( $insertions ) ? new SMWSemanticData( $subject ): $insertions;
		$this->deletions = is_null( $deletions ) ? new SMWSemanticData( $subject ): $deletions; 
	}
	
	/**
	 * Returns whether the set contains any changes.
	 * 
	 * @return boolean
	 */
	public function hasChanges() {
		return $this->changes->hasChanges()
			|| $this->insertions->hasVisibleProperties()
			|| $this->deletions->hasVisibleProperties();
	}
	
	/**
	 * Returns a SMWSemanticData object holding all inserted SMWDataItem objects.
	 * 
	 * @return SMWSemanticData
	 */
	public function getInsertions() {
		return $this->insertions;
	}
	
	/**
	 * Returns a SMWSemanticData object holding all deleted SMWDataItem objects.
	 * 
	 * @return SMWSemanticData
	 */
	public function getDeletions() {
		return $this->deletions;
	}
	
	/**
	 * Returns a SMWPropertyChanges object holding all SMWPropertyChange objects.
	 * 
	 * @return SMWPropertyChanges
	 */	
	public function getChanges() {
		return $this->changes;
	}
	
	/**
	 * Returns the subject these changes apply to.
	 * 
	 * @return SMWDIWikiPage
	 */
	public function getSubject() {
		return $this->subject;		
	}
	
	/**
	 * Adds a SMWPropertyChange to the set for the specified SMWDIProperty.
	 * 
	 * @param SMWDIProperty $property
	 * @param SMWPropertyChange $change
	 */
	public function addChange( SMWDIProperty $property, SMWPropertyChange $change ) {
		switch ( $change->getType() ) {
			case SMWPropertyChange::TYPE_UPDATE:
				$this->changes->addPropertyObjectChange( $property, $change );
				break;
			case SMWPropertyChange::TYPE_INSERT:
				$this->insertions->addPropertyObjectValue( $property, $change->getNewValue() );
				break;
			case SMWPropertyChange::TYPE_DELETE:
				$this->deletions->addPropertyObjectValue( $property, $change->getOldValue() );
				break;
		}
	}
	
	/**
	 * 
	 * 
	 * @return array of SMWDIProperty
	 */
	public function getAllProperties() {
		return array_merge(
			$this->getChanges()->getProperties(),
			$this->getInsertions()->getProperties(),
			$this->getDeletions()->getProperties()
		);
	}
	
	/**
	 * Returns a list of ALL changes, including isertions and deletions.
	 * 
	 * @param SMWDIProperty $proprety
	 * 
	 * @return array of SMWPropertyChange
	 */
	public function getAllPropertyChanges( SMWDIProperty $proprety ) {
		$changes = array();
		
		foreach ( $this->getAllProperties() as /* SMWDIProperty */ $property ) {
			foreach ( $this->changes->getPropertyChanges( $property ) as /* SMWPropertyChange */ $change ) {
				$changes[] = $change;
			}
			
			foreach ( $this->insertions->getPropertyValues( $property ) as /* SMWDataItem */ $dataItem ) {
				$changes[] = new SMWPropertyChange( null, $dataItem );
			}

			foreach ( $this->deletions->getPropertyValues( $property ) as /* SMWDataItem */ $dataItem ) {
				$changes[] = new SMWPropertyChange( $dataItem, null );
			}			
		}
		
		return $changes;
	}	
	
}
