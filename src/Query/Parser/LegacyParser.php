<?php

namespace SMW\Query\Parser;

use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Localizer;
use SMW\Query\DescriptionFactory;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Parser;
use SMW\Query\QueryToken;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author Markus Krötzsch
 */
class LegacyParser implements Parser {

	/**
	 * @var DescriptionProcessor
	 */
	private $descriptionProcessor;

	/**
	 * @var QueryToken
	 */
	private $queryToken;

	/**
	 * @var Tokenizer
	 */
	private $tokenizer;

	/**
	 * @var DescriptionFactory
	 */
	private $descriptionFactory;

	/**
	 * @var DataTypeRegistry
	 */
	private $dataTypeRegistry;

	/**
	 * Description of the default namespace restriction, or NULL if not used
	 *
	 * @var array|null
	 */
	private $defaultNamespace;

	/**
	 * List of open blocks ("parentheses") that need closing at current step
	 *
	 * @var array
	 */
	private $separatorStack = [];

	/**
	 * Remaining string to be parsed (parsing eats query string from the front)
	 *
	 * @var string
	 */
	private $currentString;

	/**
	 * Cache label of category namespace . ':'
	 *
	 * @var string
	 */
	private $categoryPrefix;

	/**
	 * Cache label of concept namespace . ':'
	 *
	 * @var string
	 */
	private $conceptPrefix;

	/**
	 * Cache canonnical label of category namespace . ':'
	 *
	 * @var string
	 */
	private $categoryPrefixCannonical;

	/**
	 * Cache canonnical label of concept namespace . ':'
	 *
	 * @var string
	 */
	private $conceptPrefixCannonical;

	/**
	 * @var DIWikiPage|null
	 */
	private $contextPage;

	/**
	 * @var boolean
	 */
	private $selfReference = false;

