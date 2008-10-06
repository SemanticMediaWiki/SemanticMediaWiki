<?php
/**
 * Create vCard exports
 * @file
 * @ingroup SMWQuery
 */

/**
 * Printer class for creating vCard exports
 * @author Markus Krötzsch
 * @author Denny Vrandecic
 * @author Frank Dengler
 * @ingroup SMWQuery
 */
class SMWvCardResultPrinter extends SMWResultPrinter {
	protected $m_title = '';
	protected $m_description = '';

	public function getMimeType($res) {
		return 'text/x-vcard';
	}

	public function getFileName($res) {
		if ($this->getSearchLabel(SMW_OUTPUT_WIKI) != '') {
			return str_replace(' ', '_',$this->getSearchLabel(SMW_OUTPUT_WIKI)) . '.vcf';
		} else {
			return 'vCard.vcf';
		}
	}

	protected function getResultText($res, $outputmode) {
		global $smwgIQRunningNumber, $wgSitename, $wgServer, $wgRequest;
		$result = '';
		$items = array();
		if ($outputmode == SMW_OUTPUT_FILE) { // make vCard file
			if ($this->m_title == '') {
				$this->m_title = $wgSitename;
			}
			$row = $res->getNext();
			while ( $row !== false ) {
				$wikipage = $row[0]->getNextObject(); // get the object
				// name
				$prefix = ''; // something like 'Dr.'
				$firstname = ''; // given name
				$additionalname = ''; // typically the "middle" name (second first name)
				$lastname = ''; // family name
				$suffix = ''; // things like "jun." or "sen."
				$fullname = ''; // the "formatted name", may be independent from first/lastname & co.
				// contacts
				$emails = array();
				$tels = array();
				$addresses = array();
				// organisational details:
				$organization = ''; // any string
				$jobtitle ='';
				$role = '';
				$department ='';
				// other stuff
				$category ='';
				$birthday = ''; // a date
				$url =''; // homepage, a legal URL
				$note =''; // any text

				foreach ($row as $field) {
					// later we may add more things like a generic
					// mechanism to add non-standard vCard properties as well
					// (could include funny things like geo, description etc.)
					$req = $field->getPrintRequest();
					if ( (strtolower($req->getLabel()) == "name")) {
						$value = current($field->getContent()); // save only the first
						if ($value !== false) {
							$fullname = $value->getShortWikiText();
						}
					}
					if ( (strtolower($req->getLabel()) == "prefix")) {
						foreach ($field->getContent() as $value) {
							$prefix .= ($prefix?',':'') . $value->getShortWikiText();
						}
					}
					if ( (strtolower($req->getLabel()) == "suffix")) {
						foreach ($field->getContent() as $value) {
							$suffix .= ($suffix?',':'') . $value->getShortWikiText();
						}
					}
					if ( (strtolower($req->getLabel()) == "firstname")) {
						$value = current($field->getContent()); // save only the first
						if ($value !== false) {
							$firstname = $value->getShortWikiText();
						}
					}
					if ( (strtolower($req->getLabel()) == "extraname")) {
						foreach ($field->getContent() as $value) {
							$additionalname .= ($additionalname?',':'') . $value->getShortWikiText();
						}
					}
					if ( (strtolower($req->getLabel()) == "lastname")) {
						$value = current($field->getContent()); // save only the first
						if ($value !== false) {
							$lastname = $value->getShortWikiText();
						}
					}
					if ( (strtolower($req->getLabel()) == "birthday") && ($req->getTypeID() == "_dat") ) {
						$value = current($field->getContent()); // save only the first
						if ($value !== false) {
							$birthday =  $value->getXSDValue();
						}
					}
					if ( (strtolower($req->getLabel()) == "homepage") && ($req->getTypeID() == "_uri") ) {
						$value = current($field->getContent()); // save only the first
						if ($value !== false) {
							$url =  $value->getXSDValue();
						}
					}
					if ( strtolower($req->getLabel()) == "note" ) {
						foreach ($field->getContent() as $value) {
							$note .= ($note?', ':'') . $value->getShortWikiText();
						}
					}
					if (strtolower($req->getLabel()) == "email") {
						foreach ($field->getContent() as $entry) {
							$emails[] = new SMWvCardEmail('internet', $entry->getShortWikiText());
						}
					}
					if (strtolower($req->getLabel()) == "workphone") {
						foreach ($field->getContent() as $entry) {
							$tels[] = new SMWvCardTel('WORK',$entry->getShortWikiText());
						}
					}

					if (strtolower($req->getLabel()) == "cellphone") {
						foreach ($field->getContent() as $entry) {
							$tels[] = new SMWvCardTel('CELL',$entry->getShortWikiText());
						}
					}
					if (strtolower($req->getLabel()) == "homephone") {
						foreach ($field->getContent() as $entry) {
							$tels[] = new SMWvCardTel('HOME',$entry->getShortWikiText());
						}
					}
					if ( (strtolower($req->getLabel()) == "organization")) {
						$value = current($field->getContent()); // save only the first
						if ($value !== false) {
							$organization = $value->getShortWikiText();
						}
					}

				}
				$pagetitle = $wikipage->getTitle();
				$items[] = new SMWvCardEntry($pagetitle, $prefix, $firstname, $lastname, $additionalname, $suffix, $fullname, $tels, $addresses, $emails, $birthday, $jobtitle, $role, $organization, $department, $category, $url, $note);
            	$row = $res->getNext();
			}
            foreach ($items as $item) {
				$result .= $item->text();
			}
		} else { // just make link to vcard
			if ($this->getSearchLabel($outputmode)) {
				$label = $this->getSearchLabel($outputmode);
			} else {
				wfLoadExtensionMessages('SemanticMediaWiki');
				$label = wfMsgForContent('smw_vcard_link');
			}
			$link = $res->getQueryLink($label);
			$link->setParameter('vcard','format');
			if ($this->getSearchLabel(SMW_OUTPUT_WIKI) != '') {
				$link->setParameter($this->getSearchLabel(SMW_OUTPUT_WIKI),'searchlabel');
			}
			if (array_key_exists('limit', $this->m_params)) {
				$link->setParameter($this->m_params['limit'],'limit');
			} else { // use a reasonable default limit
				$link->setParameter(20,'limit');
			}
			$result .= $link->getText($outputmode,$this->mLinker);
			$this->isHTML = ($outputmode == SMW_OUTPUT_HTML); // yes, our code can be viewed as HTML if requested, no more parsing needed
		}
		return $result;
	}

}

