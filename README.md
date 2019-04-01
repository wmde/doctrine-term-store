# Wikibase TermStore

[![Build Status](https://travis-ci.org/wmde/wikibase-term-store.svg?branch=master)](https://travis-ci.org/wmde/wikibase-term-store)

Small library for looking up terms by item or property id or findings ids by term.

## Usage

TODO

## Installation

To use the UPDATE_NAME library in your project, simply add a dependency on wmde/wikibase-term-store
to your project's `composer.json` file. Here is a minimal example of a `composer.json`
file that just defines a dependency on wmde/wikibase-term-store 1.x:

```json
{
    "require": {
        "wmde/wikibase-term-store": "~1.0"
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
