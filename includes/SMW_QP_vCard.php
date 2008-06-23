<?php
/**
 * Create vCard exports
 */

/**
 * Printer class for creating vCard exports
 * @author Markus Krötzsch
 * @author Denny Vrandecic
 * @author Frank Dengler
 * @note AUTOLOADED
 */
class SMWvCardResultPrinter extends SMWResultPrinter {
	protected $m_title = '';
	protected $m_description = '';

	public function getResult($results, $params, $outputmode) { // skip checks, results with 0 entries are normal
		$this->readParameters($params,$outputmode);
		return $this->getResultText($results,$outputmode) . $this->getErrorString($results);
	}

	public function getMimeType($res) {
		return 'text/x-vcard';
	}

	public function getFileName($res) {
		if ($this->mSearchlabel != '') {
			return str_replace(' ', '_',$this->mSearchlabel) . '.vcf';
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
				$firstname = '';
				$lastname = '';
				$birthday = '';
				$organization = '';
				$emails = array();
				$tels = array();
				$addresses = array();
				$additionalname = '';
				$honorprefix = '';
				$nickname = '';
				$jobtitle ='';
				$role = '';
				$department ='';
				$category ='';
				$url ='';
				$note ='';

				foreach ($row as $field) {
					// later we may add more things like a generic
					// mechanism to add whatever you want :)
					// could include funny things like geo, description etc. though
					$req = $field->getPrintRequest();
					if ( (strtolower($req->getLabel()) == "firstname")) {
						$value = current($field->getContent()); // save only the first
						if ($value !== false) {
							$firstname = $value->getShortWikiText();
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
				$title = $wikipage->getTitle();
				$items[] = new SMWvCardEntry($title, $firstname, $lastname, $additionalname, $honorprefix, $nickname, $tels, $addresses, $emails, $birthday, $jobtitle, $role, $organization, $department, $category, $url, $note);
            	$row = $res->getNext();
			}
            foreach ($items as $item) {
				$result .= $item->text();
			}
		} else { // just make link to vcard
			if ($this->mSearchlabel) {
				$label = $this->mSearchlabel;
			} else {
				$label = wfMsgForContent('smw_vcard_link');
			}
			$link = $res->getQueryLink($label);
			$link->setParameter('vcard','format');
			if ($this->mSearchlabel != '') {
				$link->setParameter($this->mSearchlabel,'searchlabel');
			}
			if (array_key_exists('limit', $this->m_params)) {
				$link->setParameter($this->m_params['limit'],'limit');
			} else { // use a reasonable default limit
				$link->setParameter(20,'limit');
			}

			$result .= $link->getText($outputmode,$this->mLinker);

		}

		return $result;
	}

}

/**
 * Represents a single entry in an vCard
 */
class SMWvCardEntry {

	private $uri;
	private $label;
	private $firstname;
	private $lastname;
	private $additionalname;
	private $honorprefix;
	private $nickname;
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
	public function SMWVCardEntry(Title $t, $firstname, $lastname, $additionalname, $honorprefix, $nickname, $tels, $addresses, $emails, $birthday, $title, $role, $organization, $department, $category, $url, $note) {
		global $wgServer;
		$this->uri = $t->getFullURL();
		$this->url = $url;
		$this->label = $t->getText();
		$this->firstname = $firstname;
		$this->lastname = $lastname;
		$this->additionalname = $additionalname;
		$this->honorprefix = $honorprefix;
		$this->nickname = $nickname;
		$this->tels = $tels;
		$this->addresses = $addresses;
		$this->emails = $emails;
		$this->birthday = $birthday;
		$this->title = $title;
		$this->role = $role;
		$this->organization = $organization;
		$this->department = $department;
		$this->category = $category;
		$this->note = $note;

		$article = new Article($t);
		$this->dtstamp  = $article->getTimestamp();
	}


	/**
	 * Creates the vCard output for a single item.
	 */
	public function text() {
		$text  = "BEGIN:VCARD\r\n";
		$text .= "VERSION:3.0\r\n";
		$text .= "N;CHARSET=UTF-8:$this->lastname;$this->firstname;$this->additionalname;$this->honorprefix\r\n";
		$text .= "FN;CHARSET=UTF-8:$this->label\r\n";
        $text .= "CLASS:PRIVATE\r\n";
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

}

/**
 * Represents a single address entry in an vCard entry.
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
    $this->postofficebox = $postofficebox;
    $this->extendedaddress = $extendedaddress;
    $this->street = $street;
    $this->locality = $locality;
    $this->region = $region;
    $this->postalcode = $postalcode;
    $this->country = $country;

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
 */
class SMWvCardTel{
	private $type;
	private $telnumber;

	/**
	 * Constructor for a single telephone item in the vcard item.
	 */
	public function __construct($type, $telnumber) {
		$this->type = $type;
		$this->telnumber = $telnumber;
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
 */
class SMWvCardEmail{
    private $type;
    private $emailaddress;

    /**
	 * Constructor for a email telephone item in the vcard item.
	 */
    public function __construct($type, $emailaddress) {
    $this->type = $type;
    $this->emailaddress = $emailaddress;
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
