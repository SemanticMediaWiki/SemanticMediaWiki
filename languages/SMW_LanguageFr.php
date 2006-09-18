<?php
/**
 * @author Pierre Matringe
 */

class SMW_LanguageFr {

/* private */ var $smwContentMessages = array(
	'smw_edithelp' => 'Aide à la rédaction de relations et d\'attributs',
	'smw_helppage' => 'Relations et attributs',
	'smw_viewasrdf' => 'Voir comme RDF',
	'smw_finallistconjunct' => ' et',					//utilisé dans "A, B, et C"
	'smw_factbox_head' => 'Faits relatifs à $1 &mdash; Recherche de pages similaires avec <span class="smwsearchicon">+</span>.',
	'smw_att_head' => 'Attributs',
	'smw_rel_head' => 'Relations à d\'autres articles',
	'smw_spec_head' => 'Propriétés spéciales',
	/*URIs that should not be used in objects in cases where users can provide URIs */
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => 'Désolé. Les URIs du domaine $1 ne sont pas disponible à cet emplacement',
	/*Messages and strings for inline queries*/
	'smw_iq_disabled' => "<span class='smwwarning'>Désolé. Les recherches dans les articles de ce wiki ne sont pas autorisées</span>",
	/*Messages and strings for ontology resued (import) */
	'smw_unknown_importns' => '[Désolé. Aucune fonction d\'import n\'est disponible pour l\'espace de nommage "$1".]',
	'smw_nonright_importtype' => '[L\'élément "$1" ne peut être employé que pour des articles de l\'espace de nommage "$2".]',
	'smw_wrong_importtype' => '[L\'élément "$1" ne peut être employé pour des articles de l\'espace de nommage domaine "$2".]',
	'smw_no_importelement' => '[Désolé. L\'élément "$1" n\'est pas disponible pour l\'importation.]',
	/*Messages and strings for basic datatype processing*/
	'smw_decseparator' => ',', 
	'smw_kiloseparator' => '.',
	'smw_unknowntype' => '[Oups ! Le type de données "$1" non supporté a été retourné à l\'attribut]',
	'smw_noattribspecial' => '[Oups ! La propriété spéciale "$1" n\'est pas un attribut (utilisez "::" au lieu de ":=")]',
	'smw_notype' => '[Oups ! Aucun type de donné n\'a été assigné à l\'attribut]',
	'smw_manytypes' => '[Oups ! Plusieurs types de données ont été assignés à l\'attribut]',
	'smw_emptystring' => '[Oups ! Les chaînes vides ne sont pas acceptées]',
	'smw_maxstring' => '[Sorry, string representation $1 is too long for this site.]',
	'smw_nointeger' => '[Oups ! "$1" n\'est pas un nombre entier]',
	'smw_nofloat' => '[Oups ! "$1" n\'est pas un nombre à virgule flottante]',
	'smw_infinite' => '[Désolé, le nombre $1 est trop long.]',
	'smw_infinite_unit' => '[Désolé, la conversion dans l\'unité $1 est impossible : le nombre est trop long.]',
	'smw_unexpectedunit' => 'Cet attribut ne supporte aucune conversion d\'unité',
	'smw_unsupportedunit' => 'La conversion de l\'unité "$1" n\'est pas supportée',
	/*Messages for geo coordinates parsing*/
	'smw_err_latitude' => 'Les indications sur la latitude (N, S) doivent être comprises entre 0 et 90. "$1" ne se trouve pas à l\'intérieur de ces limites !',
	'smw_err_longitude' => 'Les indications sur la longitude (E, O) doivent être comprises entre 0 et 180. "$1" ne se trouve pas à l\'intérieur de ces limites !',
	'smw_err_noDirection' => '[Oups ! Quelque chose ne va pas avec l\'indication "$1"]',
	'smw_err_parsingLatLong' => '[Oups ! Quelque chose ne va pas avec l\'indication "$1". Quelque chose dans la forme "1°2′3.4′′O" ou au minimum y ressemblant est attendu !]',
	'smw_err_wrongSyntax' => '[Oups ! Quelque chose ne va pas avec l\'indication "$1". Quelque chose dans la forme "1°2′3.4′′ O, 5°6′7.8′ N" ou au minimum y ressemblant est attendu !]',
	'smw_err_sepSyntax' => 'L\'expression "$1" semble être exacte, mais les valeurs de la latitude et de la longitude doivent être séparées par des signes tels que "," ou ";".',
	'smw_err_notBothGiven' => 'Une valeur doit être donnée pour la latitude (N, S) <i>et</i> la longitude (E, O).',
	/* additionals ... */
	'smw_label_latitude' => 'Latitude :',
	'smw_label_longitude' => 'Longitude :',
	'smw_findmaps' => 'Cartes géographiques',
	'smw_abb_north' => 'N',
	'smw_abb_east' => 'E',
	'smw_abb_south' => 'S',
	'smw_abb_west' => 'O',
	/*Messages for datetime parsing */
	'smw_nodatetime' => '[Oups ! La date "$1" n\'a pas été comprise. Le support des données calendaires est encore expérimental.]'
);

/* private */ var $smwUserMessages = array(
	'smw_devel_warning' => 'Cette fonction est encore en développement et n\'est peut-être pas encore opérationnelle. Il est peut-être judicieux de faire une sauvegarde du contenu du wiki avant toute utilisation de cette fonction.',
	/*Messages for Export RDF Special*/
	'exportrdf' => 'Exporter l\'article comme RDF', //name of this special
	'smw_exportrdf_docu' => '<p>Sur cette page, des parties du contenu d\'un article peuvent être exportées dans le format RDF. Veuillez entrer le nom des pages souhaitées dans la boîte de texte ci-dessous, <i>un nom par ligne </i>.<p/>',
	'smw_exportrdf_recursive' => 'Exporter également toutes les pages pertinentes de manière récursive. Cette possibilité peut aboutir à un très grand nombre de résultats !',
	'smw_exportrdf_backlinks' => 'Exporter également toutes les pages qui renvoient à des pages exportées. Produit un RDF dans lequel la navigation est facilitée.',
	/*Messages for Search Triple Special*/
	'searchtriple' => 'Recherche sémantique simple', //name of this special : Einfache semantische Suche
	'smw_searchtriple_header' => '<h1>Recherche de relations et d\'attributs</h1>',
	'smw_searchtriple_docu' => "<p>Utilisez le masque de recherche pour rechercher des articles selon certaines propriétés. La ligne supérieur est destinée à la recherche par relation, la ligne inférieure à la recherche par attribut. Certains champs peuvent être laissés vide pour obtenir plus de résultats. Cependant si la valeur d'un attribut est entrée (avec l'unité de mesure correspondante), le nom de l'attribut doit également être indiqué.</p>\n\n<p>Veuillez constater qu'il y a deux boutons de recherche. Appuyer sur la touche Entrée ne conduira peut-être pas à ce que soit menée la recherche souhaitée.</p>",
	'smw_searchtriple_subject' => 'Nom de l\'article (sujet):',
	'smw_searchtriple_relation' => 'Nom de la relation:',
	'smw_searchtriple_attribute' => 'Nom des attributs:',
	'smw_searchtriple_object' => 'Nom de l\'article (objet) (Objekt):',
	'smw_searchtriple_attvalue' => 'Valeur des attributs:',
	'smw_searchtriple_searchrel' => 'Recherche par Relation',
	'smw_searchtriple_searchatt' => 'Recherche par attribut',
	'smw_searchtriple_resultrel' => 'Résultats de la recherche (Relations)',
	'smw_searchtriple_resultatt' => 'Résultats de la recherche (attributs)',
	/*Messages for Relation Special*/
	'relations' => 'Relations',
	'smw_relations_docu' => 'Sur ce wiki, existent les relations suivantes:',
	/*Messages for Attributes Special*/
	'attributes' => 'Attributs',
	'smw_attributes_docu' => 'Sur ce wiki, existent les attributs suivants:',
	/*Messages for Unused Relations Special*/
	'unusedrelations' => 'Relations orphelines',
	'smw_unusedrelations_docu' => 'Des pages pour les relations suivantes existent, mais elles ne sont pas utilisées.',
	/*Messages for Unused Attributes Special*/
	'unusedattributes' => 'Attributs orphelins',
	'smw_unusedattributes_docu' => 'Des pages pour les attribut suivants existent, mais ils ne sont pas utilisés.',
	/* Messages for the refresh button */
	'tooltip-purge' => 'Réactualiser toutes les recherches et tous les brouillons de cette page.',
	'purge' => 'Réactualiser',
	/*Messages for Import Ontology Special*/
	'ontologyimport' => 'Importer l\'ontologie',
	'smw_ontologyimport_docu' => 'Cette page spéciale permet d\'importer des informations d\'une ontologie externe. Cette ontologie doit être dans un format RDF simplifié. Des informations supplémentaires sont disponibles dans la <a href="http://wiki.ontoworld.org/index.php/Help:Ontology_import">Documentation relative à l\'import d\'ontologie</a> en langues anglaise.',
	'smw_ontologyimport_action' => 'Importer',
	'smw_ontologyimport_return' => 'Revenir à <a href="$1">Importer l\'ontologie</a>.',	//Différence avec la version anglaise
	/*Messages for (data)Types Special*/
	'types' => 'Types de données',
	'smw_types_docu' => 'Les types de données suivants peuvent être assignées aux attributs. Chaque type de données a son propre article, dans lequel peuvent figurer des informations plus précises.'
);

/* private */ var $smwDatatypeLabels = array(
	'smw_string' => 'Chaîne de caractères',  // name of the string type
	'smw_int' => 'Nombre entier',  // name of the int type
	'smw_float' => 'Nombre à virgule flottange',  // name of the floating point type
	'smw_length' => 'Longueur',  // name of the length type
	'smw_area' => 'Étendue',  // name of the area type
	'smw_geolength' => 'Longitude',  // OBSOLETE name of the geolength type
	'smw_geoarea' => 'Aire géographique',  // OBSOLETE name of the geoarea type
	'smw_geocoordinate' => 'Coordonnées géographiques', // name of the geocoord type
	'smw_mass' => 'Masse',  // name of the mass type
	'smw_time' => 'Durée',  // name of the time type
	'smw_temperature' => 'Température',  // name of the temperature type
	'smw_datetime' => 'Date',  // name of the datetime (calendar) type	
	'smw_email' => 'Adresse électronique',  // name of the email (URI) type
	'smw_url' => 'URL',  // name of the URL type (string datatype property)
	'smw_uri' => 'URI',  // name of the URI type (object property)
	'smw_annouri' => 'Annotation-URI'  // name of the annotation URI type (annotation property)
);

/* private */ var $smwSpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'A le type de données',
	SMW_SP_HAS_URI   => 'URI équivalente',
	SMW_SP_IS_SUBRELATION_OF => 'Est une sous-relation de',
	SMW_SP_IS_SUBATTRIBUTE_OF => 'Est un sous-attribut de',
	SMW_SP_MAIN_DISPLAY_UNIT => 'Unité de mesure principale pour l\'affichage',
	// SMW_SP_MAIN_DISPLAY_UNIT => 'Primärmaßeinheit für Schirmanzeige', // Great! We really should keep this wonderful translation here! Still, I am not fully certain about my versions either. -- mak
	SMW_SP_DISPLAY_UNIT => 'Unité de mesure',
	SMW_SP_IMPORTED_FROM => 'Importé de',
	SMW_SP_CONVERSION_FACTOR => 'Correspond à'
);

	/**
	 * Function that returns the namespace identifiers.
	 */
	function getNamespaceArray() {
		return array(
			SMW_NS_RELATION       => "Relation",	//
			SMW_NS_RELATION_TALK  => "Discussion_relation",
			SMW_NS_ATTRIBUTE      => "Attribut",
			SMW_NS_ATTRIBUTE_TALK => "Discussion_attribut",
			SMW_NS_TYPE           => "Type_de_données",
			SMW_NS_TYPE_TALK      => "Discussion_Types_de_données"
		);
	}
	
	/**
	 * Function that returns the localized label for a datatype.
	 */
	function getDatatypeLabel($msgid) {
		return $this->smwDatatypeLabels[$msgid];
	}
	
	/**
	 * Function that returns the labels for the special relations and attributes.
	 */
	function getSpecialPropertiesArray() {
		return $this->smwSpecialProperties;
	}
	
	/**
	 * Function that returns all content messages (those that are stored 
	 * in some article, and can thus not be translated to individual users).
	 */
	function getContentMsgArray() {
		return $this->smwContentMessages;
	}
	
	/**
	 * Function that returns all user messages (those that are given only to 
	 * the current user, and can thus be given in the individual user language).
	 */
	function getUserMsgArray() {
		return $this->smwUserMessages;
	}
		
}
 
?>