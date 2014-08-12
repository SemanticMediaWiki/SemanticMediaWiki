<?php

namespace SMW\Query\Language;

use SMW\DIProperty;

/**
 * Description of a set of instances that have an attribute with some value
 * that fits another (sub)description.
 *
 * Corresponds to existential quatification ("SomeValuesFrom" restriction) on
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

	public function __construct( DIProperty $property, Description $description ) {
		$this->property = $property;
		$this->description = $description;
	}

	/**
	 * @return DIProperty
	 */
	public function getProperty() {
		return $this->property;
	}

	/**
	 * @return Description
	 */
	public function getDescription() {
		return $this->description;
	}

	public function getQueryString( $asValue = false ) {
		$subDescription = $this->description;
		$propertyChainString = $this->property->getLabel();
		$propertyname = $propertyChainString;

		while ( ( $propertyname !== '' ) && ( $subDescription instanceof SomeProperty ) ) { // try to use property chain syntax
			$propertyname = $subDescription->getProperty()->getLabel();

			if ( $propertyname !== '' ) {
				$propertyChainString .= '.' . $propertyname;
				$subDescription = $subDescription->getDescription();
			}
		}

		if ( $asValue ) {
			return '<q>[[' . $propertyChainString . '::' . $subDescription->getQueryString( true ) . ']]</q>';
		}

		return '[[' . $propertyChainString . '::' . $subDescription->getQueryString( true ) . ']]';
	}

	public function isSingleton() {
		return false;
	}

	public function getSize() {
		return 1 + $this->getDescription()->getSize();
	}

	public function getDepth() {
		return 1 + $this->getDescription()->getDepth();
	}

	public function getQueryFeatures() {
		return SMW_PROPERTY_QUERY | $this->description->getQueryFeatures();
	}

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

		$result->setPrintRequests( $this->getPrintRequests() );

		return $result;
	}

}
