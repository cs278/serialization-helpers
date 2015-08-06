<?php

/*
 * This file is part of the PHP Serialization Helpers package.
 *
 * © Chris Smith <chris@cs278.org>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Cs278\SerializationHelpers\Exception\SyntaxError;

use Cs278\SerializationHelpers\Exception\SyntaxError;

/**
 * An error occurred parsing the serialized string at a specific byte.
 */
final class ErrorAtOffsetException extends \LogicException implements SyntaxError
{
    /** How long is the displayed snippet. */
    const SNIPPET_LENGTH = 80;

    /** @var mixed */
    private $input;

    /** @var int Byte offset of error */
    private $offset;

    /**
     * Constructor.
     *
     * @param mixed      $input
     * @param int        $offset
     * @param string     $message
     * @param \Exception $previous
     */
    public function __construct($input, $offset, $message, \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);

        $this->input = $input;
        $this->offset = $offset;
    }

    /** {@inheritdoc} */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Fetch the byte offset the error occurred at.
     *
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Return an extract of the serialized input highlighting the error.
     *
     * @return string
     */
    public function getSnippet()
    {
        $snippetOffset = max(0, $this->offset - (self::SNIPPET_LENGTH / 2));
        $snippet = substr($this->input, $snippetOffset);

        if ($snippetOffset > 0) {
            $snippet[0] = $snippet[1] = $snippet[2] = '.';
        }

        if (strlen($snippet) > self::SNIPPET_LENGTH) {
            $snippet = substr($snippet, 0, self::SNIPPET_LENGTH - 3).'...';
        }

        // Append indicator underneath
        $snippet .= "\n".str_repeat(' ', $this->offset - $snippetOffset).'^';

        return $snippet;
    }
}
