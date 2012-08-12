Released on July 30, 2011.

* Full support for synchronizing RDF stores with SMW, and for answering #ask
  queries based on this data. The communication happens via SPARQL (1.1), and
  all SPARQL-capable stores should be supported. The following settings are
  needed in LocalSettings.php:
    $smwgDefaultStore = 'SMWSparqlStore';
    $smwgSparqlDatabase = 'SMWSparqlDatabase';
    // The following should be set to the URLs to reach the store:
    $smwgSparqlQueryEndpoint = 'http://localhost:8080/sparql/';
    $smwgSparqlUpdateEndpoint = 'http://localhost:8080/update/';
    $smwgSparqlDataEndpoint = 'http://localhost:8080/data/'; // can be empty
  The specific support that SMW used to have for the RAP RDF store has been
  discontinued.
* The Type namespace has been abolished. Builtin types now are displayed by the
  special page Special:Types, and there are no "custom types" any longer.
  By default, the Type namespace is gone and existing pages in this namespace
  can no longer be accessed. This can be changed by setting
  $smwgHistoricTypeNamespace = true in LocalSettings.php before including SMW.
* Changed the way in which units of measurement work. Type:Number now does not
  accept any units, and a new type "Quantity" is used for numbers with units.
  Units must be declared on the property page (not on the Type page as before),
  and only units that have a declared conversion factor are accepted.
* The declaration of Type:Record properties has changed. Instead of a list of
  datatypes, the declaration now requires a list of properties that are to be
  used for the fields of the record. The declaration is still done with the
  property "has fields" as before. Properties must not be used more than once
  in has_fields, or the order of values will be random.
* Introduced pre-defined builtin properties for every datatype. For example,
  the property String is always of type String and available on all (English)
  wikis. This helps to keep some of the old Tpe:Record declarations valid.
* Changed the way parameters in query printers are specified and handled
  using the Validator extension. This includes improvements to the parameter
  options in the Special:Ask GUI and better error reporting for ask queries.
* Added UNIX-style DSV (Delimiter-separated values) result format.
* Reworked internal data model, cleaning up and re-implementing SMWDataValue and
  all of its subclasses, and introducing new data item classes to handle data.
  The class SMWCompatibilityHelpers provides temporary help for extensions that
  still depend on the old format and APIs.
* Fixed PostgreSQL issues with the installation and upgrade code.
* Added API module (smwinfo) via which statistics about the semantic data can
  be obtained.
* Added second parameter to #info that allows chosing either the info or warning
  icon.
* Added #smwdoc parser hook that displays a table with parameter documentation for
  a single specified result format.
* Fixed escaping issues in the JSON result format. A compatibility breaking change
  is that per property an array of values will be returned, even if there is only
  one.
* Added SMWStore::updateDataBefore and SMWStore::updateDataAfter hooks.