<?php

/**
 * SMWExporter is a class for converting internal page-based data (SMWSemanticData) into
 * a format for easy serialisation in OWL or RDF.
 *
 * @author Markus Krötzsch
 * @note AUTOLOADED
 */
class SMWExporter {

	/**
	 * Make sure that necessary base URIs are initialised properly.
	 */
	static public function initBaseURIs() {
		global $smwgNamespace; // complete namespace for URIs (with protocol, usually http://)
		if (''==$smwgNamespace) {
			$resolver = Title::makeTitle( NS_SPECIAL, 'URIResolver');
			$smwgNamespace = $resolver->getFullURL() . '/';
		}
		if ($smwgNamespace[0] == '.') {
			$resolver = Title::makeTitle( NS_SPECIAL, 'URIResolver');
			$smwgNamespace = "http://" . substr($smwgNamespace, 1) . $resolver->getLocalURL() . '/';
		}
	}

	/**
	 * Create exportable data from a given semantic data record.
	 */
	static public function makeExportData(/*SMWSemanticData*/ $semdata) {
		SMWExporter::initBaseURIs();
		///TODO: currently the subject is a Title; should change to SMWWikiPageValue (needs Factbox changes)
		$subject = SMWDataValueFactory::newTypeIDValue('_wpg');
		$subject->setValues($semdata->getSubject()->getDBKey(), $semdata->getSubject()->getNamespace());
		$result = $subject->getExportData();

		foreach($semdata->getProperties() as $key => $property) {
			if ($property instanceof Title) { // normal property
				$pe = SMWExporter::getPropertyElement($property);
				foreach ($semdata->getPropertyValues($property) as $dv) {
					$ed = $dv->getExportData();
					$pem = ($dv->getUnit() != false)?$pe->makeVariant($dv->getUnit()):$pe;
					if ($ed !== NULL) {
						$result->addPropertyObjectValue($pem, $ed);
					}
				}
			} else { // special property
				/// TODO switch type of special property
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
		return new SMWExpResource($name, NULL, $namespace, $namespaceid);
	}

	/**
	 * This function escapes symbols that might be problematic in XML in a uniform
	 * and injective way. It is used to encode URIs.
	 */
	static function encodeURI($uri) {
		$uri = str_replace( '-', '-2D', $uri);
		//$uri = str_replace( ':', '-3A', $uri); //already done by PHP
		//$uri = str_replace( '_', '-5F', $uri); //not necessary
		$uri = str_replace( array('"','#','&',"'",'+','%'),
		                    array('-22','-23','-26','-27','-2B','-'),
		                    $uri);
		return $uri;
	}

}
