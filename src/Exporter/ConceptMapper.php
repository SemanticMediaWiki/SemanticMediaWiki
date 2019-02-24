<?php

namespace SMW\Exporter;

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DIConcept;
use SMW\DIProperty;
use SMW\Exporter\Element\ExpResource;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMWDataItem as DataItem;
use SMWExpData as ExpData;
use SMWExporter as Exporter;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class ConceptMapper implements DataItemMapper {

	/**
	 * @var Exporter
	 */
	private $exporter;

	/**
	 * @since 2.4
	 *
	 * @param Exporter|null $exporter
	 */
	public function __construct( Exporter $exporter = null ) {
		$this->exporter = $exporter;

		if ( $this->exporter === null ) {
			$this->exporter = Exporter::getInstance();
		}
	}

	/**
	 * @since 2.4
	 *
	 * @param DataItem $dataItem
	 *
	 * @return boolean
	 */
	public function isMapperFor( DataItem $dataItem ) {
		return $dataItem instanceof DIConcept;
	}

	/**
	 * @since 2.4
	 *
	 * @param DIConcept $concept
	 *
	 * @return ExpData|null
	 */
	public function newElement( DataItem $concept ) {

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$concept
		);

		if ( !$dataValue->isValid() ) {
			return null;
		}

		$description = ApplicationFactory::getInstance()->newQueryParser()->getQueryDescription(
			$dataValue->getWikiValue()
		);

		$exact = true;

		$owlDescription = $this->newExpDataFromDescription(
			$description,
			$exact
		);

		if ( $owlDescription === false ) {
			$result = new ExpData(
				$this->exporter->getSpecialNsResource( 'owl', 'Thing' )
			);

			return $result;
		}

		if ( $exact ) {
			return $owlDescription;
		}

		$result = new ExpData(
			new ExpResource( '' )
		);

		$result->addPropertyObjectValue(
			$this->exporter->getSpecialNsResource( 'rdf', 'type' ),
			new ExpData( $this->exporter->getSpecialNsResource( 'owl', 'Class' ) )
		);

		$result->addPropertyObjectValue(
			$this->exporter->getSpecialNsResource( 'rdfs', 'subClassOf' ),
			$owlDescription
		);

		return $result;
	}

	/**
	 * @since 2.4
	 *
	 * @param Description $description
	 *
	 * @param string &$exact
	 *
	 * @return Element|false
	 */
	public function newExpDataFromDescription( Description $description, &$exact ) {

		if ( ( $description instanceof Conjunction ) || ( $description instanceof Disjunction ) ) {
			$expData = $this->mapConjunctionDisjunction( $description, $exact );
		} elseif ( $description instanceof ClassDescription ) {
			$expData = $this->mapClassDescription( $description, $exact );
		} elseif ( $description instanceof ConceptDescription ) {
			$expData = $this->mapConceptDescription( $description, $exact );
		} elseif ( $description instanceof SomeProperty ) {
			$expData = $this->mapSomeProperty( $description, $exact );
		} elseif ( $description instanceof ValueDescription ) {
			$expData = $this->mapValueDescription( $description, $exact );
		} elseif ( $description instanceof ThingDescription ) {
			$expData = false;
		} else {
			$expData = false;
			$exact = false;
		}

		return $expData;
	}

	private function mapValueDescription( ValueDescription $description, &$exact ) {

		if ( $description->getComparator() === SMW_CMP_EQ ) {
			$result = $this->exporter->newExpElement( $description->getDataItem() );
		} else {
			// OWL cannot represent <= and >= ...
			$exact = false;
			$result = false;
		}

		return $result;
	}

	private function mapConceptDescription( ConceptDescription $description, &$exact ) {

		$result = new ExpData(
			$this->exporter->getResourceElementForWikiPage( $description->getConcept() )
		);

		return $result;
	}

	private function mapSomeProperty( SomeProperty $description, &$exact ) {

		$result = new ExpData(
			new ExpResource( '' )
		);

		$result->addPropertyObjectValue(
			$this->exporter->getSpecialNsResource( 'rdf', 'type' ),
			new ExpData( $this->exporter->getSpecialNsResource( 'owl', 'Restriction' ) )
		);

		$property = $description->getProperty();

		if ( $property->isInverse() ) {
			$property = new DIProperty( $property->getKey() );
		}

		$result->addPropertyObjectValue(
			$this->exporter->getSpecialNsResource( 'owl', 'onProperty' ),
			new ExpData(
				$this->exporter->getResourceElementForProperty( $property )
			)
		);

		$subdata = $this->newExpDataFromDescription(
			$description->getDescription(),
			$exact
		);

		if ( ( $description->getDescription() instanceof ValueDescription ) &&
		     ( $description->getDescription()->getComparator() === SMW_CMP_EQ ) ) {
			$result->addPropertyObjectValue(
				$this->exporter->getSpecialNsResource( 'owl', 'hasValue' ),
				$subdata
			);
		} else {
			if ( $subdata === false ) {

				$owltype = $this->exporter->getOWLPropertyType(
					$description->getProperty()
				);

				if ( $owltype === Exporter::OWL_OBJECT_PROPERTY ) {
					$subdata = new ExpData(
						$this->exporter->getSpecialNsResource( 'owl', 'Thing' )
					);
				} elseif ( $owltype === Exporter::OWL_DATATYPE_PROPERTY ) {
					$subdata = new ExpData(
						$this->exporter->getSpecialNsResource( 'rdfs', 'Literal' )
					);
				} else { // no restrictions at all with annotation properties ...
					return new ExpData(
						$this->exporter->getSpecialNsResource( 'owl', 'Thing' )
					);
				}
			}

			$result->addPropertyObjectValue(
				$this->exporter->getSpecialNsResource( 'owl', 'someValuesFrom' ),
				$subdata
			);
		}

		return $result;
	}

	private function mapClassDescription( ClassDescription $description, &$exact ) {

		if ( count( $description->getCategories() ) == 1 ) { // single category
			$categories = $description->getCategories();
			$result = new ExpData(
				$this->exporter->getResourceElementForWikiPage( end( $categories ) )
			);
		} else { // disjunction of categories

			$result = new ExpData(
				new ExpResource( '' )
			);

			$elements = [];

			foreach ( $description->getCategories() as $cat ) {
				$elements[] = new ExpData(
					$this->exporter->getResourceElementForWikiPage( $cat )
				);
			}

			$result->addPropertyObjectValue(
				$this->exporter->getSpecialNsResource( 'owl', 'unionOf' ),
				ExpData::makeCollection( $elements )
			);
		}

		$result->addPropertyObjectValue(
			$this->exporter->getSpecialNsResource( 'rdf', 'type' ),
			new ExpData(
				$this->exporter->getSpecialNsResource( 'owl', 'Class' )
			)
		);

		return $result;
	}

	private function mapConjunctionDisjunction( Description $description, &$exact ) {

		$result = new ExpData(
			new ExpResource( '' )
		);

		$result->addPropertyObjectValue(
			$this->exporter->getSpecialNsResource( 'rdf', 'type' ),
			new ExpData(
				$this->exporter->getSpecialNsResource( 'owl', 'Class' )
			)
		);

		$elements = [];

		foreach ( $description->getDescriptions() as $subdesc ) {
			$element = $this->newExpDataFromDescription( $subdesc, $exact );

			if ( $element === false ) {
				$element = new ExpData(
					$this->exporter->getSpecialNsResource( 'owl', 'Thing' )
				);
			}

			$elements[] = $element;
		}

		$prop = $description instanceof Conjunction ? 'intersectionOf' : 'unionOf';

		$result->addPropertyObjectValue(
			$this->exporter->getSpecialNsResource( 'owl', $prop ),
			ExpData::makeCollection( $elements )
		);

		return $result;
	}

}
