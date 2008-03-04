<?php

/**
 * SMWExporter is a class for converting internal page-based data (SMWSemanticData) into
 * a format for easy serialisation in OWL or RDF.
 *
 * @author Markus Krötzsch
 * @note AUTOLOADED
 */
class SMWExporter {

	static protected $m_exporturl = false;


	/**
	 * Make sure that necessary base URIs are initialised properly.
	 */
	static public function initBaseURIs() {
		if (SMWExporter::$m_exporturl !== false) return;

		global $smwgNamespace; // complete namespace for URIs (with protocol, usually http://)
		if (''==$smwgNamespace) {
			$resolver = Title::makeTitle( NS_SPECIAL, 'URIResolver');
			$smwgNamespace = $resolver->getFullURL() . '/';
		}
		if ($smwgNamespace[0] == '.') {
			$resolver = Title::makeTitle( NS_SPECIAL, 'URIResolver');
			$smwgNamespace = "http://" . substr($smwgNamespace, 1) . $resolver->getLocalURL() . '/';
		}

		$title = Title::makeTitle( NS_SPECIAL, 'ExportRDF' );
		SMWExporter::$m_exporturl = '&wikiurl;' . $title->getPrefixedURL();
	}

	/**
	 * Create exportable data from a given semantic data record. If given, the string $modifier is used
	 * as a modifier to the URI of the subject (e.g. a unit for properties).
	 */
	static public function makeExportData(/*SMWSemanticData*/ $semdata, $modifier = '') {
		SMWExporter::initBaseURIs();
		///TODO: currently the subject is a Title; should change to SMWWikiPageValue (needs Factbox changes)
		$subject = SMWDataValueFactory::newTypeIDValue('_wpg');
		$subj_title = $semdata->getSubject();
		$subject->setValues($subj_title->getDBKey(), $subj_title->getNamespace());
		$result = $subject->getExportData();

		// first set some general parameters for export
		global $smwgOWLFullExport; // export like individual (even if Category/Property)
		$indexp = ((($subj_title->getNamespace() != SMW_NS_PROPERTY) && 
		            ($subj_title->getNamespace() != NS_CATEGORY)) || $smwgOWLFullExport);
		$category_pe = NULL;
		$subprop_pe = NULL;
		switch ($subj_title->getNamespace()) {
			case NS_CATEGORY:
				$category_pe = SMWExporter::getSpecialElement('rdfs','subClassOf');
				$equality_pe = SMWExporter::getSpecialElement('owl','equivalentClass');
				$maintype_pe = SMWExporter::getSpecialElement('owl','Class');
				$label = $subj_title->getText();
			break;
			case SMW_NS_PROPERTY:
				if ($indexp) {
					$category_pe = SMWExporter::getSpecialElement('rdfs','subClassOf');
				}
				$subprop_pe = SMWExporter::getSpecialElement('rdfs','subPropertyOf');
				$equality_pe = SMWExporter::getSpecialElement('owl','equivalentProperty');
				$types = $semdata->getPropertyValues(SMW_SP_HAS_TYPE);
				/// TODO: improved mechanism for selecting property types is needed.
				if (count($types)>0) {
					$typeid = (current($types)->isUnary())?current($types)->getXSDValue():'__nry';
				} else {
					$typeid = '';
				}
				switch ($typeid) {
					case '_anu':
						$maintype_pe = SMWExporter::getSpecialElement('owl','AnnotationProperty');
					break;
					case '': case '_wpg': case '_uri': case '_ema': case '__nry':
						$maintype_pe = SMWExporter::getSpecialElement('owl','ObjectProperty');
					break;
					default:
						$maintype_pe = SMWExporter::getSpecialElement('owl','DatatypeProperty');
				}
				$label = $subj_title->getText();
			break;
			default:
				$category_pe = SMWExporter::getSpecialElement('rdf','type');
				$equality_pe = SMWExporter::getSpecialElement('owl','sameAs');
				$maintype_pe = SMWExporter::getSpecialElement('swivt','Subject');
				$label = $subj_title->getPrefixedText();
		}

		// export standard properties
		if ($modifier != '') {
			$label .= ' (' . $modifier . ')';
		}
		$ed = new SMWExpData(new SMWExpLiteral($label));
		$result->addPropertyObjectValue(SMWExporter::getSpecialElement('rdfs','label'), $ed);
		$ed = new SMWExpData(new SMWExpResource('&wikiurl;' . $subj_title->getPrefixedURL()));
		$result->addPropertyObjectValue(SMWExporter::getSpecialElement('swivt','page'), $ed);
		$ed = new SMWExpData(new SMWExpResource(SMWExporter::$m_exporturl . '/' . $subj_title->getPrefixedURL()));
		$result->addPropertyObjectValue(SMWExporter::getSpecialElement('rdfs','isDefinedBy'), $ed);
		$result->addPropertyObjectValue(SMWExporter::getSpecialElement('rdf','type'), new SMWExpData($maintype_pe));
		if ($modifier != '') { // make variant and possibly add meta data on base properties
			if ($subj_title->getNamespace() == SMW_NS_PROPERTY) {
				$ed = new SMWExpData(new SMWExpLiteral($modifier, NULL, 'http://www.w3.org/2001/XMLSchema#string'));
				$result->addPropertyObjectValue(SMWExporter::getSpecialElement('swivt','modifier'), $ed);
 				$result->addPropertyObjectValue(SMWExporter::getSpecialElement('swivt','baseProperty'), new SMWExpData($result->getSubject()));
			}
			$result->setSubject( $result->getSubject()->makeVariant($modifier) );
		}

		// export properties based on stored data
		foreach($semdata->getProperties() as $key => $property) {
			if ($property instanceof Title) { // normal property
				if (!$indexp) continue; // no properties for schema elements
				$pe = SMWExporter::getPropertyElement($property);
				foreach ($semdata->getPropertyValues($property) as $dv) {
					$ed = $dv->getExportData();
					$pem = ($dv->getUnit() != false)?$pe->makeVariant($dv->getUnit()):$pe;
					if ($ed !== NULL) {
						$result->addPropertyObjectValue($pem, $ed);
					}
				}
			} else { // special property
				$pe = NULL;
				switch ($property) {
					case SMW_SP_HAS_CATEGORY:
						$pe = $category_pe;
					break;
					case SMW_SP_HAS_URI:
						$pe = $equality_pe;
					break;
					case SMW_SP_SUBPROPERTY_OF:
						$pe = $subprop_pe;
					break;
					case SMW_SP_REDIRECTS_TO: /// TODO: currently no check for avoiding OWL DL illegal redirects is done
						$pe = $equality_pe;
					break;
				}
				if ($pe !== NULL) {
					foreach ($semdata->getPropertyValues($property) as $dv) {
						$ed = $dv->getExportData();
						if ($ed !== NULL) {
							$result->addPropertyObjectValue($pe, $ed);
						}
					}
				}
			}
		}

		return $result;
	}



