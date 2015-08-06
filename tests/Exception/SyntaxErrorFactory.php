<?php

/*
 * This file is part of the PHP Serialization Helpers package.
 *
 * © Chris Smith <chris@cs278.org>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Cs278\SerializationHelpers\Tests\Exception;

use Cs278\SerializationHelpers\Exception\SyntaxErrorFactory;

class SyntaxErrorFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataCreate
     */
    public function testCreate($message, $type, $expectedMessage, $expectedOffset = null)
    {
        $input = serialize('foobar');
        $previous = new \Exception;
        $factory = new SyntaxErrorFactory;

        $exception = $factory->create($input, $message, $previous);

        $this->assertInstanceOf('Exception', $exception);
        $this->assertInstanceOf('Cs278\SerializationHelpers\Exception\Exception', $exception);
        $this->assertInstanceOf('Cs278\SerializationHelpers\Exception\SyntaxError', $exception);
        $this->assertInstanceOf("Cs278\SerializationHelpers\Exception\SyntaxError\\${type}Exception", $exception);

        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame($input, $exception->getInput());

        if (null !== $expectedOffset) {
            $this->assertTrue(method_exists($exception, 'getOffset'));
            $this->assertSame($expectedOffset, $exception->getOffset());
        }
    }

    public function dataCreate()
    {
        return array(
            array(
                '',
                'Unknown',
                'Unknown syntax error occurred',
            ),
            array(
                'Wibble',
                'Unknown',
                'Unknown syntax error occurred: Wibble',
            ),
            array(
                'Unexpected end of serialized data',
                'UnexpectedEnd',
                'Unexpected end of serialized data',
            ),
            array(
                'unserialize(): Unexpected end of serialized data',
                'UnexpectedEnd',
                'Unexpected end of serialized data',
            ),
            array(
                'Error at offset 0 of 4000 bytes',
                'ErrorAtOffset',
                'Syntax error at byte 0 of 4000 bytes in serialized input',
                0,
            ),
            array(
                'unserialize(): Error at offset 2000 of 4000 bytes',
                'ErrorAtOffset',
                'Syntax error at byte 2000 of 4000 bytes in serialized input',
                2000,
            ),
        );
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage ErrorException does not related to an unserialize error
     */
    public function testCreateFromErrorExceptionThrowsOnBadException()
    {
        $factory = new SyntaxErrorFactory;
        $factory->createFromErrorException(new \ErrorException('', 0, 0, '', 0));
    }

    /**
     * @dataProvider dataCreateUnexpectedEnd
     */
    public function testCreateUnexpectedEnd($input)
    {
        $this->skipOnHhvm();

        // Sanity check as the set_error_handler() call breaks PHPUnit's
        // exception catching.
        $this->assertInternalType('string', $input);

        set_error_handler(function ($code, $message, $file, $line) {
            throw new \ErrorException(
                $message,
                E_NOTICE,
                0,
                '',
                0
            );
        }, E_NOTICE);

        try {
            \unserialize($input);
        } catch (\ErrorException $error) {
            restore_error_handler();

            $factory = new SyntaxErrorFactory;
            $exception = $factory->createFromErrorException($error);

            $this->assertInstanceOf('Cs278\SerializationHelpers\Exception\SyntaxError', $exception);
            $this->assertInstanceOf('Cs278\SerializationHelpers\Exception\SyntaxError\UnexpectedEndException', $exception);

            $this->assertSame($input, $exception->getInput());
            $this->assertSame('Unexpected end of serialized data', $exception->getMessage());
            $this->assertInstanceOf('ErrorException', $exception->getPrevious());

            return; // Normal test execution ends here.
        }

        restore_error_handler();

        $this->fail('No error was raised');
    }

    public function dataCreateUnexpectedEnd()
    {
        return array(
            array('a:1:{};'),
        );
    }

    /**
     * @dataProvider dataCreateErrorAtOffset
     */
    public function testCreateErrorAtOffset($input, $message, $offset)
    {
        $this->skipOnHhvm();

        // Sanity check as the set_error_handler() call breaks PHPUnit's
        // exception catching.
        $this->assertInternalType('string', $input);

        set_error_handler(function ($code, $message, $file, $line) {
            throw new \ErrorException(
                $message,
                E_NOTICE,
                0,
                '',
                0
            );
        }, E_NOTICE);

        try {
            \unserialize($input);
        } catch (\ErrorException $error) {
            restore_error_handler();

            $factory = new SyntaxErrorFactory;
            $exception = $factory->createFromErrorException($error);

            $this->assertInstanceOf('Cs278\SerializationHelpers\Exception\SyntaxError', $exception);
            $this->assertInstanceOf('Cs278\SerializationHelpers\Exception\SyntaxError\ErrorAtOffsetException', $exception);

            $this->assertSame($input, $exception->getInput());
            $this->assertSame($offset, $exception->getOffset());
            $this->assertSame($message, $exception->getMessage() . ":\n\n" . $exception->getSnippet());
            $this->assertInstanceOf('ErrorException', $exception->getPrevious());

            return; // Normal test execution ends here.
        }

        restore_error_handler();

        $this->fail('No error was raised');
    }

    public function dataCreateErrorAtOffset()
    {
        return array(
            array(
                'S:"foo";',
<<<'EOT'
Syntax error at byte 0 of 8 bytes in serialized input:

S:"foo";
^
EOT
                ,
                0,
            ),
            array(
                's:100:"";',
<<<'EOT'
Syntax error at byte 2 of 9 bytes in serialized input:

s:100:"";
  ^
EOT
                ,
                2,
            ),
            array(
                'a:20:{i:0;s:1:"x";i:1;s:1:"x";i:2;s:1:"x";i:3;s:1:"x";i:4;s:1:"x";i:5;s:1:"x";i:6;s:1:"x";i:7;s:1:"x";i:8;s:1:"x;i:9;s:1:"x";i:10;s:1:"x";i:11;s:1:"x";i:12;s:1:"x";i:13;s:1:"x";i:14;s:1:"x";i:15;s:1:"x";i:16;s:1:"x";i:17;s:1:"x";i:18;s:1:"x";i:19;s:1:"x";}',
<<<'EOT'
Syntax error at byte 112 of 256 bytes in serialized input:

...x";i:6;s:1:"x";i:7;s:1:"x";i:8;s:1:"x;i:9;s:1:"x";i:10;s:1:"x";i:11;s:1:"x...
                                        ^
EOT
                ,
                112,
            ),
        );
    }

    private function skipOnHhvm($message = '')
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped($message);
        }
    }
}
