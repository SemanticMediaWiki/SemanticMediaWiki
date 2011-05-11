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
	 * 
	 * @var SMWDataItem
	 */	
	protected $oldValue;
	
	/**
	 * 
	 * @var SMWDataItem
	 */
	protected $newValue;
	
	public function __construct( SMWDataItem $oldValue, SMWDataItem $newValue ) {
		$this->oldValue = $oldValue;
		$this->newValue = $newValue;
	}
	
	public function getOldValue() {
		return $this->oldValue;
	}
	
	public function getNewValue() {
		return $this->newValue;
	}
	
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
	