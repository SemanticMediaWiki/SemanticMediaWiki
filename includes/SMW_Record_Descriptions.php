<?php
/**
 * This file contains derived classes for representing (query) descriptions in
 * that are used for SMW records
 * @file
 * @ingroup SMWQuery
 * @author Markus Krötzsch
 */


/**
 * A special type of conjunction used to express the conditions imposed on one
 * record object by means of specifying a (partial) value for it in a query.
 * This class mostly works like a normal conjunction but it gets properties
 * that encode the positions in the record, and which cannot be serialised
 * like normal property conditions.
 * @ingroup SMWQuery
 */
class SMWRecordDescription extends SMWConjunction {

	public function getQueryString( $asvalue = false ) {
		if ( !$asvalue ) return ''; // give up; SMWRecordDescriptions must always be values
		$fields = array();
		$maxpos = - 1;
		foreach ( $this->m_descriptions as $desc ) {
			if ( $desc instanceof SMWRecordFieldDescription ) { // everything else would be a bug; ignore
				$fields[$desc->getPosition()] = $desc->getDescription()->getQueryString( true );
				if ( $maxpos < $desc->getPosition() ) $maxpos = $desc->getPosition();
			}
		}
		if ( $maxpos < 0 ) {
			return '+';
		} else {
			$result = '';
			for ( $i = 0; $i <= $maxpos; $i++ ) {
				$result .= ( $result != '' ? '; ' : '' ) . ( array_key_exists( $i, $fields ) ? $fields[$i] : '?' );
			}
			return $result;
		}
	}

	public function prune( &$maxsize, &$maxdepth, &$log ) {
		if ( $maxsize <= 0 ) {
			$log[] = $this->getQueryString();
			return new SMWThingDescription();
		}
		$prunelog = array();
		$newdepth = $maxdepth;
		$result = new SMWRecordDescription();
		foreach ( $this->m_descriptions as $desc ) {
			$restdepth = $maxdepth;
			$result->addDescription( $desc->prune( $maxsize, $restdepth, $prunelog ) );
			$newdepth = min( $newdepth, $restdepth );
		}
		if ( count( $result->getDescriptions() ) > 0 ) {
			$log = array_merge( $log, $prunelog );
			$maxdepth = $newdepth;
			if ( count( $result->getDescriptions() ) == 1 ) { // simplify unary conjunctions!
				$result = array_shift( $result->getDescriptions() );
			}
			$result->setPrintRequests( $this->getPrintRequests() );
			return $result;
		} else {
			$log[] = $this->getQueryString();
			$result = new SMWThingDescription();
			$result->setPrintRequests( $this->getPrintRequests() );
			return $result;
		}
	}
}

class SMWRecordFieldDescription extends SMWSomeProperty {
	protected $m_position;

	public function __construct( $position, SMWDescription $description ) {
		parent::__construct( SMWPropertyValue::makeProperty( '_' . ( $position + 1 ) ), $description );
		$this->m_position = $position;
	}

	public function getPosition() {
		return $this->m_position;
	}

	public function getQueryString( $asvalue = false ) {
		if ( !$asvalue ) return '';  // give up; SMWRecordFieldDescriptions must always be values
		$prefix = '';
		for ( $i = 0; $i < $this->m_position; $i++ ) {
			$prefix .= '?; ';
		}
		return $prefix . $this->m_description->getQueryString( true );
	}

	public function prune( &$maxsize, &$maxdepth, &$log ) {
		if ( ( $maxsize <= 0 ) || ( $maxdepth <= 0 ) ) {
			$log[] = $this->getQueryString();
			return new SMWThingDescription();
		}
		$maxsize--;
		$maxdepth--;
		$result = new SMWRecordFieldDescription( $this->m_position, $this->m_description->prune( $maxsize, $maxdepth, $log ) );
		$result->setPrintRequests( $this->getPrintRequests() );
		return $result;
	}
}
