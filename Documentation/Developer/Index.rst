.. include:: ../Includes.txt


.. _developer:

================
Developer Corner
================

.. _developers-plugins-and-content:

Register plugins and content types
==================================

**Please note that this is only a workaround for Extensions that do not properly handle caching.** It is
not optimal because it clears the cache of all pages where the related plugin is used. Do not use in sites
with high performance requirements!

The cacheopt Extension needs to know which tables belong to which content
type or which plugin type. This information is stored in the
:php:`CacheOptimizerRegistry`.

To connect a table to a content type, you can use this command in the
``ext_localconf.php`` file of your Extension:

.. code-block:: php

  \Tx\Cacheopt\CacheOptimizerRegistry::getInstance()
  	->registerContentForTable('tx_myext_mytable', 'my_content_type');

After adding this configuration the cache for all pages is cleared where
content elements with the CType ``my_content_type`` are present when a
``tx_myext_mytable`` record is changed.

The configuration for plugin types is basically the same:

.. code-block:: php

  \Tx\Cacheopt\CacheOptimizerRegistry::getInstance()
  	->registerPluginForTable('tx_myext_mytable', 'my_plugin_type');

There are also methods for connecting multiple tables with content or
plugin types:

.. code-block:: php

  \Tx\Cacheopt\CacheOptimizerRegistry::getInstance()->registerContentForTables(
    array(
      'tx_myext_mytable1',
      'tx_myext_mytable2'
    ),
    'my_content_type'
  );

  \Tx\Cacheopt\CacheOptimizerRegistry::getInstance()->registerPluginForTables(
    array(
      'tx_myext_mytable1',
      'tx_myext_mytable2'
    ),
    'my_plugin_type'
  );

.. _developers-cache-api:

Register cache tags and lifetime restrictions for custom records
==================================================================

If your Extension renders its own records outside of a regular content
element context (e.g. from a plugin or a custom ContentObject), TYPO3 core
does not automatically tag the page cache with those records, nor does it
cap the page cache lifetime by their starttime/endtime. This is the same
gap that cacheopt closes for content elements referenced from another page
(see :ref:`developers-plugins-and-content` for the DataHandler-based
alternative).

:php:`\Tx\Cacheopt\CacheApi` exposes this mechanism directly, so you can
call it from your own rendering code:

.. code-block:: php

  $cacheApi = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Tx\Cacheopt\CacheApi::class);
  $cacheApi->registerRecord('tx_myext_mytable', $record, $request);

``$record`` is expected to be a plain record array (as read from the
database), and ``$request`` the current PSR-7 request (e.g. ``$this->request``
in an Extbase controller, since it also implements
:php:`Psr\Http\Message\ServerRequestInterface`). This tags the page cache
with ``tx_myext_mytable_<uid>`` (and, if present, the localized UID) and
caps the page cache lifetime according to the record's starttime/endtime,
as configured in its TCA ``enablecolumns``.

If you only need one of the two effects, use
:php:`CacheApi::registerRecordCacheTags()` or
:php:`CacheApi::registerRecordCacheLifetime()` instead; ``registerRecord()``
is a convenience method that calls both.

.. _developers-starttime-discovery:

Discovery of records hidden by a future starttime
====================================================

The mechanism described above only caps the page cache lifetime for records
that are actually rendered. A record that is currently hidden due to a
future starttime is never rendered, so its starttime cannot be taken into
account this way - the page cache would keep serving a version of the page
missing that content long after it should have appeared.

This Extension closes this gap for the two mechanisms TYPO3 core uses to load
referenced records:

- :php:`RelationHandler::getFromDB()` (used by shortcut content elements and
  RECORDS TypoScript objects with ``source.field``)
- :php:`ContentObjectRenderer::exec_getQuery()` (used by the CONTENT cObject
  and the DatabaseQueryProcessor)

via Xclass overrides that run an additional, unrestricted query for
candidates with a future starttime and register the result with
:php:`\Tx\Cacheopt\Cache\ContentLifetimeRegistry`, which caps the page cache
lifetime through a :php:`ModifyCacheLifetimeForPageEvent` listener, without
letting the hidden content itself reach the rendered output.

**Limitation:** if a custom ``source.postUserFunc`` (or similar) rewrites the
list of referenced records before TYPO3 core resolves it - e.g. applying its
own visibility filtering - a record removed at that stage never reaches this
discovery mechanism and cannot be taken into account.

This can be disabled via the extension configuration if it causes issues,
see :ref:`installation-extension-configuration`.
