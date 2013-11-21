Install instructions for the latest SMW version are also online in a more
convenient format for reading:

         http://semantic-mediawiki.org/wiki/Help:Installation


Contents

* Disclaimer
* Requirements
* Installation
  ** Testing your Installation
  ** Customising Semantic MediaWiki
  ** Running SMW on older versions of MediaWiki
* Upgrading existing installations
  ** Upgrading SMW 1.7.*
  ** Upgrading SMW 1.6.*
  ** Upgrading SMW 1.5.*
* Troubleshooting
* SMW is installed. What should I do now?
* Contact


== Disclaimer ==

For a proper legal disclaimer, see the file "COPYING".

In general, the extension can be installed into a working wiki without making
any irreversible changes to the source code or database, so you can try out
the software without much risk (though no dedicated uninstall mechanism is
provided). Every serious wiki should be subject to regular database backups!
If you have any specific questions, please contact the authors.


== Requirements ==

* MediaWiki 1.19 or greater
* Validator 0.5 or greater (https://www.mediawiki.org/wiki/Extension:Validator)
* PHP 5.3 or greater
* MySQL >= 5.0.2 (version required by MediaWiki)

Notes:
* SMW uses the PHP mb_*() multibyte functions such as mb_strpos in the
  php_mbstring.dll extension. This is standard but not enabled by default on
  some distributions of PHP.
  See http://php.net/manual/en/ref.mbstring.php#mbstring.installation
* For installation and upgrade, SMW needs the rights to create new tables
  (CREATE) and to alter tables (ALTER TABLE). Both can be removed again after
  SMW was set up. The script SMW_setup.php can use the DB credentials from
  AdminSettings.php for this purpose, avoiding the need of extra rights for
  the wiki DB user.
* When using SMWSQLStore3 (default data store for SMW), SMW creates and alters
  temporary tables for certain semantic queries. To do this, your wikidb user
  must have privileges for CREATE TEMPORARY TABLES. The according features can
  be disabled by adding the following to Localsettings.php:

  $smwgQSubcategoryDepth=0;
  $smwgQPropertyDepth=0;
  $smwgQFeatures        = SMW_ANY_QUERY & ~SMW_DISJUNCTION_QUERY;
  $smwgQConceptFeatures = SMW_ANY_QUERY & ~SMW_DISJUNCTION_QUERY &
                          ~SMW_CONCEPT_QUERY;

* When using SMWSparqlStore (RDF store connector), SMW uses the CURL functions
  of PHP. These functions may have to be enabled/installed to be available.


== Installation ==

If you upgrade an existing installation of Semantic MediaWiki, also read the
remarks in the section "Notes on Upgrading" below!

(1) Extract the archive or check out the current files from SVN to obtain the
    directory "SemanticMediaWiki" that contains all relevant files. Copy this
    directory to "[wikipath]/extensions/" (or extract/download it there).
    We abbreviate "[wikipath]/extensions/SemanticMediaWiki" as "[SMW_path]".
(2) Insert the following two lines into "[wikipath]/LocalSettings.php":

    include_once("$IP/extensions/SemanticMediaWiki/SemanticMediaWiki.php");
    enableSemantics('example.org');

    where example.org should be replaced by your server's name (or IP address).
    The latter is needed only once, using the "preferred" name of your server.
    It is no problem to access a site by more than one servername in any case.
    If you have custom namespaces (such as "Portal"), read the note below.
(3) In your wiki, log in as a user with admin status and go to the page
    "Special:SMWAdmin" to do the final setup steps. Two steps are needed: at
    first, trigger the database setup ("Database installation and upgrade").
    Afterwards, activate the automatic data update ("Data repair and upgrade").
    Note that the first step requires permissions to alter/create database
    tables, as explained in the above note. The second step takes some time;
    go to Special:SMWAdmin to follow its progress. SMW can be used before this
    completes, but will not have access to all data yet (e.g. page categories).

    Both of those actions can also be accomplished with the command-line PHP
    scripts SMW_setup.php and SMW_refreshData.php. Read the documentation in
    [SMW_path]/maintenance/README for details on how to run such scripts.

'''Remark:'''  Semantic MediaWiki uses ten additional namespace indexes (see
http://www.mediawiki.org/wiki/Manual:Using_custom_namespaces), in the range from
100 to 109. 100 and 101 are not used (they were used in early beta versions),
104 and 105 are not used by default (they were used for the Type namespace in
SMW up to 1.5.*). 106 and 107 are reserved for the SemanticForms extension. If
you have your own custom namespaces, you have to set the parameter
$smwgNamespaceIndex before including SemanticMediaWiki.php. See the
documentation $within SMW_Settings.php for details. If you add more namespaces
later on, then you have to assign them to higher numbers than those used by
Semantic MediaWiki.


=== Testing your Installation ===

If you are uncertain that everything went well, you can do some testing steps
to check if SMW is set up properly.

Go to the Special:Version page. You should see Semantic MediaWiki (version nn)
listed as a Parser Hook there.

Create a regular wiki page named "TestSMW", and in it enter the wiki text
  Property test:  [[testproperty::Dummypage]]

When previewing the page before saving, you should see a Factbox at the bottom
of the article that shows your input. After saving the page, click on the link
"Browse properties" in the page's toolbox. This view should show Testproperty
with value Dummypage.

If you don't get these results, check the steps in the Installation section,
consult the FAQ section, then contact the user support list (see the Contact
section).

=== Customising Semantic MediaWiki ===

Semantic MediaWiki can be customised by a number of settings. The available
options are detailed in http://semantic-mediawiki.org/wiki/Help:Configuration

== Upgrading existing installations ==

(Please read all of this before upgrading)

=== Upgrading SMW 1.7.x ===

SMW 1.8 introduces a new default database layout (SMWSQLStore3). You can
continue to use the old layout (SMWSQLStore2) for transition purposes, but it
is strongly recommended to migrate to the new layout for future compatibility.

To use the old layout at first, add $smwgDefaultStore = 'SMWSQLStore2'; to
your LocalSettings.php. If you are using $smwgDefaultStore = 'SMWSparqlStore';
already, then you should keep this line and add the following:
SMWSparqlStore::$baseStoreClass = 'SMWSQLStore2'; (after enableSemantics).
After setting these, run SMW_setup.php or Special:SMWAdmin upgrade as usual.
The wiki should now work as normal, but using the old storage structures.

To migrate to the new store, you can do a normal "full refresh" for the new
store. Run the following two commands (both will run a while).

php SMW_refreshData.php -v -b SMWSQLStore3 -fp
php SMW_refreshData.php -v -b SMWSQLStore3

The running wiki will not be affected by this, but the operation could affect
server speed. See
http://semantic-mediawiki.org/wiki/Help:Repairing_SMW%27s_data for details.

After successful migration, remove the lines with 'SMWSQLStore2' from your
LocalSettings.php to use the new store. You can always return to the old store
in case of problems. If the old store is no longer needed, it can be deleted
(and its memory freed) by running

php SMW_setup.php --delete --backend SMWSQLStore2

=== Upgrading SMW 1.6.x ===

Installations of SMW 1.6.* and can be upgraded like SMW 1.7.*.

=== Upgrading SMW 1.5.x ===

Installations of SMW 1.5.* and can mostly be upgraded like SMW 1.7.*, with some additions.
SMW 1.6.0 introduced a new software dependency (which also applies to all later versions):
the Validator extension that helps Semantic MediaWiki to validate user-provided parameters.
It must be installed for SMW to work. Make sure that you include Validator prior to the
inclusion of SMW in your LocalSettings.php. Do note that Validator comes bundled with SMW
releases as of version 1.6.0. If you are obtaining the code via git, you will need to get
a checkout of Validator yourself.

Do not forget to also install the extension Validator first and include it in LocalSettings.php
prior to SMW with the following line of code. After that you may proceed to upgrade SMW.

require_once( "$IP/extensions/Validator/Validator.php" );

If not done already, it is suggested to change the inclusion of SMW in LocalSettings.php
to the following as described in the installation instructions above:

include_once("$IP/extensions/SemanticMediaWiki/SemanticMediaWiki.php");

Including SMW_Settings.php as in earlier versions will no longer work.

== Troubleshooting ==

Some technical problems are well known and have easy fixes. Please view the
online manual: http://semantic-mediawiki.org/wiki/Help:Troubleshooting

See http://semantic-mediawiki.org/wiki/Help:Reporting_bugs for reporting and
looking up bugs. You can also send an email to
semediawiki-user@lists.sourceforge.net (subscribe first at
http://sourceforge.net/mailarchive/forum.php?forum_name=semediawiki-user)


== SMW is installed. What should I do now? ==

Semantic MediaWiki is there to help you to structure your data, so that you
can browse and search it easier. Typically, you should first add semantic
markup to articles that cover a topic that is typical for your wiki. A single
article, semantic or not, will not improve your search capabilities.

Start with a kind of article that occurs often in your wiki, possibly with
some type of articles that is already collected in some category, such as
cities, persons, or software projects. For these articles, introduce a few
properties, and annotate many of the articles with the property. As with
categories, less is often more in semantic annotation: do not use overly
specific properties. A property that is not applicable to at least ten
articles is hardly useful.

Templates can greatly simplify initial annotation. Create a flashy template
for your users to play with, and hide the semantic annotations in the code
of the template. Use the ParserFunctions extension to implement optional
parameters, so that your users can leave fields in the template unspecified
without creating faulty annotations.

Develop suitable inline queries ({{#ask: ... }}) along with any new
annotation. If you don't know how to use some annotation for searching, or
if you are not interested in searching for the annotated information anyway,
then you should probably not take the effort in the first place. Annotate
in a goal-directed way! Not all information can be extracted from the
annotations in your wiki. E.g. one can currently not search for articles that
are *not* in a given category. Think about what you want to ask for before
editing half of your wiki with new semantics ...

If in doubt, choose simple annotations and learn to combine them into more
complex information. For example, you do not need to have a category for
"European cities" -- just combine "located in::Europe" and "Category:City."
If European cities are important for your wiki, you can create a Concept
page for storing that particular query. In any case, if some annotation is
not sufficient, you can still add more information. Cleaning too specific
and possibly contradictory annotations can be more problematic.

Regularly review users' use of categories, properties, and types using
the Special pages for each.


== Contact ==

See "Contact" in the file README, or view the current online information
http://semantic-mediawiki.org/wiki/Contact

If you have remarks or questions, please send them to
 semediawiki-user@lists.sourceforge.net
You can join this mailing list at
 http://sourceforge.net/mail/?group_id=147937

Please report bugs to MediaZilla, http://bugzilla.wikimedia.org
