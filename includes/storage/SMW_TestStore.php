<?php
/**
 * Test implementation of SMW's storage abstraction layer.
 * @bug Currently not really functional.
 * @file
 * @ingroup SMWStore
 * @author Markus Krötzsch
 */

/**
 * Storage access class for testing purposes. No persitent storage is implemented, but
 * all methods return non-empty result sets that can be used for testing purposes.
 *
 * @bug This implementation requires updates for testing new storage capabilities.
 * @ingroup SMWStore
 */
class SMWTestStore extends SMWStore {

///// Reading methods /////

	function getSemanticData($subject, $filter = false) {
		return NULL;
	}

	function getSpecialValues($subject, $specialprop, $requestoptions = NULL) {
		// TODO
		if ($specialprop === SMW_SP_INSTANCE_OF) { // category membership
			if ( ($requestoptions->limit == -1) || $requestoptions->limit > 8) {
				$requestoptions->limit = 5;
			}
			return $this->getTestTitles($requestoptions, NS_CATEGORY);
		} elseif ($specialprop === SMW_SP_REDIRECTS_TO) {
			return array(); // TODO: any better idea?
		} elseif ($specialprop === SMW_SP_HAS_TYPE) {
			global $smwgContLang;
			$name = mb_strtoupper($subject->getText());
			if ( mb_substr_count($name,'INT') > 0 ) {
				return array(SMWDataValueFactory::newTypeIDValue('__typ', 'Integer'));
			} elseif ( mb_substr_count($name,'FLOAT') > 0 ) {
				return array(SMWDataValueFactory::newTypeIDValue('__typ', 'Float'));
			} elseif ( mb_substr_count($name,'DATE') > 0 ) {
				return array(SMWDataValueFactory::newTypeIDValue('__typ', 'Date'));
			} elseif ( mb_substr_count($name,'COORD') > 0 ) {
				return array(SMWDataValueFactory::newTypeIDValue('__typ', 'Geographic coordinate'));
			} elseif ( mb_substr_count($name,'ENUM') > 0 ) {
				return array(SMWDataValueFactory::newTypeIDValue('__typ', 'Enumeration'));
			} else {
				return array(SMWDataValueFactory::newTypeIDValue('__typ', 'String'));
			}
		} elseif ($specialprop === SMW_SP_POSSIBLE_VALUE) {
			return array('enum_val1', 'enum_val5', 'enum_val3', 'enum_val2', 'enum_val4');
		} else {
			return array();
		}
	}

	function getSpecialSubjects($specialprop, SMWDataValue $value, $requestoptions = NULL) {
		if ($specialprop === SMW_SP_INSTANCE_OF) { // category membership
			if ( !($value instanceof Title) || ($value->getNamespace() != NS_CATEGORY) ) {
				return array();
			}
			return $this->getTestTitles($requestoptions);
		} elseif ($specialprop === SMW_SP_REDIRECTS_TO) { // redirections
			return array(); // TODO: any better idea?
		} elseif ($specialprop === SMW_SP_HAS_TYPE) { // redirections
			return $this->getTestTitles($requestoptions, SMW_NS_PROPERTY);
		} else {
			return $this->getTestTitles($requestoptions);
		}
	}

	function getPropertyValues($subject, SMWPropertyValue $property, $requestoptions = NULL, $outputformat = '') {
		$type = $this->getSpecialValues($property,SMW_SP_HAS_TYPE);
		$type = $type[0];
		$valarray = array();
		switch ($th->getID()) {
			case 'int':
				$valarray = array('10', '5000000000000','0','-1234','12','17','42');
			break;
			case 'float':
				$valarray = array('1.23', '5.234e+30','-0.000001','4','12.1','17.1','42.1');
			break;
			case 'datetime':
				$valarray = array('2007-04-01', '2007-12-31T18:25:21');
			break;
			case 'enum':
				$valarray = array('enum_val1', 'enum_val3', 'enum_val2');
			break;
			case 'geocoords':
				$valarray = array('38&#176;1&#8242;12&#8243; N, 122&#176;1&#8242;1.2&#8243; W');
			break;
			case 'string':
				$valarray = array('Test', 'Some longer string','Üničode','Some [[markup]]','&lt;b&gt;Bug if bold!&lt;/b&gt;');
			break;
		}
		$result = Array();
		foreach ($valarray as $val) {
			$dv = SMWDataValueFactory::newTypeObjectValue($type);
			$dv->setAttribute($property->getText());
			$dv->setDBkeys(array($val));
			$result[] = $dv;
		}
		return $result;
	}

	function getPropertySubjects(SMWPropertyValue $property, $value, $requestoptions = NULL) {
		if ( !$value->isValid() ) {
			return array();
		}
		return $this->getTestTitles($requestoptions);
	}

	function getAllPropertySubjects(SMWPropertyValue $property, $requestoptions = NULL) {
		return $this->getTestTitles($requestoptions);
	}

	function getProperties($subject, $requestoptions = NULL) {
		if ( ($requestoptions->limit == -1) || $requestoptions->limit > 8) {
			$requestoptions->limit = 8;
		}
		return $this->getTestTitles($requestoptions, SMW_NS_PROPERTY);
	}

	function getInProperties(SMWDataValue $object, $requestoptions = NULL) {
		return $this->getTestTitles($requestoptions, SMW_NS_RELATION);
	}

///// Writing methods /////

	function deleteSubject(Title $subject) {
	}

	function updateData(SMWSemanticData $data) {
	}

	function changeTitle(Title $oldtitle, Title $newtitle, $pageid, $redirid=0) {
	}

///// Query answering /////

