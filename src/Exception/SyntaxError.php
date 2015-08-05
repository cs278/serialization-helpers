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
 * Defines a syntactical error found during unserializeation.
 */
interface SyntaxError extends Exception
{
    /**
     * Return the serialized data passed to unserialize().
     *
     * @return mixed
     */
    public function getInput();
}
