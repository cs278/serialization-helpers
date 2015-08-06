<?php

/*
 * This file is part of the PHP Serialization Helpers package.
 *
 * © Chris Smith <chris@cs278.org>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Cs278\SerializationHelpers\Tests;

use Cs278\SerializationHelpers as serialization;
use Cs278\SerializationHelpers\Exception\SyntaxError;

class SerializationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataUnserializeInvalid
     * @expectedException Cs278\SerializationHelpers\Exception\SyntaxError
     */
    public function testUnserializeInvalid($input)
    {
        serialization\unserialize($input);
    }

    public function dataUnserializeInvalid()
    {
        return array(
            array('O:'),
            array(null),
            array(false),
            array(0),
            array(1.1),
        );
    }

    /**
     * @dataProvider dataUnserializeBadArgument
     */
    public function testUnserializeBadArgument($input, $message)
    {
        $this->setExpectedException('PHPUnit_Framework_Error', $message);
        serialization\unserialize($input);
    }

    public function dataUnserializeBadArgument()
    {
        return array(
            array(array(), 'unserialize() expects parameter 1 to be string, array given'),
            array(new \stdClass(), 'unserialize() expects parameter 1 to be string, object given'),
        );
    }

    /**
     * @dataProvider dataUnserialize
     */
    public function testUnserialize($expected, $input)
    {
        $result = serialization\unserialize($input);

        if (is_object($expected)) {
            $this->assertEquals($expected, $result);
        } else {
            $this->assertSame($expected, $result);
        }
    }

    public function dataUnserialize()
    {
        return array(
            array(null, 'N;'),
            array(true, 'b:1;'),
            array('foo', 's:3:"foo";'),
            array((object) array('foo' => 'bar'), 'O:8:"stdClass":1:{s:3:"foo";s:3:"bar";}'),
            array(array(1, 1, 2, 3), 'a:4:{i:0;i:1;i:1;R:2;i:2;i:2;i:3;i:3;}'),
            array(new TestStub(''), 'C:41:"Cs278\SerializationHelpers\Tests\TestStub":0:{}'),
            array(new TestStub('ROBOTS'), 'C:41:"Cs278\SerializationHelpers\Tests\TestStub":6:{ROBOTS}'),
            array('f', 's:1:"f"'), // PHP doesn't require the trailing semi colon
            array('f', 's:1:"f"GARBAG;E'), // PHP will even accept complete garbage
        );
    }

    /**
     * @dataProvider dataIsSerialized
     */
    public function testIsSerialize($expected, $input)
    {
        $this->assertTrue(serialization\isSerialized($input, $result));

        if (is_object($expected)) {
            $this->assertEquals($expected, $result);
        } else {
            $this->assertSame($expected, $result);
        }
    }

    public function dataIsSerialized()
    {
        return array(
            array(null, 'N;'),
            array(true, 'b:1;'),
            array(false, 'b:0;'),
            array(1729, 'i:1729;'),
            array(3.14159, 'd:3.14159;'),
            array('php', 's:3:"php";'),
            array(array(0 => true, 'x' => 32), 'a:2:{i:0;b:1;s:1:"x";i:32;}'),
            array((object) array(), 'O:8:"stdClass":0:{}'),
            array(new TestStub('ROBOTS'), 'C:41:"Cs278\SerializationHelpers\Tests\TestStub":6:{ROBOTS}'),
        );
    }

    /**
     * @dataProvider dataIsSerializedInvalid
     */
    public function testIsSerializedInvalid($input)
    {
        $this->assertFalse(serialization\isSerialized($input, $result));
        $this->assertSame(null, $result);
    }

    public function dataIsSerializedInvalid()
    {
        $isZend = !defined('HHVM_VERSION');

        return array_filter(array(
            array('N'),
            array('b:x;'),
            $isZend ? array('b:2;') : null,
            $isZend ? array('i:;') : null,
            array('d:3.14.159;'),
            array('s:3:"php"'),
            array('a:2:{i:0;s:1:"x";i:32;}'),
            array('O:8:"stdClas":0:{}'),
            array('a:1:{}'),
        ), 'is_array');
    }

    public function testSyntaxErrorHasInput()
    {
        $input = 's:"';

        try {
            serialization\unserialize($input);
        } catch (SyntaxError $e) {
            $this->assertSame($input, $e->getInput());

            return;
        }

        $this->fail('Should have caught SyntaxError');
    }
}

class TestStub implements \Serializable
{
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function serialize()
    {
        return $this->value;
    }

    public function unserialize($serialized)
    {
        $this->value = $serialized;
    }
}
