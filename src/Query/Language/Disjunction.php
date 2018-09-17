<?php

namespace SMW\Query\Language;

/**
 * Description of a collection of many descriptions, at least one of which
 * must be satisfied (OR, disjunction).
 *
 * Corresponds to disjunction in OWL and SPARQL. Not available in RDFS.
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class Disjunction extends Description {

	/**
	 * @var Description[]
	 */
	private $descriptions = [];

	/**
	 * contains a single class description if any such disjunct was given;
	 * disjunctive classes are aggregated therei
	 * n
	 * @var null|ClassDescription
	 */
	private $classDescription = null;

	/**
	 * Used if disjunction is trivially true already
	 *
	 * @var boolean
	 */
	private $isTrue = false;

	public function __construct( array $descriptions = [] ) {
		foreach ( $descriptions as $desc ) {
			$this->addDescription( $desc );
		}
	}

	/**
	 * @see Description::getFingerprint
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getFingerprint() {

		// Avoid a recursive tree
		if ( $this->fingerprint !== null ) {
			return $this->fingerprint;
		}

		$fingerprint = [];

		foreach ( $this->descriptions as $description ) {
			$fingerprint[$description->getFingerprint()] = true;
		}

		ksort( $fingerprint );

		return $this->fingerprint = 'D:' . md5( implode( '|', array_keys( $fingerprint ) ) );
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $hierarchyDepth
	 */
	public function setHierarchyDepth( $hierarchyDepth ) {

		$this->fingerprint = null;

		if ( $this->classDescription !== null ) {
			$this->classDescription->setHierarchyDepth( $hierarchyDepth );
		}

		foreach ( $this->descriptions as $key => $description ) {
			if ( $description instanceof SomeProperty ) {
				$description->setHierarchyDepth( $hierarchyDepth );
			}
		}
	}

	public function getDescriptions() {
		return $this->descriptions;
	}

	public function addDescription( Description $description ) {

		$this->fingerprint = null;
		$fingerprint = $description->getFingerprint();

		if ( $description instanceof ThingDescription ) {
			$this->isTrue = true;
			$this->descriptions = []; // no conditions any more
			$this->classDescription = null;
		}

		if ( !$this->isTrue ) {
			 // Combine class descriptions only when those describe the same state
			if ( $description instanceof ClassDescription ) {
				if ( is_null( $this->classDescription ) ) { // first class description
					$this->classDescription = $description;
					$this->descriptions[$description->getFingerprint()] = $description;
				} elseif ( $this->classDescription->isMergableDescription( $description ) ) {
					$this->classDescription->addDescription( $description );
				} else {
					$this->descriptions[$description->getFingerprint()] = $description;
				}
			} elseif ( $description instanceof Disjunction ) { // absorb sub-disjunctions
				foreach ( $description->getDescriptions() as $subdesc ) {
					$this->descriptions[$subdesc->getFingerprint()] = $subdesc;
				}
			// } elseif ($description instanceof SMWSomeProperty) {
			   ///TODO: use subdisjunct. for multiple SMWSomeProperty descs with same property
			} else {
				$this->descriptions[$fingerprint] = $description;
			}
		}

		// move print descriptions downwards
		///TODO: This may not be a good solution, since it does modify $description and since it does not react to future cahges
		$this->m_printreqs = array_merge( $this->m_printreqs, $description->getPrintRequests() );
		$description->setPrintRequests( [] );
	}

	public function getQueryString( $asValue = false ) {

		if ( $this->isTrue ) {
			return '+';
		}

		$result = '';
		$sep = $asValue ? '||':' OR ';

		foreach ( $this->descriptions as $desc ) {
			$subdesc = $desc->getQueryString( $asValue );

			if ( $desc instanceof SomeProperty ) { // enclose in <q> for parsing
				if ( $asValue ) {
					$subdesc = ' <q>[[' . $subdesc . ']]</q> ';
				} else {
					$subdesc = ' <q>' . $subdesc . '</q> ';
				}
			}

			$result .= ( $result ? $sep:'' ) . $subdesc;
		}

		if ( $asValue ) {
			return $result;
		}

		return ' <q>' . $result . '</q> ';
	}

	public function isSingleton() {
		/// NOTE: this neglects the unimportant case where several disjuncts describe the same object.
		if ( count( $this->descriptions ) != 1 ) {
			return false;
		}

		return $this->descriptions[0]->isSingleton();
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
		$result = SMW_DISJUNCTION_QUERY;

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
		$result = new Disjunction();

		foreach ( $this->descriptions as $desc ) {
			$restdepth = $maxdepth;
			$result->addDescription( $desc->prune( $maxsize, $restdepth, $prunelog ) );
			$newdepth = min( $newdepth, $restdepth );
		}

		if ( count( $result->getDescriptions() ) > 0 ) {
			$log = array_merge( $log, $prunelog );
			$maxdepth = $newdepth;

			if ( count( $result->getDescriptions() ) == 1 ) { // simplify unary disjunctions!
				$descriptions = $result->getDescriptions();
				$result = array_shift( $descriptions );
			}

			$result->setPrintRequests( $this->getPrintRequests() );

			return $result;
		}

		$log[] = $this->getQueryString();

		$result = new ThingDescription();
		$result->setPrintRequests( $this->getPrintRequests() );

		return $result;
	}

}
