<?php

namespace SMW\SQLStore;

use SMW\Store;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\ApplicationFactory;
use SMW\PropertyHierarchyLookup;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\ThingDescription;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class EmbeddedQueryDependencyListResolver {

	/**
	 * @var Store
	 */
	private $store = null;

	/**
	 * Specifies a list of property keys to be excluded from the detection
	 * process.
	 *
	 * @var array
	 */
	private $propertyDependencyDetectionBlacklist = array();

	/**
	 * @var PropertyHierarchyLookup
	 */
	private $propertyHierarchyLookup;

	/**
	 * @var QueryResult
	 */
	private $queryResult;

	/**
	 * @since 2.3
	 *
	 * @param Store $store
	 * @param PropertyHierarchyLookup $propertyHierarchyLookup
	 */
	public function __construct( Store $store, PropertyHierarchyLookup $propertyHierarchyLookup ) {
		$this->store = $store;
		$this->propertyHierarchyLookup = $propertyHierarchyLookup;
	}

	/**
	 * @since 2.3
	 *
	 * @param array $propertyDependencyDetectionBlacklist
	 */
	public function setPropertyDependencyDetectionBlacklist( array $propertyDependencyDetectionBlacklist ) {
		// Make sure that user defined properties are correctly normalized and flip
		// to build an index based map
		$this->propertyDependencyDetectionBlacklist = array_flip(
			str_replace( ' ', '_', $propertyDependencyDetectionBlacklist )
		);
	}

	/**
	 * @since 2.3
	 *
	 * @param $queryResult
	 */
	public function setQueryResult( $queryResult ) {
		$this->queryResult = $queryResult;
	}

	/**
	 * @since 2.3
	 *
	 * @return Query|null
	 */
	public function getQuery() {
		return $this->queryResult instanceof QueryResult ? $this->queryResult->getQuery() : null;
	}

	/**
	 * @since 2.3
	 *
	 * @return string|null
	 */
	public function getQueryId() {
		return $this->getQuery() !== null ? $this->getQuery()->getQueryId() : null;
	}

	/**
	 * @since 2.3
	 *
	 * @return DIWikiPage|null
	 */
	public function getSubject() {
		return $this->getQuery() !== null ? $this->getQuery()->getSubject() : null;
	}

	/**
	 * @since 2.3
	 *
	 * @return DIWikiPage[]|[]
	 */
	public function getQueryDependencySubjectList() {

		// Resolving dependencies for non-embedded queries or limit=0 (which only
		// links to Special:Ask via further results) is not required
		if ( $this->getSubject() === null || $this->getQuery()->getLimit() == 0 ) {
			return array();
		}

		$description = $this->getQuery()->getDescription();

		$dependencySubjectList = array(
			$this->getSubject(),
		);

		// Find entities described by the query
		$this->doResolveDependenciesFromDescription(
			$dependencySubjectList,
			$description
		);

		$this->doResolveDependenciesFromPrintRequest(
			$dependencySubjectList,
			$description->getPrintRequests()
		);

		$dependencySubjectList = array_merge(
			$dependencySubjectList,
			$this->queryResult->getResults()
		);

		$this->queryResult->reset();

		return $dependencySubjectList;
	}

	private function doResolveDependenciesFromDescription( &$subjects, $description ) {

		if ( $description instanceof ValueDescription && $description->getDataItem() instanceof DIWikiPage ) {
			$subjects[] = $description->getDataItem();
		}

		if ( $description instanceof ConceptDescription ) {
			$subjects[] = $description->getConcept();
			$this->doResolveDependenciesFromDescription(
				$subjects,
				$this->getConceptDescription( $description->getConcept() )
			);
		}

		if ( $description instanceof ClassDescription ) {
			foreach ( $description->getCategories() as $category ) {

				if ( $this->propertyHierarchyLookup->hasSubcategoryFor( $category ) ) {
					$this->doMatchSubcategory( $subjects, $category );
				}

				$subjects[] = $category;
			}
		}

		if ( $description instanceof SomeProperty ) {
			$this->doResolveDependenciesFromDescription( $subjects, $description->getDescription() );
			$this->doMatchProperty( $subjects, $description->getProperty() );
		}

		if ( $description instanceof Conjunction || $description instanceof Disjunction ) {
			foreach ( $description->getDescriptions() as $description ) {
				$this->doResolveDependenciesFromDescription( $subjects, $description );
			}
		}
	}

	private function doMatchProperty( &$subjects, DIProperty $property ) {

		if ( $property->isInverse() ) {
			$property = new DIProperty( $property->getKey() );
		}

		if ( $this->propertyHierarchyLookup->hasSubpropertyFor( $property ) ) {
			$this->doMatchSubproperty( $subjects, $property );
		}

		$key = str_replace( ' ', '_', $property->getKey() );

		if ( !isset( $this->propertyDependencyDetectionBlacklist[$key] ) ) {
			$subjects[] = $property->getDiWikiPage();
		}
	}

	private function doMatchSubcategory( &$subjects, DIWikiPage $category ) {

		$subcategories = $this->propertyHierarchyLookup->findSubcategoryListFor( $category );

		foreach ( $subcategories as $subcategory ) {

			if ( $this->propertyHierarchyLookup->hasSubcategoryFor( $subcategory ) ) {
				$this->doMatchSubcategory( $subjects, $subcategory );
			}

			$subjects[] = $subcategory;
		}
	}

	private function doMatchSubproperty( &$subjects, DIProperty $property ) {

		$subproperties = $this->propertyHierarchyLookup->findSubpropertListFor( $property );

		foreach ( $subproperties as $subproperty ) {
			$this->doMatchProperty( $subjects, new DIProperty( $subproperty->getDBKey() ) );
		}
	}

	private function doResolveDependenciesFromPrintRequest( &$subjects, array $printRequests ) {

		foreach ( $printRequests as $printRequest ) {
			$data = $printRequest->getData();

			if ( $data instanceof \SMWPropertyValue ) {

				$property = $data->getDataItem();

				if ( $property->isInverse() ) {
					$property = new DIProperty( $property->getKey() );
				}

				$subjects[] = $property->getDiWikiPage();
			}

			// Category
			if ( $data instanceof \Title ) {
				$subjects[] = DIWikiPage::newFromTitle( $data );
			}
		}
	}

	private function getConceptDescription( DIWikiPage $concept ) {

		$value = $this->store->getPropertyValues(
			$concept,
			new DIProperty( '_CONC' )
		);

		if ( $value === null || $value === array() ) {
			return new ThingDescription();
		}

		$value = end( $value );

		return ApplicationFactory::getInstance()->newQueryParser()->getQueryDescription(
			$value->getConceptQuery()
		);
	}

}
