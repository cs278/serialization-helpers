<?php

/*
 * This file is part of the PHP Serialization Helpers package.
 *
 * © Chris Smith <chris@cs278.org>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Cs278\SerializationHelpers;

use Cs278\SerializationHelpers\Exception\SyntaxError;
use Cs278\SerializationHelpers\Exception\SyntaxError\UnknownException;
use Cs278\SerializationHelpers\Exception\SyntaxErrorFactory;

/**
 * Wrapper around unserialize() that converts syntax errors to exceptions.
 *
 * @param string $input Serialized string
 *
 * @return mixed
 *
 * @throws SyntaxError If there was an error unserializing the string
 */
function unserialize($input)
{
    static $exceptionFactory;
    $currentHandler = null;

    if (null === $exceptionFactory) {
        $exceptionFactory = new SyntaxErrorFactory();
    }

    if (defined('HHVM_VERSION')) {
        // @codeCoverageIgnoreStart
        $errorHandler = function ($code, $message, $file, $line) use ($exceptionFactory, &$currentHandler, $input) {
            if ($code === E_NOTICE) {
                throw $exceptionFactory->create($input, $message);
            }

            if ($currentHandler) {
                return call_user_func_array($currentHandler, func_get_args());
            }

            return false;
        };
    } else {
        // @codeCoverageIgnoreEnd
        static $errorHandler;

        if (!$errorHandler) {
            $errorHandler = function ($code, $message, $file, $line) use ($exceptionFactory, &$currentHandler) {
                if ($code === E_NOTICE) {
                    $e = new \ErrorException($message, $code, 0, $file, $line);

                    throw $exceptionFactory->createFromErrorException($e);
                }

                if ($currentHandler) {
                    return call_user_func_array($currentHandler, func_get_args());
                }

                // @codeCoverageIgnoreStart
                return false;
                // @codeCoverageIgnoreEnd
            };
        }
    }

    $currentHandler = set_error_handler($errorHandler);

    try {
        $result = \unserialize($input);

        if ($result === false && $input !== 'b:0;') {
            throw new UnknownException($input, 'Unknown error was encountered');
        }
    } catch (\Exception $e) {
        throw $e;
    } finally {
        // Ensure the error handler is restored.
        restore_error_handler();
    }

    return $result;
}

/**
 * Tests if an input is valid PHP serialized string.
 *
 * Checks if a string is serialized using quick string manipulation
 * to throw out obviously incorrect strings. Unserialize is then run
 * on the string to perform the final verification.
 *
 * Valid serialized forms are the following:
 * <ul>
 * <li>boolean: <code>b:1;</code></li>
 * <li>integer: <code>i:1;</code></li>
 * <li>double: <code>d:0.2;</code></li>
 * <li>string: <code>s:4:"test";</code></li>
 * <li>array: <code>a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}</code></li>
 * <li>object: <code>O:8:"stdClass":0:{}</code></li>
 * <li>object (implementing Serializable): <code>C:8:"stdClass":0:{}</code></li>
 * <li>null: <code>N;</code></li>
 * </ul>
 *
 * @author    Chris Smith <chris@cs278.org>
 * @copyright Copyright © 2009, 2014 Chris Smith
 *
 * @param string $value  Value to test for serialized form
 * @param mixed  $result Result of unserialize() of the $value
 *
 * @return bool True if $value is serialized data, otherwise false
 */
function isSerialized($value, &$result = null)
{
    // Bit of a give away this one
    if (!is_string($value)) {
        return false;
    }

    if ('' === $value) {
        return false;
    }

    /*
     * Smallest variant of each serialized type.
     *
     * string(2) "N;"
     * string(4) "b:0;"
     * string(4) "i:0;"
     * string(4) "d:0;"
     * string(6) "a:0:{}"
     * string(7) "s:0:"";"
     * string(12) "O:1:"a":0:{}"
     * string(12) "C:1:"b":0:{}"
     */
    $length = strlen($value);
    $end = '';

    if ($length < 2) {
        return false;
    }

    switch ($value[0]) {
        case 's':
            if ($length < 7) {
                return false;
            }

            if ($value[$length - 2] !== '"') {
                return false;
            }
            // Fall through
        case 'b':
        case 'i':
        case 'd':
            if ($length < 4) {
                return false;
            }

            // This looks odd but it is quicker than isset()ing
            $end .= ';';
            // Fall through
        case 'a':
        case 'O':
        case 'C':
            $end .= '}';

            if ('a' === $value[0] && $length < 6) {
                return false;
            }

            if (('O' === $value[0] || 'C' === $value[0]) && $length < 12) {
                return false;
            }

            if ($value[1] !== ':') {
                return false;
            }

            switch (true) {
                case '0' === $value[2]:
                case '1' === $value[2]:
                case '2' === $value[2]:
                case '3' === $value[2]:
                case '4' === $value[2]:
                case '5' === $value[2]:
                case '6' === $value[2]:
                case '7' === $value[2]:
                case '8' === $value[2]:
                case '9' === $value[2]:
                break;

                default:
                    return false;
            }
            // Fall through
        case 'N':
            $end .= ';';

            if ($value[$length - 1] !== $end[0]) {
                return false;
            }

            break;

        default:
            return false;
    }

    try {
        $result = unserialize($value);
    } catch (SyntaxError $e) {
        return false;
    }

    return true;
}
