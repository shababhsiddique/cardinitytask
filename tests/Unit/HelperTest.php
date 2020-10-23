<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;


class HelperTest extends TestCase
{

    
    /**
     * Luhn algorithm helper stress test
     */
    public function testLuhnAlgorithm(){

        $validNumbers = [
            "4111111111111111",
            "4200000000000000",
            "4222222222222",
            "5555555555554444",
            "5454545454545454",
            "4024007180992017",
            "5383020237565480",
            "6011682468600266",
            "346304784944265",
        ];

        foreach($validNumbers as $aPAN){
            $this->assertTrue(checkLuhn($aPAN));    
        }
        
        $invalidNumbers = [
            "4024007180992019",
            "5383020237565489",
            "6011682468600269",
            "346304784944269",   
        ];

        foreach($invalidNumbers as $aPAN){
            $this->assertNotTrue(checkLuhn($aPAN));
        }
    }


    


}
