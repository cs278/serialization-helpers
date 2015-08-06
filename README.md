Serialization Helpers
=====================

[![Build Status](https://travis-ci.org/cs278/serialization-helpers.svg?branch=master)](https://travis-ci.org/cs278/serialization-helpers)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/cs278/serialization-helpers/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/cs278/serialization-helpers/?branch=master)

Helpers for dealing with strings created by the `serialize()` function in PHP.

Usage
-----

`isSerialized($value)` — Test if a supplied value is a PHP serialized string,
returns `true` iff the syntax looks correct. This function may produce false
negatives because Zend PHP’s `unserialize()` implementation will work on
malformed strings.

`isSerialized($value, &$result)` — As above but also returns the unserialized
value by reference.

`unserialize($input)` — Converts the serialized input into a PHP data type,
returns the resulting data type. If an error occurs during the unserialize
operation a `SyntaxError` will be thrown.

Examples
--------

### Test if a value is serialized:

`isSerialized($value)`

```php
<?php

use Cs278\SerializationHelpers\isSerialized;

isSerialized('b:1');
// bool(false)

isSerialized('d:2.71828');
// bool(true)
```

### Unserialize with error handling:

```php
<?php

use Cs278\SerializationHelpers\unserialize;
use Cs278\SerializationHelpers\Exception\SyntaxError;

try {
    return unserialize('s:"foobar";');
} catch (SyntaxError $e) {
    $logger->warning('Input, `{input}` was not valid serialized data', array(
        'input' => $e->getInput(),
    ));

    return null;
}
```
