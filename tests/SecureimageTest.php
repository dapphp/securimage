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

    /**
     * Tests session injection with multiple namespaces
     *
     * @return void
     */
    public function testInjectedSessionMultipleNamespaces() {
        $GLOBALS['_SESSION'] = new ObjectSession;

        $s1 = new Securimage(['namespace' => 'n1']);
        $s1->createCode();
        $s2 = new Securimage(['namespace' => 'n1']);
        $this->assertEquals($s1->getCode(), $s2->getCode());

        $s3 = new Securimage(['namespace' => 'n2']);
        $s3->createCode();
        $s4 = new Securimage(['namespace' => 'n2']);

        $this->assertEquals($s1->getCode(), $s2->getCode()); // still working, which didn't after commit e1b0db4be0c247b0bb6303ab555868ad19ea1a1d
        $this->assertEquals($s3->getCode(), $s4->getCode());
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
