<?php

namespace SMW\Exporter\ResourceBuilders;

use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\Export\ExpData;
use SMW\Exporter\ResourceBuilder;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class DispatchingResourceBuilder implements ResourceBuilder {

	/**
	 * @var ResourceBuilder[]
	 */
	private array $resourceBuilders = [];

	private ?ResourceBuilder $defaultResourceBuilder = null;

	/**
	 * @since 2.5
	 *
	 * @param Property $property
	 *
	 * @return bool
	 */
	public function isResourceBuilderFor( Property $property ): bool {
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
	public function addResourceValue( ExpData $expData, Property $property, DataItem $dataItem ) {
		return $this->findResourceBuilder( $property )->addResourceValue( $expData, $property, $dataItem );
	}

	/**
	 * @since 2.5
	 *
	 * @param Property $property
	 *
	 * @return ResourceBuilder $resourceBuilder
	 */
	public function findResourceBuilder( Property $property ) {
		if ( $this->resourceBuilders === [] ) {
			$this->initResourceBuilders();
		}

		foreach ( $this->resourceBuilders as $resourceBuilder ) {
			if ( $resourceBuilder->isResourceBuilderFor( $property ) ) {
				return $resourceBuilder;
			}
		}

		// @phan-suppress-next-line PhanTypeMismatchReturnNullable set in initResourceBuilders
		return $this->defaultResourceBuilder;
	}

	/**
	 * @since 2.5
	 *
	 * @param ResourceBuilder $resourceBuilder
	 */
	public function addResourceBuilder( ResourceBuilder $resourceBuilder ): void {
		$this->resourceBuilders[] = $resourceBuilder;
	}

	/**
	 * @since 2.5
	 *
	 * @param ResourceBuilder $defaultResourceBuilder
	 */
	public function addDefaultResourceBuilder( ResourceBuilder $defaultResourceBuilder ): void {
		$this->defaultResourceBuilder = $defaultResourceBuilder;
	}

	private function initResourceBuilders(): void {
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
