<?php

namespace SMW\Query\Language;

/**
 * Description of a collection of many descriptions, all of which
 * must be satisfied (AND, conjunction).
 *
 * Corresponds to conjunction in OWL and SPARQL. Not available in RDFS.
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class Conjunction extends Description {

	/**
	 * @var Description[]
	 */
	protected $m_descriptions;

	public function __construct( array $descriptions = array() ) {
		$this->m_descriptions = $descriptions;
	}

	public function getDescriptions() {
		return $this->m_descriptions;
	}

	public function addDescription( Description $description ) {
		if ( ! ( $description instanceof ThingDescription ) ) {
			if ( $description instanceof Conjunction ) { // absorb sub-conjunctions
				foreach ( $description->getDescriptions() as $subdesc ) {
					$this->m_descriptions[] = $subdesc;
				}
			} else {
				$this->m_descriptions[] = $description;
			}

			// move print descriptions downwards
			///TODO: This may not be a good solution, since it does modify $description and since it does not react to future changes
			$this->m_printreqs = array_merge( $this->m_printreqs, $description->getPrintRequests() );
			$description->setPrintRequests( array() );
		}
	}

	public function getQueryString( $asvalue = false ) {
		$result = '';

		foreach ( $this->m_descriptions as $desc ) {
			$result .= ( $result ? ' ' : '' ) . $desc->getQueryString( false );
		}

		if ( $result === '' ) {
			return $asvalue ? '+' : '';
		}

		// <q> not needed for stand-alone conjunctions (AND binds stronger than OR)
		return $asvalue ? " <q>{$result}</q> " : $result;
	}

	public function isSingleton() {
		foreach ( $this->m_descriptions as $d ) {
			if ( $d->isSingleton() ) {
				return true;
			}
		}
		return false;
	}

	public function getSize() {
		$size = 0;

		foreach ( $this->m_descriptions as $desc ) {
			$size += $desc->getSize();
		}

		return $size;
	}

	public function getDepth() {
		$depth = 0;

		foreach ( $this->m_descriptions as $desc ) {
			$depth = max( $depth, $desc->getDepth() );
		}

		return $depth;
	}

	public function getQueryFeatures() {
		$result = SMW_CONJUNCTION_QUERY;

		foreach ( $this->m_descriptions as $desc ) {
			$result = $result | $desc->getQueryFeatures();
		}

		return $result;
	}

	public function prune( &$maxsize, &$maxdepth, &$log ) {
		if ( $maxsize <= 0 ) {
			$log[] = $this->getQueryString();
			return new ThingDescription();
		}

		$prunelog = array();
		$newdepth = $maxdepth;
		$result = new Conjunction();

		foreach ( $this->m_descriptions as $desc ) {
			$restdepth = $maxdepth;
			$result->addDescription( $desc->prune( $maxsize, $restdepth, $prunelog ) );
			$newdepth = min( $newdepth, $restdepth );
		}

		if ( count( $result->getDescriptions() ) > 0 ) {
			$log = array_merge( $log, $prunelog );
			$maxdepth = $newdepth;

			if ( count( $result->getDescriptions() ) == 1 ) { // simplify unary conjunctions!
				$descriptions = $result->getDescriptions();
				$result = array_shift( $descriptions );
			}

			$result->setPrintRequests( $this->getPrintRequests() );

			return $result;
		} else {
			$log[] = $this->getQueryString();

			$result = new ThingDescription();
			$result->setPrintRequests( $this->getPrintRequests() );

			return $result;
		}
	}

}
