<?php

namespace SMW\Exporter\ResourceBuilders;

use SMW\DIProperty;
use SMW\Exporter\ResourceBuilder;
use SMWDataItem as DataItem;
use SMWExpData as ExpData;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class DispatchingResourceBuilder implements ResourceBuilder {

	/**
	 * @var ResourceBuilder[]
	 */
	private $resourceBuilders = [];

	/**
	 * @var ResourceBuilder
	 */
	private $defaultResourceBuilder = null;

	/**
	 * @since 2.5
	 *
	 * @param DIProperty $property
	 *
	 * @return boolean
	 */
	public function isResourceBuilderFor( DIProperty $property ) {

		if ( $this->resourceBuilders === [] ) {
			$this->initResourceBuilders();
		}

		foreach ( $this->resourceBuilders as $resourceBuilder ) {
			if ( $resourceBuilder->isResourceBuilderFor( $property ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function addResourceValue( ExpData $expData, DIProperty $property, DataItem $dataItem ) {
		return $this->findResourceBuilder( $property )->addResourceValue( $expData, $property, $dataItem );
	}

	/**
	 * @since 2.5
	 *
	 * @param DIProperty $property
	 *
	 * @return ResourceBuilder $resourceBuilder
	 */
	public function findResourceBuilder( DIProperty $property ) {

		if ( $this->resourceBuilders === [] ) {
			$this->initResourceBuilders();
		}

		foreach ( $this->resourceBuilders as $resourceBuilder ) {
			if ( $resourceBuilder->isResourceBuilderFor( $property ) ) {
				return $resourceBuilder;
			}
		}

		return $this->defaultResourceBuilder;
	}

	/**
	 * @since 2.5
	 *
	 * @param ResourceBuilder $resourceBuilder
	 */
	public function addResourceBuilder( ResourceBuilder $resourceBuilder ) {
		$this->resourceBuilders[] = $resourceBuilder;
	}

	/**
	 * @since 2.5
	 *
	 * @param ResourceBuilder $defaultResourceBuilder
	 */
	public function addDefaultResourceBuilder( ResourceBuilder $defaultResourceBuilder ) {
		$this->defaultResourceBuilder = $defaultResourceBuilder;
	}

	private function initResourceBuilders() {

		$this->addResourceBuilder( new UniquenessConstraintPropertyValueResourceBuilder() );

		$sortPropertyValueResourceBuilder = new SortPropertyValueResourceBuilder();

		$sortPropertyValueResourceBuilder->enabledCollationField(
			( (int)$GLOBALS['smwgSparqlQFeatures'] & SMW_SPARQL_QF_COLLATION ) != 0
		);

		$this->addResourceBuilder( $sortPropertyValueResourceBuilder );

		$this->addResourceBuilder( new PropertyDescriptionValueResourceBuilder() );
		$this->addResourceBuilder( new PreferredPropertyLabelResourceBuilder() );

		$this->addResourceBuilder( new ExternalIdentifierPropertyValueResourceBuilder() );
		$this->addResourceBuilder( new KeywordPropertyValueResourceBuilder() );

		$this->addResourceBuilder( new MonolingualTextPropertyValueResourceBuilder() );
		$this->addResourceBuilder( new ConceptPropertyValueResourceBuilder() );

		$this->addResourceBuilder( new ImportFromPropertyValueResourceBuilder() );
		$this->addResourceBuilder( new RedirectPropertyValueResourceBuilder() );

		$this->addResourceBuilder( new AuxiliaryPropertyValueResourceBuilder() );
		$this->addResourceBuilder( new PredefinedPropertyValueResourceBuilder() );

		$this->addDefaultResourceBuilder( new PropertyValueResourceBuilder() );
	}

}
