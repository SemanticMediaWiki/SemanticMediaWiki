<?php

namespace SMW\Indicator;

use SMW\Indicator\EntityExaminerIndicators\AssociatedRevisionMismatchEntityExaminerIndicatorProvider;
use SMW\Indicator\EntityExaminerIndicators\EntityExaminerCompositeIndicatorProvider;
use SMW\Indicator\EntityExaminerIndicators\CompositeIndicatorHtmlBuilder;
use SMW\Indicator\EntityExaminerIndicators\EntityExaminerDeferrableCompositeIndicatorProvider;
use SMW\Indicator\EntityExaminerIndicators\ConstraintErrorEntityExaminerDeferrableIndicatorProvider as ConstraintErrorEntityExaminerIndicatorProvider;
use SMW\Indicator\EntityExaminerIndicators\BlankEntityExaminerDeferrableIndicatorProvider;
use SMW\Indicator\IndicatorProviders\CompositeIndicatorProvider;
use SMW\Services\ServicesFactory;
use SMW\Utils\TemplateEngine;
use SMW\Store;
use SMW\EntityCache;
use SMW\MediaWiki\HookDispatcherAwareTrait;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class EntityExaminerIndicatorsFactory {

	use HookDispatcherAwareTrait;

	/**
	 * @since 3.2
	 *
	 * @param Store $store
	 *
	 * @return EntityExaminerCompositeIndicatorProvider
	 */
	public function newEntityExaminerIndicatorProvider( Store $store ) : EntityExaminerCompositeIndicatorProvider {

		$servicesFactory = ServicesFactory::getInstance();

		$indicatorProviders = [
			$this->newEntityExaminerDeferrableCompositeIndicatorProvider( $store )
		];

		if ( $this->hookDispatcher === null ) {
			$this->hookDispatcher = $servicesFactory->getHookDispatcher();
		}

		$this->hookDispatcher->onRegisterEntityExaminerIndicatorProviders( $store, $indicatorProviders );

		$entityExaminerIndicatorProvider = $this->newEntityExaminerCompositeIndicatorProvider(
			$indicatorProviders
		);

		return $entityExaminerIndicatorProvider;
	}

	/**
	 * @since 3.2
	 *
	 * @param Store $store
	 *
	 * @return AssociatedRevisionMismatchEntityExaminerIndicatorProvider
	 */
	public function newAssociatedRevisionMismatchEntityExaminerIndicatorProvider( Store $store ) : AssociatedRevisionMismatchEntityExaminerIndicatorProvider {

		$associatedRevisionMismatchEntityExaminerIndicatorProvider = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider(
			$store
		);

		$associatedRevisionMismatchEntityExaminerIndicatorProvider->setRevisionGuard(
			ServicesFactory::getInstance()->singleton( 'RevisionGuard' )
		);

		return $associatedRevisionMismatchEntityExaminerIndicatorProvider;
	}

	/**
	 * @since 3.2
	 *
	 * @param Store $store
	 * @param EntityCache $entityCache
	 *
	 * @return ConstraintErrorEntityExaminerIndicatorProvider
	 */
	public function newConstraintErrorEntityExaminerIndicatorProvider( Store $store, EntityCache $entityCache ) : ConstraintErrorEntityExaminerIndicatorProvider {

		$constraintErrorEntityExaminerIndicatorProvider = new ConstraintErrorEntityExaminerIndicatorProvider(
			$store,
			$entityCache
		);

		return $constraintErrorEntityExaminerIndicatorProvider;
	}

	/**
	 * @since 3.2
	 *
	 * @param Store $store
	 *
	 * @return EntityExaminerDeferrableCompositeIndicatorProvider
	 */
	public function newEntityExaminerDeferrableCompositeIndicatorProvider( Store $store ) : EntityExaminerDeferrableCompositeIndicatorProvider {
		$indicatorProviders = [];

		if ( $this->getServicesFactory()->getSettings()->get( 'smwgDetectOutdatedData' ) ) {
			$indicatorProviders[] = $this->newAssociatedRevisionMismatchEntityExaminerIndicatorProvider( $store );
		}

		$indicatorProviders[] = $this->newConstraintErrorProvider( $store );

		// Example of how to a add deferreable indicator; the `blank` can
		// be used as model for how to add other types of examinations
		// $indicatorProviders[] = new BlankEntityExaminerDeferrableIndicatorProvider()

		if ( $this->hookDispatcher === null ) {
			$this->hookDispatcher = $this->getServicesFactory()->getHookDispatcher();
		}

		$this->hookDispatcher->onRegisterEntityExaminerDeferrableIndicatorProviders( $store, $indicatorProviders );

		return new EntityExaminerDeferrableCompositeIndicatorProvider( $indicatorProviders );
	}

	private function newConstraintErrorProvider( Store $store ): ConstraintErrorEntityExaminerIndicatorProvider {
		$servicesFactory = $this->getServicesFactory();

		$constraintErrorEntityExaminerIndicatorProvider = $this->newConstraintErrorEntityExaminerIndicatorProvider(
			$store,
			$servicesFactory->singleton( 'EntityCache' )
		);

		$constraintErrorEntityExaminerIndicatorProvider->setConstraintErrorCheck(
			$servicesFactory->getSettings()->get( 'smwgCheckForConstraintErrors' )
		);

		return $constraintErrorEntityExaminerIndicatorProvider;
	}

	private function getServicesFactory(): ServicesFactory {
		return ServicesFactory::getInstance();
	}

	/**
	 * @since 3.2
	 *
	 * @param array $indicatorProviders
	 *
	 * @return EntityExaminerCompositeIndicatorProvider
	 */
	public function newEntityExaminerCompositeIndicatorProvider( array $indicatorProviders = [] ) : EntityExaminerCompositeIndicatorProvider {

		$compositeIndicatorHtmlBuilder = new CompositeIndicatorHtmlBuilder(
			new TemplateEngine()
		);

		$entityExaminerCompositeIndicatorProvider = new EntityExaminerCompositeIndicatorProvider(
			$compositeIndicatorHtmlBuilder,
			$indicatorProviders
		);

		return $entityExaminerCompositeIndicatorProvider;
	}

}
