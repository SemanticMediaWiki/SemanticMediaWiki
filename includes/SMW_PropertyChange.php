<?php

/**
 * Represents a change to a semantic property.
 * 
 * @since 1.6
 * 
 * @file SMW_PropertyChange.php
 * @ingroup SMW
 * 
 * @licence GNU GPL v3 or later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SMWPropertyChange {

	const TYPE_INSERT = 0;
	const TYPE_UPDATE = 1;
	const TYPE_DELETE = 2;
	
	/**
	 * The old value.
	 * 
	 * @var SMWDataItem or null
	 */	
	protected $oldValue;
	
	/**
	 * The new value.
	 * 
	 * @var SMWDataItem or null
	 */
	protected $newValue;
	
	/**
	 * Create a new SMWPropertyChange.
	 * 
	 * @param SMWDataItem $oldValue
	 * @param SMWDataItem $newValue
	 */
	public function __construct( /* SMWDataItem */ $oldValue, /* SMWDataItem */ $newValue ) {
		$this->oldValue = $oldValue;
		$this->newValue = $newValue;
	}
	
	/**
	 * Retruns the old value, or null if there is none.
	 * 
	 * @return SMWDataItem or null
	 */
	public function getOldValue() {
		return $this->oldValue;
	}
	
	
	/**
	 * returns the new value, or null if there is none.
	 * 
	 * @return SMWDataItem or null
	 */	
	public function getNewValue() {
		return $this->newValue;
	}
	
	/**
	 * Returns the type of the change.
	 * 
	 * @return element of the SMWPropertyChange::TYPE_ enum
	 */	
	public function getType() {
		if ( is_null( $this->oldValue ) ) {
			return self::TYPE_INSERT;
		}
		else if ( is_null( $this->newValue ) ) {
			return self::TYPE_DELETE;
		}
		else {
			return self::TYPE_UPDATE;
		}
	}
	
}
	