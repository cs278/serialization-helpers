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

/**
 * Wrapper around unserialize() that converts syntax errors to exceptions.
 *
 * @param  string $input Serialized string
 * @return mixed
 * @throws SyntaxError   If there was an error unserializing the string
 */
function unserialize($input)
{
    static $errorHandler;

    if (!$errorHandler) {
        $errorHandler = function($code, $message, $file, $line, array $context) {
            // Wrap the error into an ErrorException, for further debugging information.
            $e = new \ErrorException($message, $code, 0, $file, $line);

            // Error messages are prefixed with `unserialize(): `.
            $message = preg_replace('{^unserialize\(\):\s+}', '', $message);

            throw new SyntaxError($message, 0, $e);
        };
    }

    set_error_handler($errorHandler, E_NOTICE);

    try {
        $result = \unserialize($input);

        if ($result === false && $input !== 'b:0;') {
            throw new SyntaxError('Unknown error was encountered');
        }
    } catch (\Exception $e) {
        // Ensure the error handler is restored, even if an exception occurs.
        restore_error_handler();

        throw $e;
    }

    restore_error_handler();

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
 * <li>null: <code>N;</code></li>
 * </ul>
 *
 * @author    Chris Smith <chris@cs278.org>
 * @copyright Copyright © 2009, 2014 Chris Smith
 * @param     string  $value  Value to test for serialized form
 * @param     mixed   $result Result of unserialize() of the $value
 * @return    boolean True if $value is serialized data, otherwise false
 */
function isSerialized($value, &$result = null)
{
    // Bit of a give away this one
    if (!is_string($value)) {
        return false;
    }

    $length = strlen($value);
    $end    = '';

    switch ($value[0]) {
        case 's':
            if ($value[$length - 2] !== '"') {
                return false;
            }
        case 'b':
        case 'i':
        case 'd':
            // This looks odd but it is quicker than isset()ing
            $end .= ';';
        case 'a':
        case 'O':
            $end .= '}';

            if ($value[1] !== ':') {
                return false;
            }

            switch ($value[2]) {
                case 0:
                case 1:
                case 2:
                case 3:
                case 4:
                case 5:
                case 6:
                case 7:
                case 8:
                case 9:
                break;

                default:
                    return false;
            }
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
