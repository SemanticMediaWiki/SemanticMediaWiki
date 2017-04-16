<?php

namespace SMW\SQLStore\QueryDependency;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\PropertyHierarchyLookup;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\Store;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class QueryResultDependencyListResolver {

	/**
	 * @var PropertyHierarchyLookup
	 */
	private $propertyHierarchyLookup;

	/**
	 * Specifies a list of property keys to be excluded from the detection
	 * process.
	 *
	 * @var array
	 */
	private $propertyDependencyExemptionlist = array();

	/**
	 * @since 2.3
	 *
	 * @param $queryResult Can be a string for when format=Debug
	 * @param PropertyHierarchyLookup $propertyHierarchyLookup
	 */
	public function __construct( PropertyHierarchyLookup $propertyHierarchyLookup ) {
		$this->propertyHierarchyLookup = $propertyHierarchyLookup;
	}

	/**
	 * @since 2.3
	 *
	 * @param array $propertyDependencyExemptionlist
	 */
	public function setPropertyDependencyExemptionlist( array $propertyDependencyExemptionlist ) {
		// Make sure that user defined properties are correctly normalized and flip
		// to build an index based map
		$this->propertyDependencyExemptionlist = array_flip(
			str_replace( ' ', '_', $propertyDependencyExemptionlist )
		);
	}

	/**
	 * At the point where the QueryResult instantiates results by means of the
	 * ResultArray, record the objects with the help of the EntityListAccumulator.
	 * Processing is depending and various factors which could be to early with
	 * the row instance is not yet being resolved.
	 *
	 * QueryDependencyLinksStore::updateDependencyList is executed in deferred
	 * mode therefore allows a "late" access to track dependencies of column/row
	 * entities without having to resolve the QueryResult object on its own, see
	 * ResultArray::getNextDataValue/ResultArray::getNextDataItem.
	 *
	 * @since 2.4
	 *
	 * @param QueryResult|string $queryResult
	 *
	 * @return DIWikiPage[]|[]
	 */
	public function getDependencyListByLateRetrievalFrom( $queryResult ) {

		if ( !$this->canResolve( $queryResult ) ) {
			return array();
		}

		$inMemoryEntityProcessList = $queryResult->getInMemoryEntityProcessList();

		$dependencyList = $inMemoryEntityProcessList->getEntityList();
		$inMemoryEntityProcessList->prune();

		return $dependencyList;
	}

	/**
	 * @since 2.3
	 *
	 * @param QueryResult|string $queryResult
	 *
	 * @return DIWikiPage[]|[]
	 */
	public function getDependencyListFrom( $queryResult ) {

		if ( !$this->canResolve( $queryResult ) ) {
			return array();
		}

		$description = $queryResult->getQuery()->getDescription();

		$dependencySubjectList = array(
			$queryResult->getQuery()->getContextPage()
		);

		// Find entities described by the query
		$this->doResolveDependenciesFromDescription(
			$dependencySubjectList,
			$queryResult->getStore(),
			$description
		);

		$this->doResolveDependenciesFromPrintRequest(
			$dependencySubjectList,
			$description->getPrintRequests()
		);

		$dependencySubjectList = array_merge(
			$dependencySubjectList,
			$queryResult->getResults()
		);

		$queryResult->reset();

		return $dependencySubjectList;
	}

	/**
	 * Resolving dependencies for non-embedded queries or limit=0 (which only
	 * links to Special:Ask via further results) is not required
	 */
	private function canResolve( $queryResult ) {
		return $queryResult instanceof QueryResult && $queryResult->getQuery() !== null && $queryResult->getQuery()->getContextPage() !== null && $queryResult->getQuery()->getLimit() > 0;
	}

