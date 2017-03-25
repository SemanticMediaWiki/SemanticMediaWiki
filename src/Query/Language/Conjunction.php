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
	protected $descriptions = [];

	/**
	 * @since 1.6
	 *
	 * @param array $descriptions
	 */
	public function __construct( array $descriptions = [] ) {
		foreach ( $descriptions as $description ) {
			$this->addDescription( $description );
		}
	}

	/**
	 * @see Description::getFingerprint
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getFingerprint() {

		if ( $this->fingerprint !== null ) {
			return $this->fingerprint;
		}

		$fingerprint = [];

		// Filter equal signatures
		foreach ( $this->descriptions as $description ) {
			$fingerprint[$description->getFingerprint()] = true;
		}

		// Sorting to generate a constant fingerprint independent of its
		// position within a conjunction ( [Foo]][[Bar]], [[Bar]][[Foo]])
		ksort( $fingerprint );

		return $this->fingerprint = 'C:' . md5( implode( '|', array_keys( $fingerprint ) ) );
	}

	public function getDescriptions() {
		return $this->descriptions;
	}

	public function addDescription( Description $description ) {

		$this->fingerprint = null;

		if ( ! ( $description instanceof ThingDescription ) ) {
			if ( $description instanceof Conjunction ) { // absorb sub-conjunctions
				foreach ( $description->getDescriptions() as $subdesc ) {
					$this->descriptions[$subdesc->getFingerprint()] = $subdesc;
				}
			} else {
				$this->descriptions[$description->getFingerprint()] = $description;
			}

			// move print descriptions downwards
			///TODO: This may not be a good solution, since it does modify $description and since it does not react to future changes
			$this->m_printreqs = array_merge( $this->m_printreqs, $description->getPrintRequests() );
			$description->setPrintRequests( [] );
		}

		$fingerprint = $this->getFingerprint();

		foreach ( $this->descriptions as $description ) {
			$description->setMembership( $fingerprint );
		}
	}

	public function getQueryString( $asvalue = false ) {
		$result = '';

		foreach ( $this->descriptions as $desc ) {
			$result .= ( $result ? ' ' : '' ) . $desc->getQueryString( false );
		}

		if ( $result === '' ) {
			return $asvalue ? '+' : '';
		}

		// <q> not needed for stand-alone conjunctions (AND binds stronger than OR)
		return $asvalue ? " <q>{$result}</q> " : $result;
	}

	public function isSingleton() {
		foreach ( $this->descriptions as $d ) {
			if ( $d->isSingleton() ) {
				return true;
			}
		}
		return false;
	}

	public function getSize() {
		$size = 0;

		foreach ( $this->descriptions as $desc ) {
			$size += $desc->getSize();
		}

		return $size;
	}

	public function getDepth() {
		$depth = 0;

		foreach ( $this->descriptions as $desc ) {
			$depth = max( $depth, $desc->getDepth() );
		}

		return $depth;
	}

	public function getQueryFeatures() {
		$result = SMW_CONJUNCTION_QUERY;

		foreach ( $this->descriptions as $desc ) {
			$result = $result | $desc->getQueryFeatures();
		}

		return $result;
	}

	public function prune( &$maxsize, &$maxdepth, &$log ) {
		if ( $maxsize <= 0 ) {
			$log[] = $this->getQueryString();
			return new ThingDescription();
		}

		$prunelog = [];
		$newdepth = $maxdepth;
		$result = new Conjunction();

		foreach ( $this->descriptions as $desc ) {
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
