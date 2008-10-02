<?php
/**
 * This file contains essentially all SMW code that affects parsing by reading some
 * special SMW syntax.
 * @file
 * @ingroup SMW
 * @author Markus Krötzsch
 * @author Denny Vrandecic 
 */

/**
 * Static class to collect all functions related to parsing wiki text in SMW. 
 * It includes all parser function declarations and hooks.
 * @ingroup SMW
 */
class SMWParserExtensions {

	/// Temporarily store parser that cannot be passed to call-back functions otherwise.
	protected static $mTempParser;
	/// Internal state for switchin off/on SMW link annotations during parsing
	protected static $mTempStoreAnnotations;

	/**
	 *  This method will be called before an article is displayed or previewed.
	 *  For display and preview we strip out the semantic properties and append them
	 *  at the end of the article.
	 */
	static public function onInternalParseBeforeLinks(&$parser, &$text) {
		global $smwgStoreAnnotations, $smwgLinksInValues;
		SMWParseData::stripMagicWords($text, $parser);
		// store the results if enabled (we have to parse them in any case, in order to
		// clean the wiki source for further processing)
		$smwgStoreAnnotations = smwfIsSemanticsProcessed($parser->getTitle()->getNamespace());
		SMWParserExtensions::$mTempStoreAnnotations = true; // used for [[SMW::on]] and [[SMW:off]]
	
		// process redirects, if any
		// (it seems that there is indeed no more direct way of getting this info from MW)
		$rt = Title::newFromRedirect($text);
		if ($rt !== NULL) {
			$dv = SMWDataValueFactory::newSpecialValue(SMW_SP_REDIRECTS_TO,$rt->getPrefixedText());
			if ($smwgStoreAnnotations) {
				SMWParseData::getSMWData($parser)->addSpecialValue(SMW_SP_REDIRECTS_TO,$dv);
			}
		}

		SMWParserExtensions::$mTempParser = $parser; // only used in subsequent callbacks, forgotten afterwards
		// In the regexp matches below, leading ':' escapes the markup, as
		// known for Categories.
		// Parse links to extract semantic properties
		if ($smwgLinksInValues) { // more complex regexp -- lib PCRE may cause segfaults if text is long :-(
			$semanticLinkPattern = '/\[\[                 # Beginning of the link
			                        (?:([^:][^]]*):[=:])+ # Property name (or a list of those)
			                        (                     # After that:
			                          (?:[^|\[\]]         #   either normal text (without |, [ or ])
			                          |\[\[[^]]*\]\]      #   or a [[link]]
			                          |\[[^]]*\]          #   or an [external link]
			                        )*)                   # all this zero or more times
			                        (?:\|([^]]*))?        # Display text (like "text" in [[link|text]]), optional
			                        \]\]                  # End of link
			                        /xu';
			$text = preg_replace_callback($semanticLinkPattern, 'SMWParserExtensions::parsePropertiesCallback', $text);
		} else { // simpler regexps -- no segfaults found for those, but no links in values
			$semanticLinkPattern = '/\[\[                 # Beginning of the link
			                        (?:([^:][^]]*):[=:])+ # Property name (or a list of those)
			                        ([^\[\]]*)            # content: anything but [, |, ]
			                        \]\]                  # End of link
			                        /xu';
			$text = preg_replace_callback($semanticLinkPattern, 'SMWParserExtensions::simpleParsePropertiesCallback', $text);
		}

		// add link to RDF to HTML header
		SMWOutputs::requireHeadItem('smw_rdf', '<link rel="alternate" type="application/rdf+xml" title="' .
		                    $parser->getTitle()->getPrefixedText() . '" href="' .
		                    htmlspecialchars($parser->getOptions()->getSkin()->makeSpecialUrl(
		                       'ExportRDF/' . $parser->getTitle()->getPrefixedText(), 'xmlmime=rdf'
		                    )) . "\" />");

		SMWOutputs::commitToParser($parser);
		return true; // always return true, in order not to stop MW's hook processing!
	}

	/**
	 * This callback function strips out the semantic attributes from a wiki
	 * link. Expected parameter: array(linktext, properties, value|caption)
	 * This function is a preprocessing for smwfParsePropertiesCallback, and
	 * takes care of separating value and caption (instead of leaving this to
	 * a more complex regexp).
	 */
	static public function simpleParsePropertiesCallback($semanticLink) {
		$value = '';
		$caption = false;
		if (array_key_exists(2,$semanticLink)) {
			$parts = explode('|',$semanticLink[2]);
			if (array_key_exists(0,$parts)) {
				$value = $parts[0];
			}
			if (array_key_exists(1,$parts)) {
				$caption = $parts[1];
			}
		}
		if ($caption !== false) {
			return SMWParserExtensions::parsePropertiesCallback(array($semanticLink[0],$semanticLink[1],$value,$caption));
		} else {
			return SMWParserExtensions::parsePropertiesCallback(array($semanticLink[0],$semanticLink[1],$value));
		}
	}

	/**
	 * This callback function strips out the semantic attributes from a wiki
	 * link. Expected parameter: array(linktext, properties, value, caption)
	 */
	static public function parsePropertiesCallback($semanticLink) {
		global $smwgInlineErrors, $smwgStoreAnnotations;
		wfProfileIn("smwfParsePropertiesCallback (SMW)");
		if (array_key_exists(1,$semanticLink)) {
			$property = $semanticLink[1];
		} else { $property = ''; }
		if (array_key_exists(2,$semanticLink)) {
			$value = $semanticLink[2];
		} else { $value = ''; }
	
		if ($property == 'SMW') {
			switch ($value) {
				case 'on':  SMWParserExtensions::$mTempStoreAnnotations = true;  break;
				case 'off': SMWParserExtensions::$mTempStoreAnnotations = false; break;
			}
			wfProfileOut("smwfParsePropertiesCallback (SMW)");
			return '';
		}
	
		if (array_key_exists(3,$semanticLink)) {
			$valueCaption = $semanticLink[3];
		} else { $valueCaption = false; }
	
		//extract annotations and create tooltip
		$properties = preg_split('/:[=:]/u', $property);
		foreach($properties as $singleprop) {
			$dv = SMWParseData::addProperty($singleprop,$value,$valueCaption, SMWParserExtensions::$mTempParser, $smwgStoreAnnotations && SMWParserExtensions::$mTempStoreAnnotations);
		}
		$result = $dv->getShortWikitext(true);
		if ( ($smwgInlineErrors && $smwgStoreAnnotations && SMWParserExtensions::$mTempStoreAnnotations) && (!$dv->isValid()) ) {
			$result .= $dv->getErrorText();
		}
		wfProfileOut("smwfParsePropertiesCallback (SMW)");
		return $result;
	}

	/**
	 * This hook registers parser functions and hooks to the given parser. It is
	 * called during SMW initialisation. Note that parser hooks are something different
	 * than MW hooks in general, which explains the two-level registration.
	 */
	public static function registerParserFunctions(&$parser) {
		$parser->setHook( 'ask', 'SMWParserExtensions::doAskHook' );
		$parser->setFunctionHook( 'ask', 'SMWParserExtensions::doAsk' );
		$parser->setFunctionHook( 'show', 'SMWParserExtensions::doShow' );
		$parser->setFunctionHook( 'info', 'SMWParserExtensions::doInfo' );
		$parser->setFunctionHook( 'concept', 'SMWParserExtensions::doConcept' );
		$parser->setFunctionHook( 'set', 'SMWParserExtensions::doConcept' );
		if (defined('SFH_OBJECT_ARGS')) { // only available since MediaWiki 1.13
			$parser->setFunctionHook( 'declare', 'SMWParserExtensions::doDeclare', SFH_OBJECT_ARGS );
		}
		return true; // always return true, in order not to stop MW's hook processing!
	}

	/**
	 * Function for handling the {{\#ask }} parser function. It triggers the execution of inline
	 * query processing and checks whether (further) inline queries are allowed.
	 */
	static public function doAsk(&$parser) {
		global $smwgQEnabled, $smwgIQRunningNumber;
		if ($smwgQEnabled) {
			$smwgIQRunningNumber++;
			$params = func_get_args();
			array_shift( $params ); // we already know the $parser ...
			$result = SMWQueryProcessor::getResultFromFunctionParams($params,SMW_OUTPUT_WIKI);
		} else {
			wfLoadExtensionMessages('SemanticMediaWiki');
			$result = smwfEncodeMessages(array(wfMsgForContent('smw_iq_disabled')));
		}
		SMWOutputs::commitToParser($parser);
		return $result;
	}

	/**
	 * The \<ask\> parser hook processing part. This has been replaced by the 
	 * parser function \#ask and should no longer be used.
	 */
	static public function doAskHook($querytext, $params, &$parser) {
		global $smwgQEnabled, $smwgIQRunningNumber;
		if ($smwgQEnabled) {
			$smwgIQRunningNumber++;
			$result = SMWQueryProcessor::getResultFromHookParams($querytext,$params,SMW_OUTPUT_HTML);
		} else {
			wfLoadExtensionMessages('SemanticMediaWiki');
			$result = smwfEncodeMessages(array(wfMsgForContent('smw_iq_disabled')));
		}
		SMWOutputs::commitToParser($parser);
		return $result;
	}

	/**
	 * Function for handling the {{\#show }} parser function. The \#show function is
	 * similar to \#ask but merely prints some property value for a specified page.
	 */
	static public function doShow(&$parser) {
		global $smwgQEnabled, $smwgIQRunningNumber;
		if ($smwgQEnabled) {
			$smwgIQRunningNumber++;
			$params = func_get_args();
			array_shift( $params ); // we already know the $parser ...
			$result = SMWQueryProcessor::getResultFromFunctionParams($params,SMW_OUTPUT_WIKI,SMWQueryProcessor::INLINE_QUERY,true);
		} else {
			wfLoadExtensionMessages('SemanticMediaWiki');
			$result = smwfEncodeMessages(array(wfMsgForContent('smw_iq_disabled')));
		}
		SMWOutputs::commitToParser($parser);
		return $result;
	}

	/**
	* Function for handling the {{\#concept }} parser function. This parser function provides a special input
	* facility for defining concepts, and it displays the resulting concept description.
	*/
	static public function doConcept(&$parser) {
		global $smwgQDefaultNamespaces, $smwgQMaxSize, $smwgQMaxDepth, $smwgPreviousConcept, $wgContLang;
		wfLoadExtensionMessages('SemanticMediaWiki');
		// The global $smwgConceptText is used to pass information to the MW hooks for storing it,
		// $smwgPreviousConcept is used to detect if we already have a concept defined for this page.
		$title = $parser->getTitle();
		if ($title->getNamespace() != SMW_NS_CONCEPT) {
			$result = smwfEncodeMessages(array(wfMsgForContent('smw_no_concept_namespace')));
			SMWOutputs::commitToParser($parser);
			return $result;
		} elseif (isset($smwgPreviousConcept) && ($smwgPreviousConcept == $title->getText())) {
			$result = smwfEncodeMessages(array(wfMsgForContent('smw_multiple_concepts')));
			SMWOutputs::commitToParser($parser);
			return $result;
		}
		$smwgPreviousConcept = $title->getText();

		// process input:
		$params = func_get_args();
		array_shift( $params ); // we already know the $parser ...
		$concept_input = str_replace(array('&gt;','&lt;'),array('>','<'),array_shift( $params )); // use first parameter as concept (query) string
		/// NOTE: the str_replace above is required in MediaWiki 1.11, but not in MediaWiki 1.14
		$query = SMWQueryProcessor::createQuery($concept_input, array('limit' => 20, 'format' => 'list'), SMWQueryProcessor::CONCEPT_DESC);
		$concept_text = $query->getDescription()->getQueryString();
		$concept_docu = array_shift( $params ); // second parameter, if any, might be a description

		$dv = SMWDataValueFactory::newSpecialValue(SMW_SP_CONCEPT_DESC);
		$dv->setValues($concept_text, $concept_docu, $query->getDescription()->getQueryFeatures(), $query->getDescription()->getSize(), $query->getDescription()->getDepth());
		if (SMWParseData::getSMWData($parser) !== NULL) {
			SMWParseData::getSMWData($parser)->addSpecialValue(SMW_SP_CONCEPT_DESC,$dv);
		}

		// display concept box:
		$rdflink = SMWInfolink::newInternalLink(wfMsgForContent('smw_viewasrdf'), $wgContLang->getNsText(NS_SPECIAL) . ':ExportRDF/' . $title->getPrefixedText(), 'rdflink');
		SMWOutputs::requireHeadItem(SMW_HEADER_STYLE);

		$result = '<div class="smwfact"><span class="smwfactboxhead">' . wfMsgForContent('smw_concept_description',$title->getText()) .
				(count($query->getErrors())>0?' ' . smwfEncodeMessages($query->getErrors()):'') .
				'</span>' . '<span class="smwrdflink">' . $rdflink->getWikiText() . '</span>' . '<br />' .
				($concept_docu?"<p>$concept_docu</p>":'') .
				'<pre>' . str_replace('[', '&#x005B;', $concept_text) . "</pre>\n</div>";
		SMWOutputs::commitToParser($parser);
		return $result;
	}

	/**
	 * Function for handling the {{\#info }} parser function. This function creates a tooltip like
	 * the one used by SMW for giving hints.
	 * @note This feature is at risk and may vanish or change in future versions.
	 */
	static public function doInfo(&$parser) {
		$params = func_get_args();
		array_shift( $params ); // we already know the $parser ...
		$content = array_shift( $params ); // use only first parameter, ignore rest (may get meaning later)
		$result = smwfEncodeMessages(array($content), 'info');
		SMWOutputs::commitToParser($parser);
		return $result;
	}

	/**
	 * Function for handling the {{\#set }} parser function. This is used for adding annotations
	 * silently.
	 *
	 * Usage:
	 * {{\#set:
	 *   population = 13000
	 * | area = 396 km²
	 * | sea = Adria
	 * }}
	 * This creates annotations with the properties as stated on the left side, and the
	 * values on the right side.
	 *
	 * @param[in] &$parser Parser  The current parser
	 * @return nothing
	 */
	static public function doSet( &$parser ) {
		$params = func_get_args();
		array_shift( $params ); // we already know the $parser ...
		foreach ($params as $p)
			if (trim($p) != "") {
				$parts = explode("=", trim($p));
				if (count($parts)==2) {
					$property = $parts[0];
					$subject = $parts[1];
					SMWParseData::addProperty( $property, $subject, false, $parser, true );
				}
			}
		SMWOutputs::commitToParser($parser); // not obviously required, but let us be sure
		return '';
	}

	/**
	 * Function for handling the {{\#declare }} parser function. It is used for declaring template parameters
	 * that should automagically be annotated when the template is used.
	 *
	 * Usage:
	 * {{\#declare:Author=Author\#list|Publisher=editor}}
	 */
	static public function doDeclare( Parser &$parser, PPFrame $frame, $args ) {
		if ($frame->isTemplate()) {
			foreach ($args as $arg)
				if (trim($arg) != "") {
					$expanded = trim( $frame->expand( $arg ));
					$parts = explode("=", $expanded, 2);
					if (count($parts)==1) {
						$propertystring = $expanded;
						$argumentname = $expanded;
					} else {
						$propertystring = $parts[0];
						$argumentname = $parts[1];
					}
					$property = Title::newFromText( $propertystring, SMW_NS_PROPERTY );
					//if ($property == null) continue;
					$argument = $frame->getArgument($argumentname);
					$valuestring = $frame->expand($argument);
					if ($property != null) {
						$type = SMWDataValueFactory::getPropertyObjectTypeID($property);
						if ($type == "_wpg") {
							$matches = array();
							preg_match_all("/\[\[([^\[\]]*)\]\]/", $valuestring, $matches);
							$objects = $matches[1];
							if (count($objects) == 0) {
								if (trim($valuestring) != '') {
									SMWParseData::addProperty( $propertystring, $valuestring, false, $parser, true );
								}
							} else {
								foreach ($objects as $object) {
									SMWParseData::addProperty( $propertystring, $object, false, $parser, true );
								}
							}
						} else {
							if (trim($valuestring) != '') {
								SMWParseData::addProperty( $propertystring, $valuestring, false, $parser, true );
							}
						}
						$value = SMWDataValueFactory::newPropertyObjectValue($property, $valuestring);
						//if (!$value->isValid()) continue;
					}
				}
		} else {
			// @todo Save as metadata
		}
		SMWOutputs::commitToParser($parser); // not obviously required, but let us be sure
		return '';
	}

}