	private function doResolveDependenciesFromDescription( &$subjects, $store, $description ) {

		// Ignore entities that use a comparator other than SMW_CMP_EQ
		// [[Has page::~Foo*]] or similar is going to be ignored
		if ( $description instanceof ValueDescription &&
			$description->getDataItem() instanceof DIWikiPage &&
			$description->getComparator() === SMW_CMP_EQ ) {
			$subjects[] = $description->getDataItem();
		}

		if ( $description instanceof ConceptDescription && $concept = $description->getConcept() ) {
			if ( $concept === null || !isset( $subjects[$concept->getHash()] ) ) {
				$subjects[$concept->getHash()] = $concept;
				$this->doResolveDependenciesFromDescription(
					$subjects,
					$store,
					$this->getConceptDescription( $store, $concept )
				);
			}
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
			$this->doResolveDependenciesFromDescription( $subjects, $store, $description->getDescription() );
			$this->doMatchProperty( $subjects, $description->getProperty() );
		}

		if ( $description instanceof Conjunction || $description instanceof Disjunction ) {
			foreach ( $description->getDescriptions() as $description ) {
				$this->doResolveDependenciesFromDescription( $subjects, $store, $description );
			}
		}
	}

	private function doMatchProperty( &$subjects, DIProperty $property ) {

		if ( $property->isInverse() ) {
			$property = new DIProperty( $property->getKey() );
		}

		$subject = $property->getCanonicalDiWikiPage();

		if ( $this->propertyHierarchyLookup->hasSubpropertyFor( $property ) ) {
			$this->doMatchSubproperty( $subjects, $subject, $property );
		}

		// Use the key here do match against pre-defined properties (e.g. _MDAT)
		$key = str_replace( ' ', '_', $property->getKey() );

		if ( !isset( $this->propertyDependencyExemptionlist[$key] ) ) {
			$subjects[$subject->getHash()] = $subject;
		}
	}

	private function doMatchSubcategory( &$subjects, DIWikiPage $category ) {

		$hash = $category->getHash();
		$subcategories = array();

		// #1713
		// Safeguard against a possible category (or redirect thereof) to point
		// to itself by relying on tracking the hash of already inserted objects
		if ( !isset( $subjects[$hash] ) ) {
			$subcategories = $this->propertyHierarchyLookup->findSubcategoryListFor( $category );
		}

		foreach ( $subcategories as $subcategory ) {

			$subjects[$subcategory->getHash()] = $subcategory;

			if ( $this->propertyHierarchyLookup->hasSubcategoryFor( $subcategory ) ) {
				$this->doMatchSubcategory( $subjects, $subcategory );
			}
		}
	}

	private function doMatchSubproperty( &$subjects, $subject, DIProperty $property ) {

		$subproperties = array();

		// Using the DBKey as short-cut, as we don't expect to match sub-properties for
		// pre-defined properties instead it should be sufficient for user-defined
		// properties to rely on the normalized DBKey (e.g Has_page)
		if (
			!isset( $subjects[$subject->getHash()] ) &&
			!isset( $this->propertyDependencyExemptionlist[$subject->getDBKey()] ) ) {
			$subproperties = $this->propertyHierarchyLookup->findSubpropertListFor( $property );
		}

		foreach ( $subproperties as $subproperty ) {

			if ( isset( $this->propertyDependencyExemptionlist[$subproperty->getDBKey()] ) ) {
				continue;
			}

			$subjects[$subproperty->getHash()] = $subproperty;
			$this->doMatchProperty( $subjects, new DIProperty( $subproperty->getDBKey() ) );
		}
	}

	private function doResolveDependenciesFromPrintRequest( &$subjects, array $printRequests ) {

		foreach ( $printRequests as $printRequest ) {
			$data = $printRequest->getData();

			if ( $data instanceof \SMWPropertyValue ) {
				$subjects[] = $data->getDataItem()->getCanonicalDiWikiPage();
			}

			// Category
			if ( $data instanceof \Title ) {
				$subjects[] = DIWikiPage::newFromTitle( $data );
			}
		}
	}

	private function getConceptDescription( $store, DIWikiPage $concept ) {

		$value = $store->getPropertyValues(
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
