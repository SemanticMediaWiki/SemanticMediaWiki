<?php

namespace SMW;

use SMW\MediaWiki\TitleCreator;
use SMW\MediaWiki\PageCreator;

/**
 * Extends the BaseDependencyContainer to provide general purpose dependency
 * object definitions
 *
 * @ingroup DependencyContainer
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SharedDependencyContainer extends BaseDependencyContainer {

	/**
	 * @since  1.9
	 */
	public function __construct() {
		$this->loadAtInstantiation();
	}

	/**
	 * @since  1.9
	 */
	protected function loadAtInstantiation() {

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
	 * @see BaseDependencyContainer::registerDefinitions
	 *
	 * @since  1.9
	 *
	 * @return array
	 */
	protected function getDefinitions() {
		return array(
			'ParserData'        => $this->getParserData(),
			'NamespaceExaminer' => $this->getNamespaceExaminer(),

			'JobFactory' => function ( DependencyBuilder $builder ) {
				return new \SMW\MediaWiki\Jobs\JobFactory();
			},

			/**
			 * ContentParser object definition
			 *
			 * @since  1.9
			 *
			 * @return ContentParser
			 */
			'ContentParser' => function ( DependencyBuilder $builder ) {
				return new ContentParser( $builder->getArgument( 'Title' ) );
			},

			/**
			 * RequestContext object definition
			 *
			 * @since  1.9
			 *
			 * @return RequestContext
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
			},

			/**
			 * @since  2.0
			 *
			 * @return TitleCreator
			 */
			'TitleCreator' => function ( DependencyBuilder $builder ) {
				return new TitleCreator( new PageCreator() );
			},

			/**
			 * @since  2.0
			 *
			 * @return PageCreator
			 */
			'PageCreator' => function ( DependencyBuilder $builder ) {
				return new PageCreator();
			},

			/**
			 * MessageFormatter object definition
			 *
			 * @since  1.9
			 *
			 * @return MessageFormatter
			 */
			'MessageFormatter' => function ( DependencyBuilder $builder ) {
				return new MessageFormatter( $builder->getArgument( 'Language' ) );
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

}
