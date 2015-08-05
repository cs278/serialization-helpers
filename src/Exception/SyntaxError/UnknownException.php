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
 * An unrecognised syntax error occurred.
 */
final class UnknownException extends \LogicException implements SyntaxError
{
    /** @var mixed */
    private $input;

    /**
     * Constructor.
     *
     * @param mixed      $input
     * @param string     $message
     * @param \Exception $previous
     */
    public function __construct($input, $message, \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);

        $this->input = $input;
    }

    /** {@inheritdoc} */
    public function getInput()
    {
        return $this->input;
    }
}