/**
 * Represents a single entry in an vCard
 * @ingroup SMWQuery
 */
class SMWvCardEntry {
	private $uri;
	private $label;
	private $fullname;
	private $firstname;
	private $lastname;
	private $additionalname;
	private $prefix;
	private $suffix;
	private $tels = array();
	private $addresses = array();
	private $emails = array();
	private $birthday;
	private $dtstamp;
	private $title;
	private $role;
	private $organization;
	private $department;
	private $category;
	private $note;

	/**
	 * Constructor for a single item in the vcard. Requires the URI of the item.
	 */
	public function SMWVCardEntry(Title $t, $prefix, $firstname, $lastname, $additionalname, $suffix, $fullname, $tels, $addresses, $emails, $birthday, $jobtitle, $role, $organization, $department, $category, $url, $note) {
		global $wgServer;
		$this->uri = $t->getFullURL();
		$this->url = $url;
		// read fullname or guess it in a simple way from other names that are given
		if ($fullname != '') {
			$this->label = $fullname;
		} elseif ($firstname . $lastname != '') {
			$this->label = $firstname . (( ($firstname!='') && ($lastname!='') )?' ':'') .  $lastname;
		} else {
			$this->label = $t->getText();
		}
		$this->label = SMWVCardEntry::vCardEscape($this->label);
		// read firstname and lastname, or guess it from other names that are given
		if ($firstname . $lastname == '') { // guessing needed
			$nameparts = explode(' ', $this->label);
			// Accepted forms for guessing:
			// "Lastname"
			// "Firstname Lastname"
			// "Firstname <Additionalnames> Lastname"
			$this->lastname = SMWVCardEntry::vCardEscape(array_pop($nameparts));
			if (count($nameparts)>0) $this->firstname = SMWVCardEntry::vCardEscape(array_shift($nameparts));
			foreach ($nameparts as $name) {
				$this->additionalname .= ($this->additionalname!=''?',':'') . SMWVCardEntry::vCardEscape($name);
			}
		} else {
			$this->firstname = SMWVCardEntry::vCardEscape($firstname);
			$this->lastname = SMWVCardEntry::vCardEscape($lastname);
		}
		if ($additionalname != '') $this->additionalname = $additionalname; // no escape, can be a value list
			// ^ overwrite above guessing in that case
		$this->prefix = SMWVCardEntry::vCardEscape($prefix);
		$this->suffix = SMWVCardEntry::vCardEscape($suffix);
		$this->tels = $tels;
		$this->addresses = $addresses;
		$this->emails = $emails;
		$this->birthday = $birthday;
		$this->title = SMWVCardEntry::vCardEscape($jobtitle);
		$this->role = SMWVCardEntry::vCardEscape($role);
		$this->organization = SMWVCardEntry::vCardEscape($organization);
		$this->department = SMWVCardEntry::vCardEscape($department);
		$this->category = $category; // allow non-escaped "," in here for making a list of categories
		$this->note = SMWVCardEntry::vCardEscape($note);

		$article = new Article($t);
		$this->dtstamp  = $article->getTimestamp();
	}


