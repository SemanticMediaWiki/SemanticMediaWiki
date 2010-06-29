<?php
/**
 * This file contains a class for parsing inline query strings.
 * @file
 * @ingroup SMWQuery
 * @author Markus Krötzsch
 */

/**
 * Objects of this class are in charge of parsing a query string in order
 * to create an SMWDescription. The class and methods are not static in order
 * to more cleanly store the intermediate state and progress of the parser.
 * @ingroup SMWQuery
 */
class SMWQueryParser {

	protected $m_sepstack; // list of open blocks ("parentheses") that need closing at current step
	protected $m_curstring; // remaining string to be parsed (parsing eats query string from the front)
	protected $m_errors; // empty array if all went right, array of strings otherwise
	protected $m_label; // label of the main query result
	protected $m_defaultns; // description of the default namespace restriction, or NULL if not used

	protected $m_categoryprefix; // cache label of category namespace . ':'
	protected $m_conceptprefix; // cache label of concept namespace . ':'
	protected $m_queryfeatures; // query features to be supported, format similar to $smwgQFeatures

	public function SMWQueryParser( $queryfeatures = false ) {
		global $wgContLang, $smwgQFeatures;
		$this->m_categoryprefix = $wgContLang->getNsText( NS_CATEGORY ) . ':';
		$this->m_conceptprefix = $wgContLang->getNsText( SMW_NS_CONCEPT ) . ':';
		$this->m_defaultns = null;
		$this->m_queryfeatures = $queryfeatures === false ? $smwgQFeatures:$queryfeatures;
	}

	/**
	 * Provide an array of namespace constants that are used as default restrictions.
	 * If NULL is given, no such default restrictions will be added (faster).
	 */
	public function setDefaultNamespaces( $nsarray ) {
		$this->m_defaultns = null;
		if ( $nsarray !== null ) {
			foreach ( $nsarray as $ns ) {
				$this->m_defaultns = $this->addDescription( $this->m_defaultns, new SMWNamespaceDescription( $ns ), false );
			}
		}
	}

	/**
	 * Compute an SMWDescription from a query string. Returns whatever descriptions could be
	 * wrestled from the given string (the most general result being SMWThingDescription if
	 * no meaningful condition was extracted).
	 */
	public function getQueryDescription( $querystring ) {
		wfProfileIn( 'SMWQueryParser::getQueryDescription (SMW)' );
		$this->m_errors = array();
		$this->m_label = '';
		$this->m_curstring = $querystring;
		$this->m_sepstack = array();
		$setNS = false;
		$result = $this->getSubqueryDescription( $setNS, $this->m_label );
		if ( !$setNS ) { // add default namespaces if applicable
			$result = $this->addDescription( $this->m_defaultns, $result );
		}
		if ( $result === null ) { // parsing went wrong, no default namespaces
			$result = new SMWThingDescription();
		}
		wfProfileOut( 'SMWQueryParser::getQueryDescription (SMW)' );
		return $result;
	}

	/**
	 * Return array of error messages (possibly empty).
	 */
	public function getErrors() {
		return $this->m_errors;
	}

	/**
	 * Return error message or empty string if no error occurred.
	 */
	public function getErrorString() {
		return smwfEncodeMessages( $this->m_errors );
	}

