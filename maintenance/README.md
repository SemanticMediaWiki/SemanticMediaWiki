## Semantic MediaWiki maintenance scripts

Scripts can be run with a command line PHP call if your MediaWiki is
properly configured to run maintenance scripts.

If you keep SMW in the standard directory `./extensions/SemanticMediaWiki`
below your MediaWiki installation, then you can run these scripts from
almost anywhere.

Otherwise, it is required to set the environment variable `MW_INSTALL_PATH`
to the root of your MediaWiki installation first. This is also required if
you use a symbolic link from `./extensions/SemanticMediaWiki` to the actual
installation directory of Semantic MediaWiki. Setting environment variables
is different for different operating systems and shells, but can normally be
done from the command line right before the php call. On Bash (Linux), e.g.
one can use the following call to execute "setupStore.php" with a different
MediaWiki location:

    export MW_INSTALL_PATH="/path/to/mediawiki" && php setupStore.php

In some setups that use a lot of shared code for many wikis, it might be
required to specify the location of "LocalSettings.php" explicitly, too:

    export MW_INSTALL_PATH="/path/to/mediawiki" && php setupStore.php --conf=/path/to/mediawiki/LocalSettings.php
