.. include:: ../Includes.txt


.. _introduction:

============
Introduction
============


.. _what-it-does:

What does it do?
================

This Extension improves the cache clearing process of TYPO3.

It uses an enhanced tag handling to clear the cache in these use cases:

- When a content element is changed the cache of all pages is cleared
  where this content element is referenced by a shortcut.

- When a file or the metadata of a file is changed the cache of all
  pages is cleared where this file is used in the page properties
  or in content elements.

- When a file is changed the directory is detected and the cache of
  all pages is cleared where a folder collection references it.

- When a record of an Extension is changed the cache of all pages is
  cleared where a related plugin is used.

- The page cache lifetime is capped for content referenced via a
  shortcut, a RECORDS TypoScript object, or a CONTENT cObject that is
  currently hidden due to a future starttime, so the cache is
  refreshed once that content becomes visible. TYPO3 core does not
  take this into account, since it only tags/caps the lifetime for
  content that is actually rendered. This can be disabled via the
  extension configuration, see :ref:`installation`.



