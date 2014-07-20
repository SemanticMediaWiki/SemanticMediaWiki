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
			'NullPropertyAnnotator'   => $this->NullPropertyAnnotator(),
			'CommonPropertyAnnotator' => $this->CommonPropertyAnnotator(),
			'PredefinedPropertyAnnotator' => $this->PredefinedPropertyAnnotator(),
			'QueryProfiler' => $this->QueryProfiler(),

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
			},

			/**
			 * AskParserFunction object definition
			 *
			 * @since  1.9
			 *
			 * @return AskParserFunction
			 */
			'AskParserFunction' => function ( DependencyBuilder $builder ) {

				$parser = $builder->getArgument( 'Parser' );
				$builder->addArgument( 'Language', $parser->getTargetLanguage() );

				$parserData = $builder->newObject( 'ParserData', array(
					'Title'        => $parser->getTitle(),
					'ParserOutput' => $parser->getOutput()
				) );

				$instance = new AskParserFunction( $parserData, $builder->newObject( 'ExtensionContext' ) );

				return $instance;
			},

			/**
			 * ShowParserFunction object definition
			 *
			 * @since  1.9
			 *
			 * @return ShowParserFunction
			 */
			'ShowParserFunction' => function ( DependencyBuilder $builder ) {

				$parser = $builder->getArgument( 'Parser' );
				$builder->addArgument( 'Language', $parser->getTargetLanguage() );

				$parserData = $builder->newObject( 'ParserData', array(
					'Title'        => $parser->getTitle(),
					'ParserOutput' => $parser->getOutput()
				) );

				$instance = new ShowParserFunction( $parserData, $builder->newObject( 'ExtensionContext' ) );

				return $instance;
			},

			/**
			 * SubobjectParserFunction object definition
			 *
			 * @since  1.9
			 *
			 * @return SubobjectParserFunction
			 */
			'SubobjectParserFunction' => function ( DependencyBuilder $builder ) {

				$parser = $builder->getArgument( 'Parser' );

				$parserData = $builder->newObject( 'ParserData', array(
					'Title'        => $parser->getTitle(),
					'ParserOutput' => $parser->getOutput()
				) );

				$subobject = new Subobject( $parser->getTitle() );

				$messageFormatter = $builder->newObject( 'MessageFormatter', array(
					'Language' => $parser->getTargetLanguage()
				) );

				$instance = new SubobjectParserFunction( $parserData, $subobject, $messageFormatter );

				return $instance;
			},

			/**
			 * ExtensionContext object definition
			 *
			 * @since  1.9
			 *
			 * @return ExtensionContext
			 */
			'ExtensionContext' => function ( DependencyBuilder $builder ) {
				return new ExtensionContext( $builder );
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

	/**
	 * NullPropertyAnnotator object definition
	 *
	 * @since  1.9
	 *
	 * @return NullPropertyAnnotator
	 */
	protected function NullPropertyAnnotator() {
		return function ( DependencyBuilder $builder ) {
			return new \SMW\Annotator\NullPropertyAnnotator(
				$builder->getArgument( 'SemanticData' )
			);
		};
	}

	/**
	 * PropertyAnnotator object definition
	 *
	 * @since  1.9
	 *
	 * @return PropertyAnnotator
	 */
	protected function CommonPropertyAnnotator() {
		return function ( DependencyBuilder $builder ) {

			$annotator = $builder->newObject( 'NullPropertyAnnotator' );

			if ( $builder->hasArgument( 'DefaultSort' ) ) {
				$annotator = new \SMW\Annotator\SortkeyPropertyAnnotator(
					$annotator,
					$builder->getArgument( 'DefaultSort' )
				);
			}

			if ( $builder->hasArgument( 'CategoryLinks' ) ) {
				$annotator = new \SMW\Annotator\CategoryPropertyAnnotator(
					$annotator,
					$builder->getArgument( 'CategoryLinks' )
				);
			}

			return $annotator;
		};
	}

	/**
	 * @since  1.9
	 *
	 * @return PredefinedPropertyAnnotator
	 */
	protected function PredefinedPropertyAnnotator() {
		return function ( DependencyBuilder $builder ) {

			$annotator = $builder->newObject( 'NullPropertyAnnotator' );

			$valueProvider = new \SMW\MediaWiki\PageInfoProvider(
				$builder->getArgument( 'WikiPage' ),
				$builder->hasArgument( 'Revision' ) ? $builder->getArgument( 'Revision' ) : null,
				$builder->hasArgument( 'User' ) ? $builder->getArgument( 'User' ) : null
			);

			return new \SMW\Annotator\PredefinedPropertyAnnotator( $annotator, $valueProvider );
		};
	}

	/**
	 * @since  1.9
	 *
	 * @return ProfileAnnotator
	 */
	protected function QueryProfiler() {
		return function ( DependencyBuilder $builder ) {

			$profiler = new \SMW\Query\Profiler\NullProfile(
				new Subobject( $builder->getArgument( 'Title' ) ),
				new HashIdGenerator( $builder->getArgument( 'QueryParameters' ) )
			);

			$profiler = new \SMW\Query\Profiler\DescriptionProfile( $profiler, $builder->getArgument( 'QueryDescription' ) );
			$profiler = new \SMW\Query\Profiler\FormatProfile( $profiler, $builder->getArgument( 'QueryFormat' ) );
			$profiler = new \SMW\Query\Profiler\DurationProfile( $profiler, $builder->getArgument( 'QueryDuration' ) );

			return $profiler;
		};
	}

}
