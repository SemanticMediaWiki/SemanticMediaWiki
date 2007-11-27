<?php
/**
 * @author Pierre Matringe
 */

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

class SMW_LanguageFr extends SMW_Language {

protected $m_ContentMessages = array(
	'smw_edithelp' => 'Aide à la rédaction de relations et d\'attributs',
	'smw_viewasrdf' => 'Voir comme RDF',
	'smw_finallistconjunct' => ' et',					//utilisé dans "A, B, et C"
	'smw_factbox_head' => 'Faits relatifs à $1 &mdash; Recherche de pages similaires avec <span class="smwsearchicon">+</span>.',
	'smw_isspecprop' => 'Cette propriété est une propriété spéciale sur ce wiki.',
	'smw_isknowntype' => 'Ce type fait partie des types de données standards de ce wiki.',
	'smw_isaliastype' => 'Ce type est un alias du type de données “$1”.',
	'smw_isnotype' => 'Le type “$1” n\'est pas un type de données standard sur ce wiki, et n\'a pas non plus été n\'a pas non plus été défini par un utilisateur.',
	/*URIs that should not be used in objects in cases where users can provide URIs */
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => 'Désolé. Les URIs du domaine $1 ne sont pas disponible à cet emplacement',
	/*Messages and strings for inline queries*/
	'smw_iq_disabled' => "Désolé. Les recherches dans les articles de ce wiki ne sont pas autorisées.",
	'smw_iq_moreresults' => '&hellip; autres résultats',
	'smw_iq_nojs' => 'Utilisez un navigateur avec JavaScript pour voir cet élément.',
	'smw_iq_altresults' => 'Browse the result list directly.', // available link when JS is disabled // TODO: translate
	/*Messages and strings for ontology resued (import) */
	'smw_unknown_importns' => 'Aucune fonction d\'import n\'est disponible pour l\'espace de nommage "$1".',
	'smw_nonright_importtype' => 'L\'élément "$1" ne peut être employé que pour des articles de l\'espace de nommage "$2".',
	'smw_wrong_importtype' => 'L\'élément "$1" ne peut être employé pour des articles de l\'espace de nommage domaine "$2".',
	'smw_no_importelement' => 'L\'élément "$1" n\'est pas disponible pour l\'importation.',
	/*Messages and strings for basic datatype processing*/
	'smw_decseparator' => ',',
	'smw_kiloseparator' => '.',
	'smw_notitle' => '“$1” ne peut être utilisé comme nom de page sur ce wiki.',
	'smw_unknowntype' => 'Le type de données "$1" non supporté a été retourné à l\'attribut.',
	'smw_manytypes' => 'Plusieurs types de données ont été assignés à l\'attribut.',
	'smw_emptystring' => 'Les chaînes vides ne sont pas acceptées.',
	'smw_maxstring' => 'La chaîne de représentation $1 est trop grande pour ce site.',
	'smw_notinenum' => '\"$1\" ne fait pas partie des valeurs possibles ($2) pour cet attribut.',
	'smw_noboolean' => '\"$1\" n\'est pas reconnu comme une valeur boléenne (vrai/faux).',
	'smw_true_words' => 'v,oui',	// comma-separated synonyms for boolean TRUE besides 'true' and '1'
	'smw_false_words' => 'f,non',	// comma-separated synonyms for boolean FALSE besides 'false' and '0'
	'smw_nofloat' => '"$1" n\'est pas un nombre.',
	'smw_infinite' => 'Le nombre $1 est trop long.',
	'smw_infinite_unit' => 'La conversion dans l\'unité $1 est impossible : le nombre est trop long.',
	// Currently unused, floats silently store units.  'smw_unexpectedunit' => 'Cet attribut ne supporte aucune conversion d\'unité',
	'smw_unsupportedprefix' => 'Des préfixes ("$1") ne sont pas supportés actuellement',
	'smw_unsupportedunit' => 'La conversion de l\'unité "$1" n\'est pas supportée',
	// Messages for geo coordinates parsing
	'smw_lonely_unit' => 'Aucun nombre trouvé avant le symbole “$1”.', // $1 is something like °
	'smw_bad_latlong' => 'Latitude et longitude ne doivent être indiqués qu\'une seule fois, et avec des coordonnées valides.',
	'smw_label_latitude' => 'Latitude :',
	'smw_label_longitude' => 'Longitude :',
	'smw_abb_north' => 'N',
	'smw_abb_east' => 'E',
	'smw_abb_south' => 'S',
	'smw_abb_west' => 'O',
	/* some links for online maps; can be translated to different language versions of services, but need not*/
	'smw_service_online_maps' => " Cartes géographiques|http://tools.wikimedia.de/~magnus/geo/geohack.php?language=fr&params=\$9_\$7_\$10_\$8\n Google maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",
	/*Messages for datetime parsing */
	'smw_nodatetime' => 'La date "$1" n\'a pas été comprise. Le support des données calendaires est encore expérimental.',
	// Errors and notices related to queries //
	'smw_toomanyclosing' => 'Il semble y avoir trop d\'occurences de “$1” dans la requête.',
	'smw_noclosingbrackets' => 'Certains “[[” dans votre requête n\'ont pas été clos par des “]]” correspondants.',
	'smw_misplacedsymbol' => 'Le symbole “$1” a été utilisé à un endroit où il n\'est pas utile.',
	'smw_unexpectedpart' => 'La partie “$1” de la requête n\'a pas été comprise. Les Résults peuvent être inattendus.',
	'smw_emptysubquery' => 'Certaines sous-requêtes ont une condition non-valide.',
	'smw_misplacedsubquery' => 'Certaines sous-requêtes ont été utilisées à un endroit où aucune sous-requête n\'est permise.',
	'smw_valuesubquery' => 'Sous-requête non supportée pour les valeurs de la propriété “$1”.',
	'smw_overprintoutlimit' => 'La requête contient trop d\'instructions de formatage.',
	'smw_badprintout' => 'Certaines instructions de formatage dans la requête n\'ont pas été comprises.',
	'smw_badtitle' => 'Désolé, mais “$1” n\'est pas un titre de page valable.',
	'smw_badqueryatom' => 'Les parties “[[…]]” de la requête n\'ont pas été comprises.',
	'smw_propvalueproblem' => 'La valeur de la propriété “$1” n\'a pas été comprises.',
	'smw_nodisjunctions' => 'Les disjonctions dans les requêtes ne sont pas supportées sur ce wiki et des parties de la requête ont été ignorées($1).',
	'smw_querytoolarge' => 'Les conditions suivantes de la requête n\'ont pu être évaluées en raison des restrictions de ce wiki à la taille ou à la profondeur des requêtes : $1.'
);

protected $m_UserMessages = array(
	'smw_devel_warning' => 'Cette fonction est encore en développement et n\'est peut-être pas encore opérationnelle. Il est peut-être judicieux de faire une sauvegarde du contenu du wiki avant toute utilisation de cette fonction.',
	// Messages for article pages of types, relations, and attributes
	'smw_type_header' => 'Attributs de type “$1”',
	'smw_typearticlecount' => 'Afficher les attributs de $1 en utilisant ce type.',
	'smw_attribute_header' => 'Pages utilisant l\'attribut “$1”',
	'smw_attributearticlecount' => '<p>Afficher $1 pages utilisant cet attribut.</p>',
	/*Messages for Export RDF Special*/
	'exportrdf' => 'Exporter l\'article en RDF', //name of this special
	'smw_exportrdf_docu' => '<p>Sur cette page, des parties du contenu d\'un article peuvent être exportées dans le format RDF. Veuillez entrer le nom des pages souhaitées dans la boîte de texte ci-dessous, <i>un nom par ligne </i>.</p>',
	'smw_exportrdf_recursive' => 'Exporter également toutes les pages pertinentes de manière récursive. Cette possibilité peut aboutir à un très grand nombre de résultats !',
	'smw_exportrdf_backlinks' => 'Exporter également toutes les pages qui renvoient à des pages exportées. Produit un RDF dans lequel la navigation est facilitée.',
	'smw_exportrdf_lastdate' => 'Ne pas exporter les pages non modifiées depuis le moment indiqué.',
	// Messages for Properties Special
	'properties' => 'Propriétés',
	'smw_properties_docu' => 'Sur ce wiki, sont utilisées les propriétés suivantes.',
	'smw_property_template' => '$1 du type $2 ($3)', // <propname> of type <type> (<count>)
	'smw_propertylackspage' => 'Toute propriété devrait être décrite par une page !',
	'smw_propertylackstype' => 'Aucun type n\'a été spécifié pour cette propriété (type actuellement supposé : §1.',
	'smw_propertyhardlyused' => 'Cette propriété est très utilisée sur ce wiki !',
	// Messages for Unused Properties Special
	'unusedproperties' => 'Propriétés inutilisées',
	'smw_unusedproperties_docu' => 'Les propriétés suivantes existent, bien qu\'aucune page ne les utilise.',
	'smw_unusedproperty_template' => '$1 de type $2', // <propname> of type <type>
	// Messages for Wanted Properties Special
	'wantedproperties' => 'Propriétés demandées',
	'smw_wantedproperties_docu' => 'Les propriétés suivantes sont utilisées sur ce wiki mes n\'ont pas encore de page pour les décrire.',
	'smw_wantedproperty_template' => '$1 ($2 utilisations)', // <propname> (<count> uses)
	/* Messages for the refresh button */
	'tooltip-purge' => 'Réactualiser toutes les recherches et tous les brouillons de cette page.',
	'purge' => 'Réactualiser',
	/*Messages for Import Ontology Special*/
	'ontologyimport' => 'Importer une ontologie',
	'smw_oi_docu' => 'Cette page spéciale permet d\'importer des informations d\'une ontologie externe. Cette ontologie doit être dans un format RDF simplifié. Des informations supplémentaires sont disponibles dans la <a href="http://wiki.ontoworld.org/index.php/Help:Ontology_import">Documentation relative à l\'import d\'ontologie</a> en langues anglaise.',
	'smw_oi_action' => 'Importer',
	'smw_oi_return' => 'Revenir à <a href="$1">Importer l\'ontologie</a>.',	//Différence avec la version anglaise
	'smw_oi_noontology' => 'Aucune ontologie fournie, ou impossible de charger l\'ontologie.',
	'smw_oi_select' => 'Veuillez sélectionner le texte à importer, puis cliquez sur le bouton « importer ».',
	'smw_oi_textforall' => 'Texte à ajouter en en-tête à toutes les importations (peut rester vice :',
	'smw_oi_selectall' => 'Sélectionner ou désélectionner tous les textes',
	'smw_oi_statementsabout' => 'Textes sur',
	'smw_oi_mapto' => 'Carte de l\'entité sur',
	'smw_oi_comment' => 'Ajouter le texte suivant :',
	'smw_oi_thisissubcategoryof' => 'Sous-catégorie de',
	'smw_oi_thishascategory' => 'Fait partie de',
	'smw_oi_importedfromontology' => 'Importer de l\'ontologie',
	/*Messages for (data)Types Special*/
	'types' => 'Types de données',
	'smw_types_docu' => 'Les types de données suivants peuvent être assignées aux attributs. Chaque type de données a son propre article, dans lequel peuvent figurer des informations plus précises.',
	'smw_typeunits' => 'Unités de measure de type “$1” : $2',
	/*Messages for SemanticStatistics Special*/
	'semanticstatistics' => 'Statistiques sémantiques',
	'smw_semstats_text' => 'Ce wiki contient <b>$1</b> valeurs de propriété pour un total de <b>$2</b> <a href="$3">propriétés</a> différentes. <b>$4</b> propriétés ont leur propre page, et le type de données voulu est spécifié pour <b>$5</b> de celles-ci. Certaines des propriétés existantes peuvent faire partient des <a href="$6">propriétés inutilisées</a>. Les propriétés qui n\'ont pas encore de page se trouvent sur la <a href="$7">liste des propriétés demandées</a>.',
	/*Messages for Flawed Attributes Special --disabled--*/
	'flawedattributes' => 'Attributs défectueux',
	'smw_fattributes' => 'Les pages ci-dessous ont un attribut qui n\'est pas défini correctement. Le nombre d\'attributs incorrects est donné entre les parenthèses.',
	// Name of the URI Resolver Special (no content)
	'uriresolver' => 'Résolveur d\'URI',
	'smw_uri_doc' => '<p>Le résolveur d\'URI implémente la <a href="http://www.w3.org/2001/tag/issues.html#httpRange-14">Conclusion du TAG du W3C à propos du httpRange-14</a>. Il peut garde à ce que les humaines ne deviennent pas des sites web.',
	/*Messages for ask Special*/
	/*Messages for ask Special*/
	'ask' => 'Recherche sémantique',
	'smw_ask_doculink' => 'Recherche sémantique',
	'smw_ask_sortby' => 'Trier par colonnes',
	'smw_ask_ascorder' => 'Croissant',
	'smw_ask_descorder' => 'Décroissant',
	'smw_ask_submit' => 'Trouver des résultats',
	'smw_ask_editquery' => '[Edit query]', // TODO: translate
	'smw_ask_hidequery' => 'Hide query', // TODO: translate
	'smw_ask_help' => 'Querying help', // TODO: translate
	'smw_ask_queryhead' => 'Query', // TODO: translate
	'smw_ask_printhead' => 'Additional printouts (optional)', // TODO: translate
	// Messages for the search by property special
	'searchbyproperty' => 'Rechercher par attribut',
	'smw_sbv_docu' => '<p>Rechercher toutes les pages qui ont un attribut donné avec un certaine valeur.</p>',
	'smw_sbv_noproperty' => '<p>Veuillez entrer un attribut.</p>',
	'smw_sbv_novalue' => '<p>Veuillez entrer une valeur ou consulter toutes les valeurs des attributs pour $1.</p>',
	'smw_sbv_displayresult' => 'Liste de toutes les pages qui ont un attribut $1 avec la valeur $2.',
	'smw_sbv_property' => 'Attribut',
	'smw_sbv_value' => 'Valeur',
	'smw_sbv_submit' => 'Trouver des résultats',
	// Messages for the browsing system
	'browse' => 'Parcourir le wiki',
	'smw_browse_article' => 'Entrez le nom de la page à partir de laquelle commencer la navigation.',
	'smw_browse_go' => 'Démarrer',
	'smw_browse_more' => '…',
	// Messages for the page property special
	'pageproperty' => 'Rechercher dans les propriétés de la page',
	'smw_pp_docu' => 'Rechercher toutes les valeurs d\'une propriété dans une page donnée. Veuillez entrer la page et une propriété.',
	'smw_pp_from' => 'De la page',
	'smw_pp_type' => 'Propriété',
	'smw_pp_submit' => 'Afficher les résultats',
	// Generic messages for result navigation in all kinds of search pages
	'smw_result_prev' => 'Précédent',
	'smw_result_next' => 'Suivant',
	'smw_result_results' => 'Résultats',
	'smw_result_noresults' => 'Désolé, aucun résultat.',
);

protected $m_DatatypeLabels = array(
	'_wpg' => 'Page', // name of page datatype
	'_str' => 'Chaîne de caractères',  // name of the string type
	'_txt' => 'Texte',  // name of the text type (very long strings)
	//'_boo' => 'Booléen',  // name of the boolean type
	'_num' => 'Nombre', // name for the datatype of numbers
	'_geo' => 'Coordonnées géographiques', // name of the geocoord type
	'_tem' => 'Température',  // name of the temperature type
	'_dat' => 'Date',  // name of the datetime (calendar) type
	'_ema' => 'Adresse électronique',  // name of the email type
	'_uri' => 'URL',  // name of the URI type
	'_anu' => 'Annotation-URI'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'URI'                   => '_uri',
	'Nombre entier'         => '_num',
	'Nombre décimal'        => '_num',
	'Énumeration'           => '_str',
	// support English aliases:
	'Page'                  => '_wpg',
	'String'                => '_str',
	'Text'                  => '_txt',
	'Number'                => '_num',
	'Geographic coordinate' => '_geo',
	'Temperature'           => '_tem',
	'Date'                  => '_dat',
	'Email'                 => '_ema',
	'Annotation URI'        => '_anu'
);

protected $m_SpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'A le type',
	SMW_SP_HAS_URI   => 'URI équivalente',
	SMW_SP_SUBPROPERTY_OF => 'Sous^-propriété de',
	SMW_SP_DISPLAY_UNITS => 'Unités de mesure',
	SMW_SP_IMPORTED_FROM => 'Importé de',
	SMW_SP_CONVERSION_FACTOR => 'Correspond à',
	SMW_SP_SERVICE_LINK => 'Fournit le service',
	SMW_SP_POSSIBLE_VALUE => 'Valeur possible'
);