	/**
	 * Creates the vCard output for a single item.
	 */
	public function text() {
		$text  = "BEGIN:VCARD\r\n";
		$text .= "VERSION:3.0\r\n";
		// N and FN are required properties in vCard 3.0, we need to write something there
		$text .= "N;CHARSET=UTF-8:$this->lastname;$this->firstname;$this->additionalname;$this->prefix;$this->suffix\r\n";
		$text .= "FN;CHARSET=UTF-8:$this->label\r\n";
		// heuristic for setting confidentiality level of vCard:
		global $wgGroupPermissions;
		if ( (array_key_exists('*', $wgGroupPermissions)) &&
		     (array_key_exists('read', $wgGroupPermissions['*'])) ) {
			$public = $wgGroupPermissions['*']['read'];
		} else {
			$public = true;
		}
		$text .= ($public?'CLASS:PUBLIC':'CLASS:CONFIDENTIAL') . "\r\n";
		if ($this->birthday !== "") $text .= "BDAY:$this->birthday\r\n";
		if ($this->title !== "") $text .= "TITLE;CHARSET=UTF-8:$this->title\r\n";
		if ($this->role !== "") $text .= "ROLE;CHARSET=UTF-8:$this->role\r\n";
		if ($this->organization !== "") $text .= "ORG;CHARSET=UTF-8:$this->organization;$this->department\r\n";
		if ($this->category !== "") $text .= "CATEGORIES;CHARSET=UTF-8:$this->category\r\n";
		foreach ($this->emails as $entry) $text .= $entry->createVCardEmailText();
		foreach ($this->addresses as $entry) $text .= $entry->createVCardAddressText();
		foreach ($this->tels as $entry) $text .= $entry->createVCardTelText();
		if ($this->note !== "") $text .= "NOTE;CHARSET=UTF-8:$this->note\r\n";
		$text .= "SOURCE;CHARSET=UTF-8:$this->uri\r\n";
		$text .= "PRODID:-////Semantic MediaWiki\r\n";
		$text .= "REV:$this->dtstamp\r\n";
		$text .= "URL:" . ($this->url?$this->url:$this->uri) . "\r\n";
		$text .= "UID:$this->uri\r\n";
		$text .= "END:VCARD\r\n";
		return $text;
	}

	public static function vCardEscape($text) {
		return str_replace(array('\\',',',':',';'), array('\\\\','\,','\:','\;'),$text);
	}

}

/**
 * Represents a single address entry in an vCard entry.
 * @ingroup SMWQuery
 */
class SMWvCardAddress{
	private $type;
	private $postofficebox;
	private $extendedaddress;
	private $street;
	private $locality;
	private $region;
	private $postalcode;
	private $country;

	/**
	 * Constructor for a single address item in the vcard item.
	 */
	public function __construct($type, $postofficebox, $extendedaddress, $street, $locality, $region, $postalcode, $country) {
		$this->type = $type;
		$this->postofficebox = SMWVCardEntry::vCardEscape($postofficebox);
		$this->extendedaddress = SMWVCardEntry::vCardEscape($extendedaddress);
		$this->street = SMWVCardEntry::vCardEscape($street);
		$this->locality = SMWVCardEntry::vCardEscape($locality);
		$this->region = SMWVCardEntry::vCardEscape($region);
		$this->postalcode = SMWVCardEntry::vCardEscape($postalcode);
		$this->country = SMWVCardEntry::vCardEscape($country);
	}

	/**
	 * Creates the vCard output for a single address item.
	 */
	public function createVCardAddressText(){
		if ($this->type == "") $this->type="work";
		$text  =  "ADR;TYPE=$this->type;CHARSET=UTF-8:$this->postofficebox;$this->extendedaddress;$this->street;$this->locality;$this->region;$this->postalcode;$this->country\r\n";
		return $text;
	}
}

/**
 * Represents a single telephone entry in an vCard entry.
 * @ingroup SMWQuery
 */
class SMWvCardTel{
	private $type;
	private $telnumber;

	/**
	 * Constructor for a single telephone item in the vcard item.
	 */
	public function __construct($type, $telnumber) {
		$this->type = $type;  // may be a vCard value list using ",", no escaping
		$this->telnumber = SMWVCardEntry::vCardEscape($telnumber); // escape to be sure
	}

	/**
	 * Creates the vCard output for a single telephone item.
	 */
	public function createVCardTelText(){
		if ($this->type == "") $this->type="work";
		$text  =  "TEL;TYPE=$this->type:$this->telnumber\r\n";
		return $text;
	}
}

/**
 * Represents a single email entry in an vCard entry.
 * @ingroup SMWQuery
 */
class SMWvCardEmail{
	private $type;
	private $emailaddress;

	/**
	 * Constructor for a email telephone item in the vcard item.
	 */
	public function __construct($type, $emailaddress) {
		$this->type = $type;
		$this->emailaddress = $emailaddress; // no escape, normally not needed anyway
	}

	/**
	 * Creates the vCard output for a single email item.
	 */
	public function createVCardEmailText(){
		if ($this->type == "") $this->type="internet";
		$text  =  "EMAIL;TYPE=$this->type:$this->emailaddress\r\n";
		return $text;
	}
}
