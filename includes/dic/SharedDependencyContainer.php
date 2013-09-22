<?php

namespace SMW;

/**
 * Extends the BaseDependencyContainer
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Extends the BaseDependencyContainer to provide general purpose dependency
 * object definitions
 *
 * @ingroup DependencyContainer
 */
class SharedDependencyContainer extends BaseDependencyContainer {

	/**
	 * @since  1.9
	 */
	public function __construct() {
		$this->load();
	}

	/**
	 * Load object definitions in advance
	 *
	 * @since  1.9
	 */
	public function load() {

		/**
		 * Settings object definition
		 *
		 * @since  1.9
		 *
		 * @return Settings
		 */
		$this->registerObject( 'Settings', function () {
			return Settings::newFromGlobals();
		}, DependencyObject::SCOPE_SINGLETON );

		/**
		 * Store object definition
		 *
		 * @since  1.9
		 *
		 * @return Store
		 */
		$this->registerObject( 'Store', function ( DependencyBuilder $builder ) {
			return StoreFactory::getStore( $builder->newObject( 'Settings' )->get( 'smwgDefaultStore' ) );
		}, DependencyObject::SCOPE_SINGLETON );

		/**
		 * CacheHandler object definition
		 *
		 * @since  1.9
		 *
		 * @return CacheHandler
		 */
		$this->registerObject( 'CacheHandler', function ( DependencyBuilder $builder ) {
			return CacheHandler::newFromId( $builder->newObject( 'Settings' )->get( 'smwgCacheType' ) );
		}, DependencyObject::SCOPE_SINGLETON );

	}

	/**
	 * Load object definitions on request
	 *
	 * @see  DependencyContainer::loadObjects
	 *
	 * @since  1.9
	 *
	 * @return array
	 */
	public function loadObjects() {
		return array(
			'ParserData'            => $this->getParserData(),
			'NamespaceExaminer'     => $this->getNamespaceExaminer(),
			'UpdateObserver'        => $this->getUpdateObserver(),
			'BasePropertyAnnotator' => $this->getBasePropertyAnnotator(),

			'ContentProcessor' => function ( DependencyBuilder $builder ) {
					return new ParserTextProcessor(
						$builder->getArgument( 'ParserData' ),
						$builder->newObject( 'Settings' )
					);
				},

			'ContentParser' => function ( DependencyBuilder $builder ) {
					return new ContentParser( $builder->getArgument( 'Title' ) );
				},

			'ObservableUpdateDispatcher' => function ( DependencyBuilder $builder ) {
					return new ObservableSubjectDispatcher( $builder->newObject( 'UpdateObserver' ) );
				},

			'Factbox' => function ( DependencyBuilder $builder ) {
				return new Factbox(
					$builder->newObject( 'Store' ),
					$builder->newObject( 'ParserData' ),
					$builder->newObject( 'Settings' ),
					$builder->newObject( 'RequestContext' )
				);
			},

			'FactboxCache' => function ( DependencyBuilder $builder ) {

				$outputPage = $builder->getArgument( 'OutputPage' );

				$instance = new FactboxCache( $outputPage );
				$instance->setDependencyBuilder( $builder );

				return $instance;
			},

			/**
			 * IContextSource object definition
			 *
			 * @since  1.9
			 *
			 * @return IContextSource
			 */
			'RequestContext' => function ( DependencyBuilder $builder ) {

				$instance = new \RequestContext();

				if ( $builder->hasArgument( 'Title' ) ) {
					$instance->setTitle( $builder->getArgument( 'Title' ) );
				}

				if ( $builder->hasArgument( 'Language' ) ) {
					$instance->setLanguage( $builder->getArgument( 'Language' ) );
				}

				return $instance;
			},

			/**
			 * WikiPage object definition
			 *
			 * @since  1.9
			 *
			 * @return WikiPage
			 */
			'WikiPage' => function ( DependencyBuilder $builder ) {
				return \WikiPage::factory( $builder->getArgument( 'Title' ) );
			}

		);
	}

	/**
	 * ParserData object definition
	 *
	 * @since  1.9
	 *
	 * @return ParserData
	 */
	protected function getParserData() {
		return function ( DependencyBuilder $builder ) {

			$instance = new ParserData(
				$builder->getArgument( 'Title' ),
				$builder->getArgument( 'ParserOutput' )
			);

			$instance->setObservableDispatcher( $builder->newObject( 'ObservableUpdateDispatcher' ) );

			return $instance;
		};
	}

	/**
	 * NamespaceExaminer object definition
	 *
	 * @since  1.9
	 *
	 * @return NamespaceExaminer
	 */
	protected function getNamespaceExaminer() {
		return function ( DependencyBuilder $builder ) {
			return NamespaceExaminer::newFromArray( $builder->newObject( 'Settings' )->get( 'smwgNamespacesWithSemanticLinks' ) );
		};
	}

	/**
	 * UpdateObserver object definition
	 *
	 * @since  1.9
	 *
	 * @return UpdateObserver
	 */
	protected function getUpdateObserver() {
		return function ( DependencyBuilder $builder ) {
			$updateObserver = new UpdateObserver();
			$updateObserver->setDependencyBuilder( $builder );
			return $updateObserver;
		};
	}

	/**
	 * BasePropertyAnnotator object definition
	 *
	 * @since  1.9
	 *
	 * @return BasePropertyAnnotator
	 */
	protected function getBasePropertyAnnotator() {
		return function ( DependencyBuilder $builder ) {
			return new BasePropertyAnnotator(
				$builder->getArgument( 'SemanticData' ),
				$builder->newObject( 'Settings' )
			);
		};
	}

}
