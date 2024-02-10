# TYPO3 Extension cacheopt

[![.github/workflows/test.yml](https://github.com/astehlik/typo3-extension-cacheopt/actions/workflows/test.yml/badge.svg)](https://github.com/astehlik/typo3-extension-cacheopt/actions/workflows/test.yml)
[![Maintainability](https://api.codeclimate.com/v1/badges/f33e31d46e2e87b9e282/maintainability)](https://codeclimate.com/github/astehlik/typo3-extension-cacheopt/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/f33e31d46e2e87b9e282/test_coverage)](https://codeclimate.com/github/astehlik/typo3-extension-cacheopt/test_coverage)

This Extension optimizes the cache clearing behavior of TYPO3:

* When a content element is changed the cache of all pages is cleared
  where this content element is referenced by a shortcut.
* When a file or the metadata of a file is changed the cache of all
  pages is cleared where this file is used in the page properties
  or in content elements.
* When a file is changed the directory is detected and the cache of
  all pages is cleared where a folder collection references it.
* When a record of an Extension is changed the cache of all pages is
  cleared where a related plugin is used.

## More documentation

More documentation can be found in the Extension manual:

https://docs.typo3.org/typo3cms/extensions/cacheopt/
