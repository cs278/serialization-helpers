<?php

/*
 * This file is part of the PHP Serialization Helpers package.
 *
 * © Chris Smith <chris@cs278.org>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Cs278\SerializationHelpers\Exception;

/**
 * Handles constructing concrete implementations of SyntaxError.
 *
 * Responsible for converting a PHP error regarding the unserialzation process
 * into an exception implementing SyntaxError.
 */
final class SyntaxErrorFactory
{
    /**
     * Convert an error exception into SyntaxError exception.
     *
     * @param \ErrorException $e ErrorException raised from unserialize() call
     *
     * @return SyntaxError
     *
     * @throws \InvalidArgumentException Iff ErrorException was not raised by
     *                                   a call to unserialize()
     */
    public function createFromErrorException(\ErrorException $e)
    {
        $frame = $this->findStackFrame($e);

        if (null === $frame) {
            throw new \InvalidArgumentException(
                'ErrorException does not related to an unserialize error'
            );
        }

        $message = preg_replace('{^unserialize\(\):\s+}', '', $e->getMessage());
        $input = isset($frame['args'][0]) ? $frame['args'][0] : null;

        if ($message === 'Unexpected end of serialized data') {
            return new SyntaxError\UnexpectedEndException(
                $input,
                $message,
                $e
            );
        }

        if (preg_match('{^Error at offset (\d+) of (\d+) bytes$}', $message, $matches) > 0) {
            $offset = (int) $matches[1];
            $length = (int) $matches[2];
            $message = sprintf('Syntax error at byte %u of %u bytes in serialized input', $offset, $length);

            return new SyntaxError\ErrorAtOffsetException(
                $input,
                $offset,
                $message,
                $e
            );
        }

        return new SyntaxError\UnknownException(
            $input,
            sprintf('Unknown syntax error occurred: %s', $e->getMessage()),
            $e
        );
    }

    /**
     * Find stack frame relating to unserialize function.
     *
     * @param \Exception $e
     *
     * @return array|null
     */
    private function findStackFrame(\Exception $e)
    {
        $stack = $e->getTrace();

        do {
            $frame = array_shift($stack);

            if (
                isset($frame['function'])
                && $frame['function'] === 'unserialize'
                && empty($frame['class'])
            ) {
                break;
            }

            $frame = null;
        } while (count($stack) > 0);

        return $frame;
    }
}
