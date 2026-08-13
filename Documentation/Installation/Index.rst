.. include:: ../Includes.txt



.. _installation:

============
Installation
============

Import the extension in the extension manager and install it.

This Extension works out of the box with no special configuration needed
for default TYPO3 installations.

Requires the ``frontend.cache.autoTagging`` feature toggle
------------------------------------------------------------

This Extension relies on TYPO3 core's native frontend cache tagging for files
(``sys_file_<uid>``), which is guarded by the ``frontend.cache.autoTagging``
feature toggle. It is enabled by default on new TYPO3 v13+ instances, but
needs to be enabled manually on instances upgraded from earlier versions
(:guilabel:`Admin Tools > Settings > Configure Installation-Wide Options >
FEATURES`). Without it, cache invalidation for changed/moved/renamed/replaced
files that are directly referenced in content elements will not work.

For Extensions additional configuration is needed. Default configuration
is included for:

- news
- cz_simple_cal

If you have additional Extensions that are not supported yet you can:

- Look at the section :ref:`developer` to see how you can configure additional Extensions.
- Open an `issue on Github`_ and request the Extension to be included in the default configuration.

.. _issue on Github: https://github.com/Intera/typo3-extension-cacheopt/issues/

.. _installation-extension-configuration:

Extension Configuration
------------------------

:guilabel:`Admin Tools > Settings > Extension Configuration > cacheopt`

``enableStarttimeDiscovery`` (default: enabled)
   Caps the page cache lifetime for content referenced via a shortcut, a
   RECORDS TypoScript object, or a CONTENT cObject that is currently hidden
   due to a future starttime, so the cache is refreshed once that content
   becomes visible. This is implemented via Xclass overrides of TYPO3
   core's :php:`RelationHandler` and :php:`ContentObjectRenderer`. Disable
   this setting if you experience issues related to these overrides.