protected $m_SpecialPropertyAliases = array(
	'Unité de mesure'   => SMW_SP_DISPLAY_UNITS,
	// support English aliases for special properties
	'Has type'          => SMW_SP_HAS_TYPE,
	'Equivalent URI'    => SMW_SP_HAS_URI,
	'Subproperty of'    => SMW_SP_SUBPROPERTY_OF,
	'Display units'     => SMW_SP_DISPLAY_UNITS,
	'Imported from'     => SMW_SP_IMPORTED_FROM,
	'Corresponds to'    => SMW_SP_CONVERSION_FACTOR,
	'Provides service'  => SMW_SP_SERVICE_LINK,
	'Allows value'      => SMW_SP_POSSIBLE_VALUE
);

protected $m_Namespaces = array(
	SMW_NS_RELATION       => "Relation",
	SMW_NS_RELATION_TALK  => "Discussion_relation",
	SMW_NS_PROPERTY       => "Attribut",
	SMW_NS_PROPERTY_TALK  => "Discussion_attribut",
	SMW_NS_TYPE           => "Type",
	SMW_NS_TYPE_TALK      => "Discussion_type"
);

protected $m_NamespaceAliases = array(
	// support English aliases for namespaces
	//'Relation'      => SMW_NS_RELATION,
	'Relation_talk' => SMW_NS_RELATION_TALK,
	'Property'      => SMW_NS_PROPERTY,
	'Property_talk' => SMW_NS_PROPERTY_TALK,
	'Type'          => SMW_NS_TYPE,
	'Type_talk'     => SMW_NS_TYPE_TALK
);

}


