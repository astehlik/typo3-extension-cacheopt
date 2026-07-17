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

- The page cache lifetime is capped according to the starttime/endtime of
  every rendered content element, even if it is referenced from another
  page (e.g. via a "shortcut" or "records" content element). TYPO3 core
  only takes starttime/endtime into account for records residing directly
  on the cached page.



