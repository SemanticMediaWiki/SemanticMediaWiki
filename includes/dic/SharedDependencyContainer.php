<?php

namespace SMW;

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
			'UpdateObserver'    => $this->getUpdateObserver(),
			'NullPropertyAnnotator'   => $this->NullPropertyAnnotator(),
			'CommonPropertyAnnotator' => $this->CommonPropertyAnnotator(),
			'PredefinedPropertyAnnotator' => $this->PredefinedPropertyAnnotator(),
			'RedirectPropertyAnnotator'   => $this->RedirectPropertyAnnotator(),
			'QueryProfiler' => $this->QueryProfiler(),

			/**
			 * ContentProcessor object definition
			 *
			 * @since  1.9
			 *
			 * @return ContentProcessor
			 */
			'ContentProcessor' => function ( DependencyBuilder $builder ) {
				return new ContentProcessor(
					$builder->getArgument( 'ParserData' ),
					$builder->newObject( 'ExtensionContext' )
				);
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
			 * ObservableSubjectDispatcher object definition
			 *
			 * @since  1.9
			 *
			 * @return ObservableSubjectDispatcher
			 */
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
			 * FunctionHookRegistry object definition
			 *
			 * @since  1.9
			 *
			 * @return FunctionHookRegistry
			 */
			'FunctionHookRegistry' => function ( DependencyBuilder $builder ) {
				return new FunctionHookRegistry( $builder->newObject( 'ExtensionContext' ) );
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
			},

			/**
			 * @since  1.9
			 *
			 * @return PropertyTypeComparator
			 */
			'PropertyTypeComparator' => function ( DependencyBuilder $builder ) {

				$instance = new PropertyTypeComparator(
					$builder->newObject( 'Store' ),
					$builder->getArgument( 'SemanticData' ),
					$builder->newObject( 'Settings' )
				);

				$instance->registerDispatcher( $builder->newObject( 'ObservableUpdateDispatcher' ) );

				return $instance;
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

			$instance->registerDispatcher( $builder->newObject( 'ObservableUpdateDispatcher' ) );

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
			$updateObserver->invokeContext( $builder->newObject( 'ExtensionContext' ) );
			return $updateObserver;
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
			return new NullPropertyAnnotator(
				$builder->getArgument( 'SemanticData' ),
				$builder->newObject( 'ExtensionContext' )
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
				$annotator = new SortKeyPropertyAnnotator(
					$annotator,
					$builder->getArgument( 'DefaultSort' )
				);
			}

			if ( $builder->hasArgument( 'CategoryLinks' ) ) {
				$annotator = new CategoryPropertyAnnotator(
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

			$valueProvider = new MediaWikiPageInfoProvider(
				$builder->getArgument( 'WikiPage' ),
				$builder->hasArgument( 'Revision' ) ? $builder->getArgument( 'Revision' ) : null,
				$builder->hasArgument( 'User' ) ? $builder->getArgument( 'User' ) : null
			);

			return new PredefinedPropertyAnnotator( $annotator, $valueProvider );
		};
	}

	/**
	 * RedirectPropertyAnnotator object definition
	 *
	 * @since  1.9
	 *
	 * @return RedirectPropertyAnnotator
	 */
	protected function RedirectPropertyAnnotator() {
		return function ( DependencyBuilder $builder ) {

			$annotator = $builder->newObject( 'NullPropertyAnnotator' );

			return new RedirectPropertyAnnotator(
				$annotator,
				$builder->getArgument( 'Text' )
			);
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
