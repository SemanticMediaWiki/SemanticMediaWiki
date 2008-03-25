<?php
/**
 * This file contains the SMWInfolink class.
 *
 * @author Markus Krötzsch
 */

/**
 * This class mainly is a container to store URLs for the factbox in a
 * clean way. The class provides methods for creating source code for
 * realising them in wiki or html contexts.
 */
class SMWInfolink {
	private $target;        // the actual link target
	private $caption;       // the label for the link
	private $style;         // CSS class of a span to embedd the link into, or
	                        // false if no extra style is required
	private $internal;      // indicates whether $target is a page name (true) or URL (false)


	/**
	 * Create a new link to some internal page or to some external URL.
	 */
	public function __construct($internal, $caption, $target, $style=false) {
		$this->internal = $internal;
		$this->caption = $caption;
		$this->target = $target;
		$this->style = $style;
	}


	/**
	 * Create a new link to an internal page $target. All parameters are mere strings
	 * as used by wiki users
	 */
	public static function newInternalLink($caption, $target, $style=false) {
		return new SMWInfolink(true,$caption,$target,$style);
	}

	/**
	 * Create a new link to an external location $url.
	 */
	public static function newExternalLink($caption, $url, $style=false) {
		return new SMWInfolink(false,$caption,$url,$style);
	}

	/**
	 * Static function to construct links to property searches.
	 */
	public static function newPropertySearchLink($caption,$propertyname,$value,$style = 'smwsearch') {
		global $wgContLang;
		return new SMWInfolink(true,$caption,$wgContLang->getNsText(NS_SPECIAL) . ':SearchByProperty/' .  $propertyname . '::' . $value, $style);
	}

	/**
	 * Static function to construct links to inverse property searches.
	 */
	public static function newInversePropertySearchLink($caption,$subject,$propertyname,$style = false) {
		global $wgContLang;
		return new SMWInfolink(true,$caption,$wgContLang->getNsText(NS_SPECIAL) . ':PageProperty/' .  $subject . '::' . $propertyname, $style);
	}

	/**
	 * Static function to construct links to the browsing special.
	 */
	public static function newBrowsingLink($caption,$titletext,$style = 'smwbrowse') {
		global $wgContLang;
		return new SMWInfolink(true,$caption,$wgContLang->getNsText(NS_SPECIAL) . ':Browse/' .  $titletext, $style);
	}


	/**
	 * Returns a suitable text string for displaying this link in HTML or wiki, depending
	 * on whether $outputformat is SMW_OUTPUT_WIKI or SMW_OUTPUT_HTML.
	 *
	 * The parameter $linker controls linking of values such as titles and should
	 * be some Linker object (for HTML output). Some default linker will be created
	 * if needed and not provided.
	 */
	public function getText($outputformat, $linker = NULL) {
		if ($this->style !== false) {
			smwfRequireHeadItem(SMW_HEADER_STYLE); // make SMW styles available
			$start = "<span class=\"$this->style\">";
			$end = '</span>';
		} else {
			$start = '';
			$end = '';
		}
		if ($this->internal) {
			$title = Title::newFromText($this->target);
			if ($title !== NULL) {
				if ($outputformat == SMW_OUTPUT_WIKI) {
					$link = "[[$this->target|$this->caption]]";
				} else { // SMW_OUTPUT_HTML
					$link = $this->getLinker($linker)->makeKnownLinkObj($title, $this->caption);
				}
			} else { // Title creation failed, maybe illegal symbols or too long; make a direct URL link 
			         // (only possible if offending target parts belong to some parameter
			         //  that can be separated from title text,
			         //  e.g. as in Special:Bla/il<leg>al -> Special:Bla&p=il&lt;leg&gt;al)
				return '';
// 				if ($outputformat == SMW_OUTPUT_WIKI) {
// 					$link = '[' . SMWExporter::expandURI('&wikiurl;' . rawurlencode($this->target)) . " $this->caption]";
// 				} else {
// 					$link = '<a href="' . SMWExporter::expandURI('&wikiurl;' . rawurlencode($this->target)) . "\">$this->caption</a>";
// 				}
			}
		} else {
			if ($outputformat == SMW_OUTPUT_WIKI) {
				$link = "[$this->target $this->caption]";
			} else {
				$link = "<a href=\"$this->target\">$this->caption</a>";
			}
		}

		return $start . $link . $end;
	}


	/**
	 * Return a Linker object, using the parameter $linker if not NULL, and creatng a new one
	 * otherwise. $linker is usually a user skin object, while the fallback linker object is 
	 * not customised to user settings.
	 */
	protected function getLinker(&$linker = NULL) {
		if ($linker === NULL) {
			$linker = new Linker();
		} else {
			return $linker;
		}
	}


	/**
	 * Return hyperlink for this infolink in HTML format.
	 */
	public function getHTML($linker) {
		return $this->getText(SMW_OUTPUT_HTML, $linker);
// 		if ($this->style !== false) {
// 			smwfRequireHeadItem(SMW_HEADER_STYLE); // make SMW styles available
// 			$start = "<span class=\"$this->style\">";
// 			$end = '</span>';
// 		} else {
// 			$start = '';
// 			$end = '';
// 		}
// 		if ($this->internal) {
// 			$title = Title::newFromText($this->target);
// 			if ($title !== NULL) {
// 				return $start . $linker->makeKnownLinkObj(Title::newFromText($this->target), $this->caption) . $end;
// 			} else { // Title creation failed, maybe illegal symbols or too long
// 				return '';
// 			}
// 		} else {
// 			return $start . "<a href=\"$this->target\">$this->caption</a>" . $end;
// 		}
	}

	/**
	 * Return hyperlink for this infolink in wiki format.
	 */
	public function getWikiText($linker = NULL) {
		return $this->getText(SMW_OUTPUT_WIKI, $linker);
// 		if ($this->style !== false) {
// 			smwfRequireHeadItem(SMW_HEADER_STYLE); // make SMW styles available
// 			$start = "<span class=\"$this->style\">";
// 			$end = '</span>';
// 		} else {
// 			$start = '';
// 			$end = '';
// 		}
// 		if ($this->internal) {
// 			if (preg_match('/(.*)(\[|\]|<|>|&gt;|&lt;|\'\'|{|})(.*)/u', $this->target) != 0 ) {
// 				return ''; // give up if illegal characters occur,
// 				           /// TODO: we would need a skin to provide an ext URL in this case
// 			}
// 			return $start . "[[$this->target|$this->caption]]" . $end;
// 		} else {
// 			return $start . "[$this->target $this->caption]" . $end;
// 		}
	}

}
