# Wikibase Doctrine TermStore

[![Build Status](https://travis-ci.org/wmde/doctrine-term-store.svg?branch=master)](https://travis-ci.org/wmde/doctrine-term-store)
[![Latest Stable Version](https://poser.pugx.org/wikibase/doctrine-term-store/version.png)](https://packagist.org/packages/wikibase/doctrine-term-store)
[![Download count](https://poser.pugx.org/wikibase/doctrine-term-store/d/total.png)](https://packagist.org/packages/wikibase/doctrine-term-store)

[Doctrine DBAL](https://www.doctrine-project.org/projects/dbal.html) implementation of
[Wikibase TermStore](https://github.com/wmde/wikibase-term-store). 

## Usage

The public entry point of the package is `DoctrineTermStore`, which is used to construct all services.

```php
$termStore = new DoctrineTermStore( /* config */ );
```

Getting terms:

```php
$fingerprint = $termStore->newPropertyTermStore()->getTerms( $propertyId );
```

Schema creation:

```php
$termStore->install();
```

## Installation

To use the Wikibase TermStore library in your project, simply add a dependency on wikibase/doctrine-term-store
to your project's `composer.json` file. Here is a minimal example of a `composer.json`
file that just defines a dependency on wikibase/doctrine-term-store 1.x:

```json
{
    "require": {
        "wikibase/doctrine-term-store": "~1.0"
    }
}
```

## Development

Start by installing the project dependencies by executing

    composer update

You can run the tests by executing

    make test
    
You can run the style checks by executing

    make cs
    
To run all CI checks, execute

    make ci
    
You can also invoke PHPUnit directly to pass it arguments, as follows

    vendor/bin/phpunit --filter SomeClassNameOrFilter
