# Installation guide (brief)

This is a brief installation and configuration guide for [Semantic MediaWiki](../README.md) (SMW)
that only contains the core steps. More verbose installation instructions with additional explanation
and upgrading instructions can be found [here](https://www.semantic-mediawiki.org/wiki/Help:Installation).

A list of supported PHP versions, MediaWiki versions and databases per SMW release can be found
in the [compatibility matrix](COMPATIBILITY.md).


## Download and installation

### Installation with Composer

The strongly recommended way to install Semantic MediaWiki is with [Composer](http://getcomposer.org) using
[MediaWiki's built-in support for Composer](https://www.mediawiki.org/wiki/Composer).

##### Step 1

Change to the base directory of your MediaWiki installation. This is where the "LocalSettings.php"
file is located. If you have not yet installed Composer do it now by running the following command
in your shell:

    wget https://getcomposer.org/composer.phar

##### Step 2

If you are using MediaWiki 1.25 or later continue to step 3. If not run the following command in
your shell:

    php composer.phar require mediawiki/semantic-media-wiki "~2.5" --no-dev

Note that if you have Git installed on your system add the `--prefer-source` flag to the above command.

Now you can continue to step 5.

##### Step 3
    
If you do not have a "composer.local.json" file yet, create one and add the following content to it:

```
{
	"require": {
                  "mediawiki/semantic-media-wiki": "~2.5"
        }
}
```

If you already have a "composer.local.json" file add the following line to the end of the "require"
section in your file:

    "mediawiki/semantic-media-wiki": "~2.5"

Remember to add a comma to the end of the preceding line in this section.

##### Step 4

Run the following command in your shell:

    php composer.phar update --no-dev

Note if you have Git installed on your system add the `--prefer-source` flag to the above command. Also
note that it may be necessary to run this command twice. If unsure do it twice right away.

##### Step 5

Run the MediaWiki [update script](https://www.mediawiki.org/wiki/Manual:Update.php). The location of
this script is `maintenance/update.php`. It can be run as follows in your shell:

    php maintenance/update.php

##### Step 6

Add the following line to the end of your "LocalSettings.php" file:

    enableSemantics( 'example.org' );

Note that "example.org" should be replaced by your wiki's domain.

##### Step 7

If you are installing SMW on a freshly installed wiki continue to the next step. If the wiki already has content
pages run the Semantic MediaWiki [data rebuild script](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_"rebuildData.php"). The location of this script
is `extensions/SemanticMediaWiki/maintenance/rebuildData.php`. It can be run as follows in your shell:

    php extensions/SemanticMediaWiki/maintenance/rebuildData.php -v

##### Verify installation success

As final step, you can verify SMW got installed by looking at the "Special:Version" page on your wiki and check that
the Semantic MediaWiki section is listed.

### Installation without shell access

As an alternative to installing via Composer, you can obtain the SMW code by getting one of the release tarballs.
These tarballs include all dependencies of SMW.

This option exists mainly for those that have no command line access. A drawback of this approach is that it makes
your setup incompatible with extensions that share dependencies with SMW. You are thus highly encouraged to use
the Composer approach if you have command line access.

#### Step 1

Download an SMW tarball and extract it into your extensions directory.

* [Download as tarball](https://github.com/SemanticMediaWiki/SemanticMediaWiki/releases) from the latest SMW release
with dependencies

#### Step 2

Add the following lines to the end of your "LocalSettings.php" file:

    require_once "$IP/extensions/SemanticMediaWiki/SemanticMediaWiki.php";
    enableSemantics( 'example.org' );

Note that "example.org" should be replaced by your wiki's domain.

#### Step 3

Log in as a user with administrator permission to your wiki and go to the page "Special:SemanticMediaWiki":

Click on the "Initialise or upgrade tables" button in the "Database installation and upgrade" section to setup the
database.

#### Step 4

If you are installing SMW on a freshly installed wiki continue to the next step. If the wiki already has content
pages also do the following on page "Special:SemanticMediaWiki":

Click on the "Start updating data" button in the "Data rebuild" subsection of the "Data repair and upgrade" section
to activate the [automatic data update](https://www.semantic-mediawiki.org/wiki/Help:Repairing_SMW's_data).

#### Verify installation success

As final step, you can now verify SMW got installed by looking at the "Special:Version" page on your wiki and check that
the Semantic MediaWiki section is listed.

### Installation of development versions and release candidates

If you would like to install a development version or release candidate then replace the lines as stated in step 3 of the
"Installation with Composer" section with the following line

* master: `"mediawiki/semantic-media-wiki": "@dev"`
* legacy branch: `"mediawiki/semantic-media-wiki": "2.5.x@dev"`
* release candidate: `"mediawiki/semantic-media-wiki": "~2.5@rc"`

## More instructions

* [Verbose installation instructions](https://www.semantic-mediawiki.org/wiki/Help:Installation)
* [Upgrading instructions](https://www.semantic-mediawiki.org/wiki/Help:Installation#Upgrading)
* [Configuration instructions](https://www.semantic-mediawiki.org/wiki/Help:Configuration)
* [Administrator manual](https://www.semantic-mediawiki.org/wiki/Help:Administrator_manual)
