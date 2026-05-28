<?php

namespace SMW\Indicator;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Html\TemplateParser;
use MediaWiki\MediaWikiServices;
use SMW\EntityCache;
use SMW\Indicator\EntityExaminerIndicators\AssociatedRevisionMismatchEntityExaminerIndicatorProvider;
use SMW\Indicator\EntityExaminerIndicators\CompositeIndicatorHtmlBuilder;
use SMW\Indicator\EntityExaminerIndicators\ConstraintErrorEntityExaminerDeferrableIndicatorProvider as ConstraintErrorEntityExaminerIndicatorProvider;
use SMW\Indicator\EntityExaminerIndicators\EntityExaminerCompositeIndicatorProvider;
use SMW\Indicator\EntityExaminerIndicators\EntityExaminerDeferrableCompositeIndicatorProvider;
use SMW\Services\ServicesFactory;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class EntityExaminerIndicatorsFactory {

	private ?HookContainer $hookContainer = null;

	/**
	 * @since 7.0.0
	 */
	public function setHookContainer( HookContainer $hookContainer ): void {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @since 3.2
	 *
	 * @param Store $store
	 *
	 * @return EntityExaminerCompositeIndicatorProvider
	 */
	public function newEntityExaminerIndicatorProvider( Store $store ): EntityExaminerCompositeIndicatorProvider {
		$indicatorProviders = [
			$this->newEntityExaminerDeferrableCompositeIndicatorProvider( $store )
		];

		if ( $this->hookContainer === null ) {
			$this->hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		}

		$this->hookContainer->run(
			'SMW::Indicator::EntityExaminer::RegisterIndicatorProviders',
			[ $store, &$indicatorProviders ]
		);

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
	public function newAssociatedRevisionMismatchEntityExaminerIndicatorProvider( Store $store ): AssociatedRevisionMismatchEntityExaminerIndicatorProvider {
		$associatedRevisionMismatchEntityExaminerIndicatorProvider = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider(
			$store,
			new TemplateParser( __DIR__ . '/../../templates/EntityExaminer' )
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
	public function newConstraintErrorEntityExaminerIndicatorProvider( Store $store, EntityCache $entityCache ): ConstraintErrorEntityExaminerIndicatorProvider {
		$constraintErrorEntityExaminerIndicatorProvider = new ConstraintErrorEntityExaminerIndicatorProvider(
			$store,
			$entityCache,
			new TemplateParser( __DIR__ . '/../../templates/EntityExaminer' )
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
	public function newEntityExaminerDeferrableCompositeIndicatorProvider( Store $store ): EntityExaminerDeferrableCompositeIndicatorProvider {
		$indicatorProviders = [];

		if ( $this->getServicesFactory()->getSettings()->get( 'smwgDetectOutdatedData' ) ) {
			$indicatorProviders[] = $this->newAssociatedRevisionMismatchEntityExaminerIndicatorProvider( $store );
		}

		$indicatorProviders[] = $this->newConstraintErrorProvider( $store );

		// Example of how to a add deferreable indicator; the `blank` can
		// be used as model for how to add other types of examinations
		// $indicatorProviders[] = new BlankEntityExaminerDeferrableIndicatorProvider()

		if ( $this->hookContainer === null ) {
			$this->hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		}

		$this->hookContainer->run(
			'SMW::Indicator::EntityExaminer::RegisterDeferrableIndicatorProviders',
			[ $store, &$indicatorProviders ]
		);

		return new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders,
			new TemplateParser( __DIR__ . '/../../templates/EntityExaminer' )
		);
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
	public function newEntityExaminerCompositeIndicatorProvider( array $indicatorProviders = [] ): EntityExaminerCompositeIndicatorProvider {
		$compositeIndicatorHtmlBuilder = new CompositeIndicatorHtmlBuilder(
			new TemplateParser( __DIR__ . '/../../templates/EntityExaminer' )
		);

		$entityExaminerCompositeIndicatorProvider = new EntityExaminerCompositeIndicatorProvider(
			$compositeIndicatorHtmlBuilder,
			$indicatorProviders
		);

		return $entityExaminerCompositeIndicatorProvider;
	}

}
