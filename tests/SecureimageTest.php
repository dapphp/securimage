<?php
// start session before output
session_start();

/**
 * Tests for Secureimage class
 */
class SecurimageTest extends PHPUnit_Framework_TestCase
{

    /**
     * Tests that naive implementation with ArrayAccess is compatible with Secureimage codes
     *
     * @return void
     */
    public function testInjectedSession() {
        $GLOBALS['_SESSION'] = new ObjectSession;

        $s1 = new Securimage;
        $s1->createCode();
        $s2 = new Securimage;

        $this->assertEquals($s1->getCode(), $s2->getCode());
    }

}

/**
 * Most simple ArrayAccess implementation
 */
class ObjectSession implements ArrayAccess
{

    private $_array = [];

    public function offsetExists($offset) {
        return array_key_exists($offset, $this->_array);
    }

    public function offsetGet($offset) {
        return $this->_array[$offset];
    }

    public function offsetSet($offset, $value) {
        $this->_array[$offset] = $value;
    }

    public function offsetUnset($offset) {
        unset($this->_array[$offset]);
    }

}
