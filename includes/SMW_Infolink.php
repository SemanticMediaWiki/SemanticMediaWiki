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
	 * Create a new link to some internal page or to some external URL.
	 */
	public function SMWInfolink($internal, $caption, $target, $style=false) {
		$this->internal = $internal;
		$this->caption = $caption;
		$this->target = $target;
		$this->style = $style;
	}

	/**
	 * Return hyperlink for this infolink in HTML format.
	 */
	public function getHTML($skin) {
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
				return $start . $skin->makeKnownLinkObj(Title::newFromText($this->target), $this->caption) . $end;
			} else {
				return '';
			}
		} else {
			return $start . "<a href=\"$this->target\">$this->caption</a>" . $end;
		}
	}

	/**
	 * Return hyperlink for this infolink in wiki format.
	 */
	public function getWikiText() {
		if ($this->style !== false) {
			smwfRequireHeadItem(SMW_HEADER_STYLE); // make SMW styles available
			$start = "<span class=\"$this->style\">";
			$end = '</span>';
		} else {
			$start = '';
			$end = '';
		}
		if ($this->internal) {
			if (preg_match('/(.*)(\[|\]|<|>|&gt;|&lt;|\'\'|{|})(.*)/', $this->target) != 0 ) {
				return ''; // give up if illegal characters occur,
				           // TODO: we would need a skin to provide an ext URL in this case
			}
			return $start . "[[$this->target|$this->caption]]" . $end;
		} else {
			return $start . "[$this->target $this->caption]" . $end;
		}
	}

}