	/**
	 * Return label for the results of this query (which
	 * might be empty if no such information was passed).
	 */
	public function getLabel() {
		return $this->m_label;
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
	 * The call-by-ref parameter $label is used to append any label strings found.
	 */
	protected function getSubqueryDescription( &$setNS, &$label ) {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		$conjunction = null;      // used for the current inner conjunction
		$disjuncts = array();     // (disjunctive) array of subquery conjunctions
		$hasNamespaces = false;   // does the current $conjnuction have its own namespace restrictions?
		$mustSetNS = $setNS;      // must ns restrictions be set? (may become true even if $setNS is false)

		$continue = ( $chunk = $this->readChunk() ) != ''; // skip empty subquery completely, thorwing an error
		while ( $continue ) {
			$setsubNS = false;
			switch ( $chunk ) {
				case '[[': // start new link block
					$ld = $this->getLinkDescription( $setsubNS, $label );
					if ( $ld !== null ) {
						$conjunction = $this->addDescription( $conjunction, $ld );
					}
				break;
				case '<q>': // enter new subquery, currently irrelevant but possible
					$this->pushDelimiter( '</q>' );
					$conjunction = $this->addDescription( $conjunction, $this->getSubqueryDescription( $setsubNS, $label ) );
				break;
				case 'OR': case '||': case '': case '</q>': // finish disjunction and maybe subquery
					if ( $this->m_defaultns !== null ) { // possibly add namespace restrictions
						if ( $hasNamespaces && !$mustSetNS ) {
							// add ns restrictions to all earlier conjunctions (all of which did not have them yet)
							$mustSetNS = true; // enforce NS restrictions from now on
							$newdisjuncts = array();
							foreach ( $disjuncts as $conj ) {
								$newdisjuncts[] = $this->addDescription( $conj, $this->m_defaultns );
							}
							$disjuncts = $newdisjuncts;
						} elseif ( !$hasNamespaces && $mustSetNS ) {
							// add ns restriction to current result
							$conjunction = $this->addDescription( $conjunction, $this->m_defaultns );
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
							$this->m_errors[] = wfMsgForContent( 'smw_toomanyclosing', $chunk );
							return null;
						}
					} elseif ( $chunk == '' ) {
						$continue = false;
					}
				break;
				case '+': // "... AND true" (ignore)
				break;
				default: // error: unexpected $chunk
					$this->m_errors[] = wfMsgForContent( 'smw_unexpectedpart', $chunk );
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
				if ( $d === null ) {
					$this->m_errors[] = wfMsgForContent( 'smw_emptysubquery' );
					$setNS = false;
					return null;
				} else {
					$result = $this->addDescription( $result, $d, false );
				}
			}
		} else {
			$this->m_errors[] = wfMsgForContent( 'smw_emptysubquery' );
			$setNS = false;
			return null;
		}
		$setNS = $mustSetNS; // NOTE: also false if namespaces were given but no default NS descs are available

		return $result;
	}

	/**
	 * Compute an SMWDescription for current part of a query, which should
	 * be the content of "[[ ... ]]". Returns NULL upon error.
	 *
	 * Parameters $setNS and $label have the same use as in getSubqueryDescription().
	 */
	protected function getLinkDescription( &$setNS, &$label ) {
		// This method is called when we encountered an opening '[['. The following
		// block could be a Category-statement, fixed object, or property statement.
		$chunk = $this->readChunk( '', true, false ); // NOTE: untrimmed, initial " " escapes prop. chains
		
		if ( ( smwfNormalTitleText( $chunk ) == $this->m_categoryprefix ) ||  // category statement or
		     ( smwfNormalTitleText( $chunk ) == $this->m_conceptprefix ) ) {  // concept statement
			return $this->getClassDescription( $setNS, $label,
			       ( smwfNormalTitleText( $chunk ) == $this->m_categoryprefix ) );
		} else { // fixed subject, namespace restriction, property query, or subquery
			$sep = $this->readChunk( '', false ); // do not consume hit, "look ahead"
			
			if ( ( $sep == '::' ) || ( $sep == ':=' ) ) {
				if ( $chunk { 0 } != ':' ) { // property statement
					return $this->getPropertyDescription( $chunk, $setNS, $label );
				} else { // escaped article description, read part after :: to get full contents
					$chunk .= $this->readChunk( '\[\[|\]\]|\|\||\|' );
					return $this->getArticleDescription( trim( $chunk ), $setNS, $label );
				}
			} else { // Fixed article/namespace restriction. $sep should be ]] or ||
				return $this->getArticleDescription( trim( $chunk ), $setNS, $label );
			}
		}
	}

	/**
	 * Parse a category description (the part of an inline query that
	 * is in between "[[Category:" and the closing "]]" and create a
	 * suitable description.
	 */
	protected function getClassDescription( &$setNS, &$label, $category = true ) {
		// note: no subqueries allowed here, inline disjunction allowed, wildcards allowed
		$result = null;
		$continue = true;
		
		while ( $continue ) {
			$chunk = $this->readChunk();
			
			if ( $chunk == '+' ) {
				// wildcard, ignore for categories (semantically meaningless, everything is in some class)
			} else { // assume category/concept title
				/// NOTE: use m_c...prefix to prevent problems with, e.g., [[Category:Template:Test]]
				$class = Title::newFromText( ( $category ? $this->m_categoryprefix:$this->m_conceptprefix ) . $chunk );
				if ( $class !== null ) {
					$desc = $category ? new SMWClassDescription( $class ):new SMWConceptDescription( $class );
					$result = $this->addDescription( $result, $desc, false );
				}
			}
			
			$chunk = $this->readChunk();
			$continue = ( $chunk == '||' ) && $category; // disjunctions only for cateories
		}

		return $this->finishLinkDescription( $chunk, false, $result, $setNS, $label );
	}

	/**
	 * Parse a property description (the part of an inline query that
	 * is in between "[[Some property::" and the closing "]]" and create a
	 * suitable description. The "::" is the first chunk on the current
	 * string.
	 */
	protected function getPropertyDescription( $propertyname, &$setNS, &$label ) {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		$this->readChunk(); // consume separator ":=" or "::"
		
		// first process property chain syntax (e.g. "property1.property2::value"), escaped by initial " ":
		$propertynames = ( $propertyname { 0 } == ' ' ) ? array( $propertyname ):explode( '.', $propertyname );
		$properties = array();
		$typeid = '_wpg';
		$inverse = false;
		
		foreach ( $propertynames as $name ) {
			if ( $typeid != '_wpg' ) { // non-final property in chain was no wikipage: not allowed
				$this->m_errors[] = wfMsgForContent( 'smw_valuesubquery', $prevname );
				return null; ///TODO: read some more chunks and try to finish [[ ]]
			}
			
			$property = SMWPropertyValue::makeUserProperty( $name );
			
			if ( !$property->isValid() ) { // illegal property identifier
				$this->m_errors = array_merge( $this->m_errors, $property->getErrors() );
				return null; ///TODO: read some more chunks and try to finish [[ ]]
			}
			
			$typeid = $property->getPropertyTypeID();
			$inverse = $property->isInverse();
			$prevname = $name;
			$properties[] = $property;
		} ///NOTE: after iteration, $property and $typeid correspond to last value

		$innerdesc = null;
		$continue = true;
		
		while ( $continue ) {
			$chunk = $this->readChunk();
			
			switch ( $chunk ) {
				case '+': // wildcard, add namespaces for page-type properties
					if ( ( $this->m_defaultns !== null ) && ( ( $typeid == '_wpg' ) || $inverse ) ) {
						$innerdesc = $this->addDescription( $innerdesc, $this->m_defaultns, false );
					} else {
						$innerdesc = $this->addDescription( $innerdesc, new SMWThingDescription(), false );
					}
					$chunk = $this->readChunk();
				break;
				case '<q>': // subquery, set default namespaces
					if ( ( $typeid == '_wpg' ) || $inverse ) {
						$this->pushDelimiter( '</q>' );
						$setsubNS = true;
						$sublabel = '';
						$innerdesc = $this->addDescription( $innerdesc, $this->getSubqueryDescription( $setsubNS, $sublabel ), false );
					} else { // no subqueries allowed for non-pages
						$this->m_errors[] = wfMsgForContent( 'smw_valuesubquery', end( $propertynames ) );
						$innerdesc = $this->addDescription( $innerdesc, new SMWThingDescription(), false );
					}
					$chunk = $this->readChunk();
				break;
				default: // normal object value
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
							case '|': case '||': // terminates only outermost [[ ]]
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

					$dv = SMWDataValueFactory::newPropertyObjectValue( $property );
					$vd = $dv->getQueryDescription( $value );
					$innerdesc = $this->addDescription( $innerdesc, $vd, false );
					$this->m_errors = $this->m_errors + $dv->getErrors();
			}
			$continue = ( $chunk == '||' );
		}

		if ( $innerdesc === null ) { // make a wildcard search
			$innerdesc = ( ( $this->m_defaultns !== null ) && ( $typeid == '_wpg' ) ) ?
			              $this->addDescription( $innerdesc, $this->m_defaultns, false ):
			              $this->addDescription( $innerdesc, new SMWThingDescription(), false );
			$this->m_errors[] = wfMsgForContent( 'smw_propvalueproblem', $property->getWikiValue() );
		}
		
		$properties = array_reverse( $properties );
		
		foreach ( $properties as $property ) {
			$innerdesc = new SMWSomeProperty( $property, $innerdesc );
		}
		
		$result = $innerdesc;
		
		return $this->finishLinkDescription( $chunk, false, $result, $setNS, $label );
	}

	/**
	 * Parse an article description (the part of an inline query that
	 * is in between "[[" and the closing "]]" assuming it is not specifying
	 * a category or property) and create a suitable description.
	 * The first chunk behind the "[[" has already been read and is
	 * passed as a parameter.
	 */
	protected function getArticleDescription( $firstchunk, &$setNS, &$label ) {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		
		$chunk = $firstchunk;
		$result = null;
		$continue = true;
		// $innerdesc = null;
		
		while ( $continue ) {
			if ( $chunk == '<q>' ) { // no subqueries of the form [[<q>...</q>]] (not needed)
				$this->m_errors[] = wfMsgForContent( 'smw_misplacedsubquery' );
				return null;
			}
			
			$list = preg_split( '/:/', $chunk, 3 ); // ":Category:Foo" "User:bar"  ":baz" ":+"
			
			if ( ( $list[0] == '' ) && ( count( $list ) == 3 ) ) {
				$list = array_slice( $list, 1 );
			}
			if ( ( count( $list ) == 2 ) && ( $list[1] == '+' ) ) { // try namespace restriction
				global $wgContLang;
				$idx = $wgContLang->getNsIndex( $list[0] );
				
				if ( $idx !== false ) {
					$result = $this->addDescription( $result, new SMWNamespaceDescription( $idx ), false );
				}
			} else {
				$value = SMWDataValueFactory::newTypeIDValue( '_wpg', $chunk );
				
				if ( $value->isValid() ) {
					$result = $this->addDescription( $result, new SMWValueDescription( $value ), false );
				}
			}

			$chunk = $this->readChunk( '\[\[|\]\]|\|\||\|' );
			
			if ( $chunk == '||' ) {
				$chunk = $this->readChunk( '\[\[|\]\]|\|\||\|' );
				$continue = true;
			} else {
				$continue = false;
			}
		}

		return $this->finishLinkDescription( $chunk, true, $result, $setNS, $label );
	}

	protected function finishLinkDescription( $chunk, $hasNamespaces, $result, &$setNS, &$label ) {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		
		if ( $result === null ) { // no useful information or concrete error found
			$this->m_errors[] = wfMsgForContent( 'smw_badqueryatom' );
		} elseif ( !$hasNamespaces && $setNS && ( $this->m_defaultns !== null ) ) {
			$result = $this->addDescription( $result, $this->m_defaultns );
			$hasNamespaces = true;
		}
		
		$setNS = $hasNamespaces;

		// terminate link (assuming that next chunk was read already)
		if ( $chunk == '|' ) {
			$chunk = $this->readChunk( '\]\]' );
			
			if ( $chunk != ']]' ) {
				$label .= $chunk;
				$chunk = $this->readChunk( '\]\]' );
			} else { // empty label does not add to overall label
				$chunk = ']]';
			}
		}
		
		if ( $chunk != ']]' ) {
			// What happended? We found some chunk that could not be processed as
			// link content (as in [[Category:Test<q>]]) and there was no label to
			// eat it. Or the closing ]] are just missing entirely.
			if ( $chunk != '' ) {
				$this->m_errors[] = wfMsgForContent( 'smw_misplacedsymbol', htmlspecialchars( $chunk ) );
				
				// try to find a later closing ]] to finish this misshaped subpart
				$chunk = $this->readChunk( '\]\]' );
				
				if ( $chunk != ']]' ) {
					$chunk = $this->readChunk( '\]\]' );
				}
			}
			if ( $chunk == '' ) {
				$this->m_errors[] = wfMsgForContent( 'smw_noclosingbrackets' );
			}
		}
		
		return $result;
	}

	/**
	 * Get the next unstructured string chunk from the query string.
	 * Chunks are delimited by any of the special strings used in inline queries
	 * (such as [[, ]], <q>, ...). If the string starts with such a delimiter,
	 * this delimiter is returned. Otherwise the first string in front of such a
	 * delimiter is returned.
	 * Trailing and initial spaces are ignored if $trim is true, and chunks
	 * consisting only of spaces are not returned.
	 * If there is no more qurey string left to process, the empty string is
	 * returned (and in no other case).
	 *
	 * The stoppattern can be used to customise the matching, especially in order to
	 * overread certain special symbols.
	 *
	 * $consume specifies whether the returned chunk should be removed from the
	 * query string.
	 */
	protected function readChunk( $stoppattern = '', $consume = true, $trim = true ) {
		if ( $stoppattern == '' ) {
			$stoppattern = '\[\[|\]\]|::|:=|<q>|<\/q>|^' . $this->m_categoryprefix .
			               '|^' . $this->m_conceptprefix . '|\|\||\|';
		}
		$chunks = preg_split( '/[\s]*(' . $stoppattern . ')/u', $this->m_curstring, 2, PREG_SPLIT_DELIM_CAPTURE );
		if ( count( $chunks ) == 1 ) { // no matches anymore, strip spaces and finish
			if ( $consume ) {
				$this->m_curstring = '';
			}
			
			return $trim ? trim( $chunks[0] ):$chunks[0];
		} elseif ( count( $chunks ) == 3 ) { // this should generally happen if count is not 1
			if ( $chunks[0] == '' ) { // string started with delimiter
				if ( $consume ) {
					$this->m_curstring = $chunks[2];
				}
				
				return $trim ? trim( $chunks[1] ):$chunks[1];
			} else {
				if ( $consume ) {
					$this->m_curstring = $chunks[1] . $chunks[2];
				}
				
				return $trim ? trim( $chunks[0] ):$chunks[0];
			}
		} else { return false; }  // should never happen
	}

	/**
	 * Enter a new subblock in the query, which must at some time be terminated by the
	 * given $endstring delimiter calling popDelimiter();
	 */
	protected function pushDelimiter( $endstring ) {
		array_push( $this->m_sepstack, $endstring );
	}

	/**
	 * Exit a subblock in the query ending with the given delimiter.
	 * If the delimiter does not match the top-most open block, false
	 * will be returned. Otherwise return true.
	 */
	protected function popDelimiter( $endstring ) {
		$topdelim = array_pop( $this->m_sepstack );
		return ( $topdelim == $endstring );
	}

	/**
	 * Extend a given description by a new one, either by adding the new description
	 * (if the old one is a container description) or by creating a new container.
	 * The parameter $conjunction determines whether the combination of both descriptions
	 * should be a disjunction or conjunction.
	 *
	 * In the special case that the current description is NULL, the new one will just
	 * replace the current one.
	 *
	 * The return value is the expected combined description. The object $curdesc will
	 * also be changed (if it was non-NULL).
	 */
	protected function addDescription( $curdesc, $newdesc, $conjunction = true ) {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		$notallowedmessage = 'smw_noqueryfeature';
		if ( $newdesc instanceof SMWSomeProperty ) {
			$allowed = $this->m_queryfeatures & SMW_PROPERTY_QUERY;
		} elseif ( $newdesc instanceof SMWClassDescription ) {
			$allowed = $this->m_queryfeatures & SMW_CATEGORY_QUERY;
		} elseif ( $newdesc instanceof SMWConceptDescription ) {
			$allowed = $this->m_queryfeatures & SMW_CONCEPT_QUERY;
		} elseif ( $newdesc instanceof SMWConjunction ) {
			$allowed = $this->m_queryfeatures & SMW_CONJUNCTION_QUERY;
			$notallowedmessage = 'smw_noconjunctions';
		} elseif ( $newdesc instanceof SMWDisjunction ) {
			$allowed = $this->m_queryfeatures & SMW_DISJUNCTION_QUERY;
			$notallowedmessage = 'smw_nodisjunctions';
		} else {
			$allowed = true;
		}
		
		if ( !$allowed ) {
			$this->m_errors[] = wfMsgForContent( $notallowedmessage, str_replace( '[', '&#x005B;', $newdesc->getQueryString() ) );
			return $curdesc;
		}
		
		if ( $newdesc === null ) {
			return $curdesc;
		} elseif ( $curdesc === null ) {
			return $newdesc;
		} else { // we already found descriptions
			if ( ( ( $conjunction )  && ( $curdesc instanceof SMWConjunction ) ) ||
			     ( ( !$conjunction ) && ( $curdesc instanceof SMWDisjunction ) ) ) { // use existing container
				$curdesc->addDescription( $newdesc );
				return $curdesc;
			} elseif ( $conjunction ) { // make new conjunction
				if ( $this->m_queryfeatures & SMW_CONJUNCTION_QUERY ) {
					return new SMWConjunction( array( $curdesc, $newdesc ) );
				} else {
					$this->m_errors[] = wfMsgForContent( 'smw_noconjunctions', str_replace( '[', '&#x005B;', $newdesc->getQueryString() ) );
					return $curdesc;
				}
			} else { // make new disjunction
				if ( $this->m_queryfeatures & SMW_DISJUNCTION_QUERY ) {
					return new SMWDisjunction( array( $curdesc, $newdesc ) );
				} else {
					$this->m_errors[] = wfMsgForContent( 'smw_nodisjunctions', str_replace( '[', '&#x005B;', $newdesc->getQueryString() ) );
					return $curdesc;
				}
			}
		}
	}
}