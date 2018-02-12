<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Helpers\Cleaners;

class CleanersTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_getStringFromUnicodeValue()
    {
        $this->assertTrue(true);
    }

    /**
     * Parsing a currency without any decimal
     *
     * @return void
     */
    public function test_cleanContractValue_Decimal()
    {
        $this->assertEquals(Cleaners::cleanContractValue('$1000.99'), '1000.99');
    }

    /**
     * Parsing a currency without any decimal
     *
     * @return void
     */
    public function test_cleanContractValue_NoDecimal()
    {
        $this->assertEquals(Cleaners::cleanContractValue('$1000'), '1000');
    }

    /**
     * Parsing a currency without any decimal
     *
     * @return void
     */
    public function test_cleanContractValue_NoDecimalWithComma()
    {
        $this->assertEquals(Cleaners::cleanContractValue('$1,000'), '1000');
    }

    /**
     * Parsing a currency without any decimal
     *
     * @return void
     */
    public function test_cleanContractValue_FrenchCurrencyNoDecimal()
    {
        $this->assertEquals(Cleaners::cleanContractValue('1000$'), '1000');
    }

    /**
     * Parsing a currency without any decimal
     *
     * @return void
     */
    public function test_cleanContractValue_FrenchCurrencyWithDecimal()
    {
        $this->assertEquals(Cleaners::cleanContractValue('1000,99$'), '1000.99');
    }

    /**
     * Parsing a currency without any decimal
     *
     * @return void
     */
    public function test_cleanContractValue_FranglishCurrencyWithDecimalAndComma()
    {
        $this->assertEquals(Cleaners::cleanContractValue('1,000,99$'), '1000.99');
    }

    /**
     * Parsing a currency without any decimal
     *
     * @return void
     */
    public function test_cleanContractValue_LessThanADollar()
    {
        $this->assertEquals(Cleaners::cleanContractValue('$0.99'), '0.99');
    }

    /**
     * Parsing a currency without any decimal
     *
     * @return void
     */
    public function test_cleanContractValue_FrenchCurrencyLessThanADollar()
    {
        $this->assertEquals(Cleaners::cleanContractValue('0.99$'), '0.99');
    }

    /**
     * Parsing a currency without any decimal
     *
     * @return void
     */
    public function test_cleanContractValue_FrenchWithSpaces()
    {
        $this->assertEquals(Cleaners::cleanContractValue(' 10 $'), '10');
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_cleanHtmlValue()
    {
        $this->assertTrue(true);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_cleanLabelText()
    {
        $this->assertTrue(true);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_removeLinebreaks()
    {
        $this->assertTrue(true);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_convertToUtf8()
    {
        $this->assertTrue(true);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_applyInitialSourceHtmlTransformations()
    {
        $this->assertTrue(true);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_cleanIncomingUrl()
    {
        $this->assertTrue(true);
    }
}
