<?php

namespace SMW\Query\Language;

use SMW\DIProperty;

/**
 * Description of a set of instances that have an attribute with some value
 * that fits another (sub)description.
 *
 * Corresponds to existential quantification ("SomeValuesFrom" restriction) on
 * properties in OWL. In conjunctive queries (OWL) and SPARQL (RDF), it is
 * represented by using variables in the object part of such properties.
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class SomeProperty extends Description {

	/**
	 * @var Description
	 */
	protected $description;

	/**
	 * @var DIProperty
	 */
	protected $property;

	/**
	 * @var integer|null
	 */
	protected $hierarchyDepth;

	/**
	 * @since 1.6
	 *
	 * @param DIProperty $property
	 * @param Description $description
	 */
	public function __construct( DIProperty $property, Description $description ) {
		$this->property = $property;
		$this->description = $description;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $hierarchyDepth
	 */
	public function setHierarchyDepth( $hierarchyDepth ) {

		if ( $hierarchyDepth > $GLOBALS['smwgQSubpropertyDepth'] ) {
			$hierarchyDepth = $GLOBALS['smwgQSubpropertyDepth'];
		}

		$this->hierarchyDepth = $hierarchyDepth;
	}

	/**
	 * @since 3.0
	 *
	 * @return integer|null
	 */
	public function getHierarchyDepth() {
		return $this->hierarchyDepth;
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

		$subDescription = $this->description;
		$property = $this->property->getSerialization();

		// Resolve property.chains and connect its members
		while ( $subDescription instanceof SomeProperty ) {
			$subDescription = $subDescription->getDescription();
			$subDescription->setMembership( $property );
		}

		// During a recursive chain use the hash from a stored
		// member to distinguish Foo.Bar.Foobar.Bam from Foo.Bar.Foobar
		$membership = $this->getMembership() . $subDescription->getMembership();

		return $this->fingerprint = 'S:' . md5( $property . '|' . $membership . '|' . $this->description->getFingerprint() . $this->hierarchyDepth );
	}

	/**
	 * @return DIProperty
	 */
	public function getProperty() {
		return $this->property;
	}

	/**
	 * @since 1.6
	 *
	 * @return Description
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @since 1.6
	 *
	 * @return string
	 */
	public function getQueryString( $asValue = false ) {
		$subDescription = $this->description;

		// Use the canonical label to ensure that conditions contain
		// language indep. references
		$propertyChainString = $this->property->getCanonicalLabel();
		$propertyname = $propertyChainString;
		$final = '';

		while ( ( $propertyname !== '' ) && ( $subDescription instanceof SomeProperty ) ) { // try to use property chain syntax
			$propertyname = $subDescription->getProperty()->getCanonicalLabel();

			if ( $propertyname !== '' ) {
				$propertyChainString .= '.' . $propertyname;
				$subDescription = $subDescription->getDescription();
			}
		}

		if ( $this->hierarchyDepth !== null ) {
			$final = '|+depth=' . $this->hierarchyDepth;
		}

		if ( $asValue ) {
			return '<q>[[' . $propertyChainString . '::' . $subDescription->getQueryString( true ) . $final . ']]</q>';
		}

		return '[[' . $propertyChainString . '::' . $subDescription->getQueryString( true ) . $final . ']]';
	}

	/**
	 * @since 1.6
	 *
	 * @return boolean
	 */
	public function isSingleton() {
		return false;
	}

	/**
	 * @since 1.6
	 *
	 * @return integer
	 */
	public function getSize() {
		return 1 + $this->getDescription()->getSize();
	}

	/**
	 * @since 1.6
	 *
	 * @return integer
	 */
	public function getDepth() {
		return 1 + $this->getDescription()->getDepth();
	}

	/**
	 * @since 1.6
	 *
	 * @return integer
	 */
	public function getQueryFeatures() {
		return SMW_PROPERTY_QUERY | $this->description->getQueryFeatures();
	}

	/**
	 * @since 1.6
	 *
	 * @return SomeProperty
	 */
	public function prune( &$maxsize, &$maxdepth, &$log ) {

		if ( ( $maxsize <= 0 ) || ( $maxdepth <= 0 ) ) {
			$log[] = $this->getQueryString();
			return new ThingDescription();
		}

		$maxsize--;
		$maxdepth--;

		$result = new SomeProperty(
			$this->property,
			$this->description->prune( $maxsize, $maxdepth, $log )
		);

		$result->setHierarchyDepth( $this->getHierarchyDepth() );
		$result->setPrintRequests( $this->getPrintRequests() );

		return $result;
	}

}
