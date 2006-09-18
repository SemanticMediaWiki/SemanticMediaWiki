<?php
/**
 * @author Klaus Lassleben
 * @author Markus Krötzsch
 * @author Kai Hüner
 */

	require_once('SMW_Storage.php');
	require_once('SMW_SemanticData.php');

	/*********************************************************************/
	/* Hooks                                                             */
	/*********************************************************************/	

	/**
	*  This method will be called before an article is displayed or previewed.
	*  For display and preview we strip out the semantic relations and append them
	*  at the end of the article.
	*
	*  TODO: $strip_state is not used (and must not be used, since it is
	*        not relevant when moving the hook to internalParse()).
	*/
	function smwfParserHook(&$parser, &$text, &$strip_state)
	{
		smwfInitMessages(); // make sure that the messages are available

		// Init global storage for semantic data of this article.
		SMWSemanticData::initStorage($parser->mTitle,$parser->mOptions->getSkin());

		// In the regexp matches below, leading ':' escapes the markup, as
		// known for Categories.
		// Parse links to extract semantic relations
		$semanticLinkPattern = '(\[\[(([^:][^]]*)::)+([^\|\]]*)(\|([^]]*))?\]\])';
		$text = preg_replace_callback($semanticLinkPattern, 'smwfParseRelationsCallback', $text);

		// Parse links to extract attribute values
		$semanticLinkPattern = '(\[\[(([^:][^]]*):=)+([^\|\]]*)(\|([^]]*))?\]\])';
		$text = preg_replace_callback($semanticLinkPattern, 'smwfParseAttributesCallback', $text);

		// print the results if enabled (we have to parse them in any case, in order to
		// clean the wiki source for further processing)
		if ( smwfIsSemanticsProcessed($parser->mTitle->getNamespace()) ) {
			SMWSemanticData::printFactbox($text);
		}

		return true;
	}


	/**
	 * This hook is triggered later during parsing.  It inserts HTML code that
	 * would otherwise be escaped by the MediaWiki parser. Currently it is
	 * used to build the JavaScript tooltips.
	 */
	function smwfParserAfterTidyHook(&$parser, &$text)
	{
		// Parse span tags around semantic links containing tooltip info.
		// Note this must match the exact HTML smwfParseAttributesCallback() adds.
		$text = preg_replace_callback(
					'{
						<span\s*id="SMWtt"\s*title="	# look for HTML of attribute span tag with a title
						([^"]*)			# capture title up to closing quote
						"[^>]*>				# ignore everything to end of opening span tag
						(.*?)			# capture everything up to closing span tag with a minimal match
						</span>
					}x',
					'smwfParseAttributesAfterTidyCallback', $text);

		return true;
	}

	/**
	*  This method will be called after an article is saved
	*  and stores the semantic relations in the database. One
	*  could consider creating an object for deferred saving
	*  as used in other places of MediaWiki.
	*/
	function smwfSaveHook(&$article, &$user, &$text)
	{
		$title=$article->getTitle();
		return SMWSemanticData::storeData($title, smwfIsSemanticsProcessed($title->getNamespace()));
	}

	/**
	*  This method will be called whenever an article is deleted so that
	*  semantic relations are cleared appropriately.
	*/
	function smwfDeleteHook(&$article, &$user, &$reason)
	{
		$title=$article->getTitle();
		smwfDeleteRelations($title);
		smwfDeleteAttributes($title);
		smwfDeleteSpecialProperties($title);
		return true;
	}

	/**
	*  This method will be called whenever an article is moved so that
	*  semantic relations are moved accordingly.
	*/
	function smwfMoveHook(&$old_title, &$new_title, &$user, $pageid, $redirid)
	{
		smwfMoveAnnotations($old_title, $new_title);
		return true;
	}

	/*********************************************************************/
	/* Callbacks                                                         */
	/*********************************************************************/	

	/**
	* This callback function strips out the semantic relation from a 
	* wiki link. Retrieved data is stored in the static SMWSemanticData.
	*/
	function smwfParseRelationsCallback($semanticLink) {
		if (array_key_exists(2,$semanticLink)) {
			$relation = $semanticLink[2];
		} else { $relation = ''; }
		if (array_key_exists(3,$semanticLink)) { 
			$linkTarget = $semanticLink[3];
		} else { $linkTarget = ''; }
		if (array_key_exists(4,$semanticLink)) { 
			$linkCaption = $semanticLink[4];
			// answer to bug #1479616
			// removes the extra : that comes in from the automatic | expansion in links
			if (($linkCaption != '') && ($linkCaption[1] == ":")) {
				$linkCaption = "|" . mb_substr($linkCaption, 2);
			}
		} else { $linkCaption = ''; }

		//extract annotations
		$relations = explode('::', $relation);
		foreach($relations as $singleRelation) {
			SMWSemanticData::addRelation($singleRelation,$linkTarget);
		}

		//pass it back as a normal link
		return '[[:' . $linkTarget . $linkCaption . ']]';
	}

	/**
	* This callback function strips out the semantic attributes from a wiki 
	* link.
	*/
	function smwfParseAttributesCallback($semanticLink)
	{
		if (array_key_exists(2,$semanticLink)) {
			$attribute = $semanticLink[2];
		} else { $attribute = ''; }
		if (array_key_exists(3,$semanticLink)) {
			$value = $semanticLink[3];
		} else { $value = ''; }
		if (array_key_exists(4,$semanticLink)) {
			$valueCaption = $semanticLink[4];
		} else { $valueCaption = ''; }

		//extract annotations and create tooltip
		$attributes = explode(':=', $attribute);
		foreach($attributes as $singleAttribute) {
			$attr = SMWSemanticData::addAttribute($singleAttribute,$value);
		}

		//set text for result
		if ('' == $valueCaption) {
			$result = $attr->getUserValue();
		} else {
			$result = mb_substr( $valueCaption, 1 ); // remove initial '|'
		}

		// Set tooltip for result. smwfParserAfterTidyHook() matches this 
		// HTML to add JavaScript to the final article.
		if ($attr->getTooltip() != '') {
			$result = '<span id="SMWtt" title="' . $attr->getTooltip() . '" style="color:#B70">' . $result . '</span>';
		} //TODO: the above suggests significant technical improvements
		return $result;
	}


	/**
	 * This callback creates a tooltip based on JavaScript. The creation of
	 * the respective tags has to be performed after "TidyParse" in order to
	 * push the required HTML Tags (a, script) to the code. These would be
	 * escaped when inserted at an earlier stage.
	 */
	function smwfParseAttributesAfterTidyCallback($semanticLink)
	{
		// Here we read the data hosted in the "span"-container. A toolTip
		// is created to show the attribute values. To invoke the tooltip,
		// we have to use a javaScript function (createToolTip) which writes
		// a toolTip (div-Tag) to the document. Since the script-Tag would cause
		// a line break, we have to surround everything with a span-Tag.

		// if the tip is given as an array, we print each value as a line;
		if ((strstr(' = ', $semanticLink[1]) != -1) or (strstr(', ', $semanticLink[1]))) {
			$tip = str_replace(array(' = ', ', '),'<br/>', $semanticLink[1]);
		} else {
			$tip = $semanticLink[1];
		}
		$id      = uniqid('SMW_'); // parameter needed up to PHP4
		$result  = '<span><script type="text/javascript">/*<![CDATA[*/ createToolTip(\''.$id.'\', \''.$tip.'\');/*]]>*/</script>';
		$result .= '<a class="smwatr" onmouseover="showToolTip(\''.$id.'\')" onmouseout="hideToolTip()">'.$semanticLink[2].'</a></span>'; //no CamelCase for onmouse... -> W3C Validator

		return $result;
	}

?>