	function getQueryResult(SMWQuery $query) {
		$prs = $query->getDescription()->getPrintrequests(); // ignore print requests at deepder levels

		// Here, the actual SQL query building and execution must happen. Loads of work.
		// For testing purposes, we assume that the outcome is the following array of titles
		// (the eventual query result format is quite certainly different)
		$qr = array(Title::newFromText('Angola'), Title::newFromText('Namibia'));

		// create result by executing print statements for everything that was fetched
		///TODO: use limit and offset values
		$result = new SMWQueryResult($prs);
		foreach ($qr as $qt) {
			$row = array();
			foreach ($prs as $pr) {
				switch ($pr->getMode()) {
					case SMWPrintRequest::PRINT_THIS:
						$row[] = new SMWResultArray(array($qt), $pr);
						break;
					case SMWPrintRequest::PRINT_CATS:
						$row[] = new SMWResultArray($this->getSpecialValues($qt,SMW_SP_INSTANCE_OF), $pr);
						break;
					case SMWPrintRequest::PRINT_PROP:
						///TODO: respect given datavalue (desired unit), needs extension of getAttributeValues()
						$row[] = new SMWResultArray($this->getAttributeValues($qt,$pr->getTitle()), $pr);
						break;
				}
			}
			$result->addRow($row);
		}

		return $result;
	}

///// Special page functions /////

	function getPropertiesSpecial($requestoptions = NULL) {
		return array();
	}

	function getUnusedPropertiesSpecial($requestoptions = NULL) {
		return array();
	}

	function getWantedPropertiesSpecial($requestoptions = NULL) {
		return array();
	}

	function getStatistics() {
		return array('PROPUSES' => 0, 'USEDPROPS' => 0, 'DECLPROPS' => 0);
	}

///// Setup store /////

	function setup($verbose = true) {
		return true;
	}

	function drop($verbose = true) {
		return true;
	}

	function refreshData(&$index, $count, $namespaces = false, $usejobs = true) {
		$index = -1;
		return 1;
	}


///// Private methods /////

	/**
	 * Return a set of titles as a (random) answer to some request,
	 * but adhere to the given options (limit, sorting)
	 */
	private function getTestTitles($requestoptions, $namespace = -1) {
		$result = Array();
		$initarray = Array();
		if ($namespace == SMW_NS_PROPERTY) {
			$initarray = array( 'Teststring','Testint','Testfloat','Testcoords','Testdate','Testenum');
		}
		for ($i=0; $i<300; $i++) {
			global $wgContLang;
			if ($namespace < 0) {
				$ns = (($i%5)*2);
			} else {
				$ns = $namespace;
			}

			if ($i < count($initarray)) {
				$text = $initarray[$i];
				$key = $initarray[$i];
			} else {
				$firstchar = chr(65+($i*17)%25);
				$key = $firstchar . $i;
				if ($ns == 0) {
					$text = $firstchar . $i . '_(Test)';
				} else {
					$text = $firstchar . $i . '_(Test' . $wgContLang->getNsText($ns) . ')';
				}
			}
			$result[$key] = Title::newFromText($text, $ns);
		}
		// the order of applying the following is crucial:
		if ($requestoptions !== NULL) {
			if ($requestoptions->boundary !== NULL) {
				$newresult = array();
				foreach ($result as $key => $r) {
					if ($requestoptions->ascending) {
						if ($requestoptions->include_boundary) {
							$ok = ($r->getText() >= $requestoptions->boundary);
						} else {
							$ok = ($r->getText() > $requestoptions->boundary);
						}
					} else {
						if ($requestoptions->include_boundary) {
							$ok = ($r->getText() <= $requestoptions->boundary);
						} else {
							$ok = ($r->getText() < $requestoptions->boundary);
						}
					}
					if ($ok) {
						$newresult[$key] = $r;
					}
				}
				$result = $newresult;
			}
			if ($requestoptions->sort) {
				if ($requestoptions->ascending) {
					ksort($result);
				} else {
					krsort($result);
				}
			}
			if ($requestoptions->offset > 0) {
				$result = array_slice($result, $requestoptions->offset);
			}
			if ($requestoptions->limit >= 0) {
				$result = array_slice($result, 0, $requestoptions->limit);
			}
		}
		return array_values($result);
	}

	/**
	 * Transform input parameters into a suitable string of additional SQL conditions.
	 * The parameter $valuecol defines the string name of the column to which
	 * value restrictions etc. are to be applied.
	 * @param $requestoptions object with options
	 * @param $valuecol name of SQL column to which conditions apply
	 * @param $labelcol name of SQL column to which string conditions apply, if any
	 */
// 	protected function getSQLConditions($requestoptions, $valuecol, $labelcol = NULL) {
// 		$sql_conds = '';
// 		if ($requestoptions !== NULL) {
// 			$db =& wfGetDB( DB_MASTER ); // TODO: use slave?
// 			// <snip>
// 			if ($labelcol !== NULL) { // apply string conditions
// 				foreach ($requestoptions->getStringConditions() as $strcond) {
// 					$string = str_replace(array('_', ' '), array('\_', '\_'), $strcond->string);
// 					switch ($strcond->condition) {
// 						case SMWStringCondition::STRCOND_PRE:
// 							$string .= '%';
// 							break;
// 						case SMWStringCondition::STRCOND_POST:
// 							$string = '%' . $string;
// 							break;
// 						case SMWStringCondition::STRCOND_MID:
// 							$string = '%' . $string . '%';
// 							break;
// 					}
// 					$sql_conds .= ' AND ' . $labelcol . ' LIKE ' . $db->addQuotes($string);
// 				}
// 			}
// 		}
// 		return $sql_conds;
// 	}

}


