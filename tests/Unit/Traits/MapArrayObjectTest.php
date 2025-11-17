<?php

declare(strict_types=1);

namespace PayrollCalculator\Tests\Unit\Traits;

use PHPUnit\Framework\TestCase;
use PayrollCalculator\Traits\MapArrayObject;

class MapArrayObjectTest extends TestCase
{
    private TestMapArrayObject $testObject;

    protected function setUp(): void
    {
        $this->testObject = new TestMapArrayObject();
    }

    public function testSetAndGet(): void
    {
        $this->testObject->set('testKey', 'testValue');
        $this->assertEquals('testValue', $this->testObject->get('testKey'));
    }

    public function testGetNonExistentKey(): void
    {
        $this->assertNull($this->testObject->get('nonExistentKey'));
    }

    public function testSetOverwritesExistingValue(): void
    {
        $this->testObject->set('testKey', 'originalValue');
        $this->testObject->set('testKey', 'newValue');
        $this->assertEquals('newValue', $this->testObject->get('testKey'));
    }

    public function testToArray(): void
    {
        $this->testObject->set('key1', 'value1');
        $this->testObject->set('key2', 'value2');
        $this->testObject->set('key3', 'value3');

        $array = $this->testObject->toArray();
        
        $this->assertIsArray($array);
        $this->assertContains('value1', $array);
        $this->assertContains('value2', $array);
        $this->assertContains('value3', $array);
    }

    public function testKeys(): void
    {
        $this->testObject->set('key1', 'value1');
        $this->testObject->set('key2', 'value2');
        $this->testObject->set('key3', 'value3');

        $keys = $this->testObject->keys();
        
        $this->assertIsArray($keys);
        $this->assertContains('key1', $keys);
        $this->assertContains('key2', $keys);
        $this->assertContains('key3', $keys);
    }

    public function testValues(): void
    {
        $this->testObject->set('key1', 'value1');
        $this->testObject->set('key2', 'value2');
        $this->testObject->set('key3', 'value3');

        $values = $this->testObject->values();
        
        $this->assertIsArray($values);
        $this->assertContains('value1', $values);
        $this->assertContains('value2', $values);
        $this->assertContains('value3', $values);
    }

    public function testToString(): void
    {
        $this->testObject->set('key1', 'value1');
        $this->testObject->set('key2', 'value2');

        $string = $this->testObject->toString();
        
        $this->assertIsString($string);
        $this->assertStringContainsString('value1', $string);
        $this->assertStringContainsString('value2', $string);
    }

    public function testKeyToString(): void
    {
        $this->testObject->set('key1', 'value1');
        $this->testObject->set('key2', 'value2');

        $string = $this->testObject->keyToString();
        
        $this->assertIsString($string);
        $this->assertStringContainsString('key1', $string);
        $this->assertStringContainsString('key2', $string);
    }

    public function testCount(): void
    {
        $this->assertEquals(0, $this->testObject->count());
        
        $this->testObject->set('key1', 'value1');
        $this->assertEquals(1, $this->testObject->count());
        
        $this->testObject->set('key2', 'value2');
        $this->assertEquals(2, $this->testObject->count());
        
        $this->testObject->set('key3', 'value3');
        $this->assertEquals(3, $this->testObject->count());
    }

    public function testSum(): void
    {
        $this->testObject->set('num1', 10);
        $this->testObject->set('num2', 20.5);
        $this->testObject->set('num3', 30);

        $sum = $this->testObject->sum();
        
        $this->assertEquals(60.5, $sum);
    }

    public function testSumWithNonNumericValues(): void
    {
        $this->testObject->set('num1', 10);
        $this->testObject->set('str1', 'test');
        $this->testObject->set('num2', 20);

        $sum = $this->testObject->sum();
        
        $this->assertEquals(30, $sum);
    }
}

/**
 * Test class to demonstrate MapArrayObject trait usage
 */
class TestMapArrayObject
{
    use MapArrayObject;
}