	/**
	 * @since 3.0
	 *
	 * @param DescriptionProcessor $descriptionProcessor
	 * @param Tokenizer $tokenizer
	 * @param QueryToken $queryToken
	 */
	public function __construct( DescriptionProcessor $descriptionProcessor, Tokenizer $tokenizer, QueryToken $queryToken ) {
		$this->descriptionProcessor = $descriptionProcessor;
		$this->tokenizer = $tokenizer;
		$this->queryToken = $queryToken;
		$this->descriptionFactory = new DescriptionFactory();
		$this->dataTypeRegistry = DataTypeRegistry::getInstance();
		$this->setDefaultPrefix();
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage|null $contextPage
	 */
	public function setContextPage( DIWikiPage $contextPage = null ) {
		$this->contextPage = $contextPage;
	}

	/**
	 * Provide an array of namespace constants that are used as default restrictions.
	 * If NULL is given, no such default restrictions will be added (faster).
	 *
	 * @since 1.6
	 */
	public function setDefaultNamespaces( $namespaces ) {
		$this->defaultNamespace = null;

		if ( !is_array( $namespaces ) ) {
			return;
		}

		foreach ( $namespaces as $namespace ) {
			$this->defaultNamespace = $this->descriptionProcessor->asOr(
				$this->defaultNamespace,
				$this->descriptionFactory->newNamespaceDescription( $namespace )
			);
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param string|null $languageCode
	 */
	public function setDefaultPrefix( $languageCode = null ) {

		$localizer = Localizer::getInstance();

		if ( $languageCode === null ) {
			$language = $localizer->getContentLanguage();
		} else {
			$language = $localizer->getLanguage( $languageCode );
		}

		$this->categoryPrefix = $language->getNsText( NS_CATEGORY ) . ':';
		$this->conceptPrefix = $language->getNsText( SMW_NS_CONCEPT ) . ':';

		$this->categoryPrefixCannonical = 'Category:';
		$this->conceptPrefixCannonical = 'Concept:';

		$this->tokenizer->setDefaultPattern(
			[
				$this->categoryPrefix,
				$this->conceptPrefix,
				$this->categoryPrefixCannonical,
				$this->conceptPrefixCannonical
			]
		);
	}

	/**
	 * Return array of error messages (possibly empty).
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->descriptionProcessor->getErrors();
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function containsSelfReference() {

		if ( $this->selfReference ) {
			return true;
		}

		return $this->descriptionProcessor->containsSelfReference();
	}

	/**
	 * Return error message or empty string if no error occurred.
	 *
	 * @return string
	 */
	public function getErrorString() {
		throw new \RuntimeException( "Shouldnot be used, remove getErrorString usage!" );
		return smwfEncodeMessages( $this->getErrors() );
	}

	/**
	 * @since 2.5
	 *
	 * @return QueryToken
	 */
	public function getQueryToken() {
		return $this->queryToken;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function createCondition( $property, $value ) {

		if ( $property instanceOf DIProperty ) {
			$property = $property->getLabel();
		}

		return "[[$property::$value]]";
	}

	/**
	 * Compute an SMWDescription from a query string. Returns whatever descriptions could be
	 * wrestled from the given string (the most general result being SMWThingDescription if
	 * no meaningful condition was extracted).
	 *
	 * @param string $queryString
	 *
	 * @return Description
	 */
	public function getQueryDescription( $queryString ) {

		if ( $queryString === '' ) {
			$this->descriptionProcessor->addErrorWithMsgKey(
				'smw-query-condition-empty'
			);

			return  $this->descriptionFactory->newThingDescription();
		}

		$this->descriptionProcessor->clear();
		$this->descriptionProcessor->setContextPage( $this->contextPage );

		$this->currentString = $queryString;
		$this->separatorStack = [];

		$this->selfReference = false;
		$setNS = false;

		$description = $this->getSubqueryDescription( $setNS );

		// add default namespaces if applicable
		if ( !$setNS ) {
			$description = $this->descriptionProcessor->asAnd(
				$this->defaultNamespace,
				$description
			);
		}

		// parsing went wrong, no default namespaces
		if ( $description === null ) {
			$description = $this->descriptionFactory->newThingDescription();
		}

		return $description;
	}

	/**
	 * Compute an SMWDescription for current part of a query, which should
	 * be a standalone query (the main query or a subquery enclosed within
	 * "\<q\>...\</q\>". Recursively calls similar methods and returns NULL upon error.
	 *
	 * The call-by-ref parameter $setNS is a boolean. Its input specifies whether
	 * the query should set the current default namespace if no namespace restrictions
	 * were given. If false, the calling super-query is happy to set the required
	 * NS-restrictions by itself if needed. Otherwise the subquery has to impose the defaults.
	 * This is so, since outermost queries and subqueries of disjunctions will have to set
	 * their own default restrictions.
	 *
	 * The return value of $setNS specifies whether or not the subquery has a namespace
	 * specification in place. This might happen automatically if the query string imposes
	 * such restrictions. The return value is important for those callers that otherwise
	 * set up their own restrictions.
	 *
	 * Note that $setNS is no means to switch on or off default namespaces in general,
	 * but just controls query generation. For general effect, the default namespaces
	 * should be set to NULL.
	 *
	 * @return Description|null
	 */
	private function getSubqueryDescription( &$setNS ) {

		$conjunction = null;      // used for the current inner conjunction
		$disjuncts = [];     // (disjunctive) array of subquery conjunctions

		$hasNamespaces = false;   // does the current $conjnuction have its own namespace restrictions?
		$mustSetNS = $setNS;      // must NS restrictions be set? (may become true even if $setNS is false)

		$continue = ( $chunk = $this->readChunk() ) !== ''; // skip empty subquery completely, thorwing an error

		while ( $continue ) {
			$setsubNS = false;

			switch ( $chunk ) {
				case '[[': // start new link block
					$ld = $this->getLinkDescription( $setsubNS );

					if ( !is_null( $ld ) ) {
						$conjunction = $this->descriptionProcessor->asAnd( $conjunction, $ld );
					}
				break;
				case 'AND':
				case '<q>': // enter new subquery, currently irrelevant but possible
					$this->pushDelimiter( '</q>' );
					$conjunction = $this->descriptionProcessor->asAnd( $conjunction, $this->getSubqueryDescription( $setsubNS ) );
				break;
				case 'OR':
				case '||':
				case '':
				case '</q>': // finish disjunction and maybe subquery
					if ( !is_null( $this->defaultNamespace ) ) { // possibly add namespace restrictions
						if ( $hasNamespaces && !$mustSetNS ) {
							// add NS restrictions to all earlier conjunctions (all of which did not have them yet)
							$mustSetNS = true; // enforce NS restrictions from now on
							$newdisjuncts = [];

							foreach ( $disjuncts as $conj ) {
								$newdisjuncts[] = $this->descriptionProcessor->asAnd( $conj, $this->defaultNamespace );
							}

							$disjuncts = $newdisjuncts;
						} elseif ( !$hasNamespaces && $mustSetNS ) {
							// add ns restriction to current result
							$conjunction = $this->descriptionProcessor->asAnd( $conjunction, $this->defaultNamespace );
						}
					}

					$disjuncts[] = $conjunction;
					// start anew
					$conjunction = null;
					$hasNamespaces = false;

					// finish subquery?
					if ( $chunk == '</q>' ) {
						if ( $this->popDelimiter( '</q>' ) ) {
							$continue = false; // leave the loop
						} else {
							$this->descriptionProcessor->addErrorWithMsgKey( 'smw_toomanyclosing', $chunk );
							return null;
						}
					} elseif ( $chunk === '' ) {
						$continue = false;
					}
				break;
				case '+': // "... AND true" (ignore)
				break;
				default: // error: unexpected $chunk
					$this->descriptionProcessor->addErrorWithMsgKey( 'smw_unexpectedpart', $chunk );
					// return null; // Try to go on, it can only get better ...
			}

			if ( $setsubNS ) { // namespace restrictions encountered in current conjunct
				$hasNamespaces = true;
			}

			if ( $continue ) { // read on only if $continue remained true
				$chunk = $this->readChunk();
			}
		}

		if ( count( $disjuncts ) > 0 ) { // make disjunctive result
			$result = null;

			foreach ( $disjuncts as $d ) {
				if ( is_null( $d ) ) {
					$this->descriptionProcessor->addErrorWithMsgKey( 'smw_emptysubquery' );
					$setNS = false;
					return null;
				} else {
					$result = $this->descriptionProcessor->asOr( $result, $d );
				}
			}
		} else {
			$this->descriptionProcessor->addErrorWithMsgKey( 'smw_emptysubquery' );
			$setNS = false;
			return null;
		}

		// NOTE: also false if namespaces were given but no default NS descs are available
		$setNS = $mustSetNS;

		return $result;
	}

	/**
	 * Compute an SMWDescription for current part of a query, which should
	 * be the content of "[[ ... ]]". Returns NULL upon error.
	 *
	 * Parameters $setNS has the same use as in getSubqueryDescription().
	 */
	private function getLinkDescription( &$setNS ) {
		// This method is called when we encountered an opening '[['. The following
		// block could be a Category-statement, fixed object, or property statement.

		// NOTE: untrimmed, initial " " escapes prop. chains
		$chunk = $this->readChunk( '', true, false );

		if ( $this->hasClassPrefix( $chunk ) ) {
			return $this->getClassDescription( $setNS, $this->isClass( $chunk ) );
		}

		// fixed subject, namespace restriction, property query, or subquery

		// Do not consume hit, "look ahead"
		$sep = $this->readChunk( '', false );

		if ( ( $sep == '::' ) || ( $sep == ':=' ) ) {
			if ( $chunk{0} != ':' ) { // property statement
				return $this->getPropertyDescription( $chunk, $setNS );
			} else { // escaped article description, read part after :: to get full contents
				$chunk .= $this->readChunk( '\[\[|\]\]|\|\||\|' );
				return $this->getArticleDescription( trim( $chunk ), $setNS );
			}
		}

		 // Fixed article/namespace restriction. $sep should be ]] or ||
		return $this->getArticleDescription( trim( $chunk ), $setNS );
	}

	/**
	 * Parse a category description (the part of an inline query that
	 * is in between "[[Category:" and the closing "]]" and create a
	 * suitable description.
	 */
	private function getClassDescription( &$setNS, $category = true ) {

		// No subqueries allowed here, inline disjunction allowed, wildcards allowed
		$description = null;
		$continue = true;
		$invalidName = false;

		while ( $continue ) {
			$chunk = $this->readChunk();

			if ( $chunk == '+' ) {
				$desc = $this->descriptionFactory->newNamespaceDescription( $category ? NS_CATEGORY : SMW_NS_CONCEPT );
				$description = $this->descriptionProcessor->asOr( $description, $desc );
			} else { // assume category/concept title
				$isNegation = false;

				// [[Category:!Foo]]
				// Only the ElasticStore does actively support this construct
				if ( $chunk{0} === '!' ) {
					$chunk = substr( $chunk, 1 );
					$isNegation = true;
				}

				// We add a prefix to prevent problems with, e.g., [[Category:Template:Test]]
				$prefix = $category ? $this->categoryPrefix : $this->conceptPrefix;
				$title = Title::newFromText( $prefix . $chunk );

				// Something like [[Category::Foo]] doesn't produce any meaningful
				// results
				if ( strpos( $prefix . $chunk, '::' ) !== false ) {
					$invalidName .= "{$prefix}{$chunk}";
				} elseif ( $invalidName ) {
					$invalidName .= "||{$chunk}";
				}

				if ( $title !== null ) {
					$diWikiPage = new DIWikiPage( $title->getDBkey(), $title->getNamespace(), '' );

					if ( !$this->selfReference && $this->contextPage !== null ) {
						$this->selfReference = $diWikiPage->equals( $this->contextPage );
					}

					$desc = $category ? $this->descriptionFactory->newClassDescription( $diWikiPage ) : $this->descriptionFactory->newConceptDescription( $diWikiPage );

					if ( $isNegation ) {
						$desc->isNegation = $isNegation;
					}

					$description = $this->descriptionProcessor->asOr( $description, $desc );
				}
			}

			$chunk = $this->readChunk();

			// Disjunctions only for categories
			$continue = ( $chunk == '||' ) && $category;
		}

		if ( $invalidName ) {
			return $this->descriptionProcessor->addErrorWithMsgKey( 'smw-category-invalid-value-assignment', "[[{$invalidName}]]" );
		}

		return $this->finishLinkDescription( $chunk, false, $description, $setNS );
	}

	/**
	 * Parse a property description (the part of an inline query that
	 * is in between "[[Some property::" and the closing "]]" and create a
	 * suitable description. The "::" is the first chunk on the current
	 * string.
	 */
	private function getPropertyDescription( $propertyName, &$setNS ) {

		// Consume separator ":=" or "::"
		$this->readChunk();
		$dataValueFactory = DataValueFactory::getInstance();

		// First process property chain syntax (e.g. "property1.property2::value"),
		// escaped by initial " ":
		$propertynames = ( $propertyName{0} == ' ' ) ? [ $propertyName ] : explode( '.', $propertyName );
		$propertyValueList = [];

		$typeid = '_wpg';
		$inverse = false;

		// After iteration, $property and $typeid correspond to last value
		foreach ( $propertynames as $name ) {

			// Non-final property in chain was no wikipage: not allowed
			if ( !$this->isPagePropertyType( $typeid ) ) {
				$this->descriptionProcessor->addErrorWithMsgKey( 'smw_valuesubquery', $name );

				// TODO: read some more chunks and try to finish [[ ]]
				return null;
			}

			$propertyValue = $dataValueFactory->newPropertyValueByLabel( $name );

			// Illegal property identifier
			if ( !$propertyValue->isValid() ) {
				$this->descriptionProcessor->addError( $propertyValue->getErrors() );

				// TODO: read some more chunks and try to finish [[ ]]
				return null;
			}

			// Set context to allow evading restriction checks for specific
			// entities that handle the context such as pre-defined properties
			// (Has query, Modification date etc.)
			$propertyValue->setOption( $propertyValue::OPT_QUERY_CONTEXT, true );

			// Check restriction
			if ( $propertyValue->isRestricted() ) {
				$this->descriptionProcessor->addError( $propertyValue->getRestrictionError() );
				return null;
			}

			$property = $propertyValue->getDataItem();
			$propertyValueList[] = $propertyValue;

			$typeid = $property->findPropertyTypeID();
			$inverse = $property->isInverse();
		}

		$innerdesc = null;
		$continue = true;

		while ( $continue ) {
			$chunk = $this->readChunk();

			switch ( $chunk ) {
				// !+
				case '!+':
					$desc = $this->descriptionFactory->newThingDescription();
					$desc->isNegation = true;
					$innerdesc = $this->descriptionProcessor->asOr( $innerdesc, $desc );
					$chunk = $this->readChunk();
				break;
				// wildcard, add namespaces for page-type properties
				case '+':
					if ( !is_null( $this->defaultNamespace ) && ( $this->isPagePropertyType( $typeid ) || $inverse ) ) {
						$innerdesc = $this->descriptionProcessor->asOr( $innerdesc, $this->defaultNamespace );
					} else {
						$innerdesc = $this->descriptionProcessor->asOr( $innerdesc, $this->descriptionFactory->newThingDescription() );
					}
					$chunk = $this->readChunk();
				break;
				 // subquery, set default namespaces
				case '<q>':
					if ( $this->isPagePropertyType( $typeid ) || $inverse ) {
						$this->pushDelimiter( '</q>' );
						$setsubNS = true;
						$innerdesc = $this->descriptionProcessor->asOr( $innerdesc, $this->getSubqueryDescription( $setsubNS ) );
					} else { // no subqueries allowed for non-pages
						$this->descriptionProcessor->addErrorWithMsgKey( 'smw_valuesubquery', end( $propertynames ) );
						$innerdesc = $this->descriptionProcessor->asOr( $innerdesc, $this->descriptionFactory->newThingDescription() );
					}
					$chunk = $this->readChunk();
				break;
				// normal object value
				default:
					// read value(s), possibly with inner [[...]]
					$open = 1;
					$value = $chunk;
					$continue2 = true;
					// read value with inner [[, ]], ||
					while ( ( $open > 0 ) && ( $continue2 ) ) {
						$chunk = $this->readChunk( '\[\[|\]\]|\|\||\|' );
						switch ( $chunk ) {
							case '[[': // open new [[ ]]
								$open++;
							break;
							case ']]': // close [[ ]]
								$open--;
							break;
							case '|':
							case '||': // terminates only outermost [[ ]]
								if ( $open == 1 ) {
									$open = 0;
								}
							break;
							case '': ///TODO: report error; this is not good right now
								$continue2 = false;
							break;
						}
						if ( $open != 0 ) {
							$value .= $chunk;
						}
					} ///NOTE: at this point, we normally already read one more chunk behind the value
					$outerDesription = $this->descriptionProcessor->newDescriptionForPropertyObjectValue(
						$propertyValue->getDataItem(),
						$value
					);

					$this->queryToken->addFromDesciption( $outerDesription );
					$innerdesc = $this->descriptionProcessor->asOr(
						$innerdesc,
						$outerDesription
					);

			}
			$continue = ( $chunk == '||' );
		}

		// No description, make a wildcard search
		if ( $innerdesc === null ) {
			if ( $this->defaultNamespace !== null && $this->isPagePropertyType( $typeid ) ) {
				$innerdesc = $this->descriptionProcessor->asOr( $innerdesc, $this->defaultNamespace );
			} else {
				$innerdesc = $this->descriptionProcessor->asOr( $innerdesc, $this->descriptionFactory->newThingDescription() );
			}

			$this->descriptionProcessor->addErrorWithMsgKey( 'smw_propvalueproblem', $propertyValue->getWikiValue() );
		}

		$propertyValueList = array_reverse( $propertyValueList );

		foreach ( $propertyValueList as $propertyValue ) {
			$innerdesc = $this->descriptionFactory->newSomeProperty( $propertyValue->getDataItem(), $innerdesc );
		}

		$result = $innerdesc;

		return $this->finishLinkDescription( $chunk, false, $result, $setNS );
	}

	/**
	 * Parse an article description (the part of an inline query that
	 * is in between "[[" and the closing "]]" assuming it is not specifying
	 * a category or property) and create a suitable description.
	 * The first chunk behind the "[[" has already been read and is
	 * passed as a parameter.
	 */
	private function getArticleDescription( $firstChunk, &$setNS ) {

		$chunk = $firstChunk;
		$description = null;

		$continue = true;
		$localizer = Localizer::getInstance();

		while ( $continue ) {

			 // No subqueries of the form [[<q>...</q>]] (not needed)
			if ( $chunk == '<q>' ) {
				$this->descriptionProcessor->addErrorWithMsgKey( 'smw_misplacedsubquery' );
				return null;
			}

			// ":Category:Foo" "User:bar"  ":baz" ":+"
			$list = preg_split( '/:/', $chunk, 3 );

			if ( ( $list[0] === '' ) && ( count( $list ) == 3 ) ) {
				$list = array_slice( $list, 1 );
			}

			// try namespace restriction
			if ( ( count( $list ) == 2 ) && ( $list[1] == '+' ) ) {

				$idx = $localizer->getNamespaceIndexByName( $list[0] );

				if ( $idx !== false ) {
					$description = $this->descriptionProcessor->asOr(
						$description,
						$this->descriptionFactory->newNamespaceDescription( $idx )
					);
				}
			} else {
				$outerDesription = $this->descriptionProcessor->newDescriptionForWikiPageValueChunk(
					$chunk
				);

				$this->queryToken->addFromDesciption( $outerDesription );

				$description = $this->descriptionProcessor->asOr(
					$description,
					$outerDesription
				);
			}

			$chunk = $this->readChunk( '\[\[|\]\]|\|\||\|' );

			if ( $chunk == '||' ) {
				$chunk = $this->readChunk( '\[\[|\]\]|\|\||\|' );
				$continue = true;
			} else {
				$continue = false;
			}
		}

		return $this->finishLinkDescription( $chunk, true, $description, $setNS );
	}

	private function finishLinkDescription( $chunk, $hasNamespaces, $description, &$setNS ) {

		if ( is_null( $description ) ) { // no useful information or concrete error found
			$this->descriptionProcessor->addErrorWithMsgKey( 'smw_unexpectedpart', $chunk ); // was smw_badqueryatom
		} elseif ( !$hasNamespaces && $setNS && !is_null( $this->defaultNamespace  ) ) {
			$description = $this->descriptionProcessor->asAnd( $description, $this->defaultNamespace );
			$hasNamespaces = true;
		}

		$setNS = $hasNamespaces;

		if ( $chunk == '|' ) { // skip content after single |, but report a warning
			// Note: Using "|label" in query atoms used to be a way to set the mainlabel in SMW <1.0; no longer supported now
			$chunk = $this->readChunk( '\]\]' );
			$labelpart = '|';
			$hasError = true;

			// Set an individual hierarchy depth
			if ( strpos( $chunk, '+depth=' ) !== false ) {
				list( $k, $depth ) = explode( '=', $chunk, 2 );

				if ( $description instanceOf ClassDescription || $description instanceOf SomeProperty || $description instanceOf Disjunction ) {
					$description->setHierarchyDepth( $depth );
				}

				$chunk = $this->readChunk( '\]\]' );
				$hasError = false;
			}

			if ( $chunk != ']]' ) {
				$labelpart .= $chunk;
				$chunk = $this->readChunk( '\]\]' );
			}

			if ( $hasError ) {
				$this->descriptionProcessor->addErrorWithMsgKey( 'smw_unexpectedpart', $labelpart );
			}
		}

		if ( $chunk != ']]' ) {
			// What happended? We found some chunk that could not be processed as
			// link content (as in [[Category:Test<q>]]), or the closing ]] are
			// just missing entirely.
			if ( $chunk !== '' ) {
				$this->descriptionProcessor->addErrorWithMsgKey( 'smw_misplacedsymbol', $chunk );

				// try to find a later closing ]] to finish this misshaped subpart
				$chunk = $this->readChunk( '\]\]' );

				if ( $chunk != ']]' ) {
					$chunk = $this->readChunk( '\]\]' );
				}
			}
			if ( $chunk === '' ) {
				$this->descriptionProcessor->addErrorWithMsgKey( 'smw_noclosingbrackets' );
			}
		}

		return $description;
	}

	/**
	 * @see Tokenizer::read
	 */
	private function readChunk( $stoppattern = '', $consume = true, $trim = true ) {
		return $this->tokenizer->getToken( $this->currentString, $stoppattern, $consume, $trim );
	}

	/**
	 * Enter a new subblock in the query, which must at some time be terminated by the
	 * given $endstring delimiter calling popDelimiter();
	 */
	private function pushDelimiter( $endstring ) {
		array_push( $this->separatorStack, $endstring );
	}

	/**
	 * Exit a subblock in the query ending with the given delimiter.
	 * If the delimiter does not match the top-most open block, false
	 * will be returned. Otherwise return true.
	 */
	private function popDelimiter( $endstring ) {
		$topdelim = array_pop( $this->separatorStack );
		return ( $topdelim == $endstring );
	}

	private function isPagePropertyType( $typeid ) {
		return $typeid == '_wpg' || $this->dataTypeRegistry->isSubDataType( $typeid );
	}

	private function hasClassPrefix( $chunk ) {
		return in_array( smwfNormalTitleText( $chunk ), [ $this->categoryPrefix, $this->conceptPrefix, $this->categoryPrefixCannonical, $this->conceptPrefixCannonical ] );
	}

	private function isClass( $chunk ) {
		return smwfNormalTitleText( $chunk ) == $this->categoryPrefix || smwfNormalTitleText( $chunk ) == $this->categoryPrefixCannonical;
	}

}