	/**
	 * Create an SMWExportElement for some property (currently a Title).
	 */
	static protected function getPropertyElement($property) {
		$name = SMWExporter::encodeURI(urlencode($property->getDBKey()));
		if (in_array($name[0], array('-','0','1','2','3','4','5','6','7','8','9'))) { // illegal as first local name char in XML
			global $wgContLang;
			$name = SMWExporter::encodeURI(urlencode(str_replace(' ', '_', $wgContLang->getNsText(SMW_NS_PROPERTY)) . ':')) . $name;
			$namespaceid = 'wiki';
			$namespace = '&wiki;';
		} else {
			$namespaceid = 'property';
			$namespace = '&property;';
		}
		$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
		$dv->setValues($property->getDBKey(), $property->getNamespace());
		return new SMWExpResource($name, $dv, $namespace, $namespaceid);
	}

	/**
	 * Create an SMWExportElement for some special element that belongs to a known vocabulary.
	 * The parameter given must be a supported namespace id (e.g. "rdfs") and a local name (e.g. "label").
	 * Returns NULL if $namespace is not known.
	 */
	static public function getSpecialElement($namespace, $localname) {
		$namespaces = array(
			'swivt' => '&swivt;',
			'rdfs'  => '&rdfs;',
			'rdf'   => '&rdf;',
			'owl'   => '&owl;',
		);
		if (array_key_exists($namespace,$namespaces)) {
			return new SMWExpResource($localname, NULL, $namespaces[$namespace], $namespace);
		} else {
			return NULL;
		}
	}

	/**
	 * This function escapes symbols that might be problematic in XML in a uniform
	 * and injective way. It is used to encode URIs.
	 */
	static function encodeURI($uri) {
		$uri = str_replace( '-', '-2D', $uri);
		//$uri = str_replace( '_', '-5F', $uri); //not necessary
		$uri = str_replace( array(':', '"','#','&',"'",'+','%'),
		                    array('-3A', '-22','-23','-26','-27','-2B','-'),
		                    $uri);
		return $uri;
	}

}
