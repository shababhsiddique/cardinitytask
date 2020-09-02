<?php

class CreditcardType
{
    public static $creditcardTypes = [
        [
            'Name' => 'American Express',
            'cardLength' => [15],
            'cardPrefix' => ['34', '37'],
        ], [
            'Name' => 'Maestro',
            'cardLength' => [12, 13, 14, 15, 16, 17, 18, 19],
            'cardPrefix' => ['5018', '5020', '5038', '6304', '6759', '6761', '6763'],
        ], [
            'Name' => 'Mastercard',
            'cardLength' => [16],
            'cardPrefix' => ['51', '52', '53', '54', '55'],
        ], [
            'Name' => 'Visa',
            'cardLength' => [13, 16],
            'cardPrefix' => ['4'],
        ], [
            'Name' => 'JCB',
            'cardLength' => [16],
            'cardPrefix' => ['3528', '3529', '353', '354', '355', '356', '357', '358'],
        ], [
            'Name' => 'Discover',
            'cardLength' => [16],
            'cardPrefix' => ['6011', '622126', '622127', '622128', '622129', '62213','62214', '62215', '62216', '62217', '62218', '62219','6222', '6223', '6224', '6225', '6226', '6227', '6228','62290', '62291', '622920', '622921', '622922', '622923','622924', '622925', '644', '645', '646', '647', '648','649', '65'],
        ], [
            'Name' => 'Solo',
            'cardLength' => [16, 18, 19],
            'cardPrefix' => ['6334', '6767'],
        ], [
            'Name' => 'Unionpay',
            'cardLength' => [16, 17, 18, 19],
            'cardPrefix' => ['622126', '622127', '622128', '622129', '62213', '62214','62215', '62216', '62217', '62218', '62219', '6222', '6223','6224', '6225', '6226', '6227', '6228', '62290', '62291','622920', '622921', '622922', '622923', '622924', '622925'],
        ], [
            'Name' => 'Diners Club',
            'cardLength' => [14],
            'cardPrefix' => ['300', '301', '302', '303', '304', '305', '36'],
        ], [
            'Name' => 'Diners Club US',
            'cardLength' => [16],
            'cardPrefix' => ['54', '55'],
        ], [
            'Name' => 'Diners Club Carte Blanche',
            'cardLength' => [14],
            'cardPrefix' => ['300', '305'],
        ], [
            'Name' => 'Laser',
            'cardLength' => [16, 17, 18, 19],
            'cardPrefix' => ['6304', '6706', '6771', '6709'],
        ],
    ];    
}

 
if (!function_exists('getCardType')) {
    /**
     * Returns the cards type
     *
     * @param string PAN number
     *
     * @return string card type name.
     *
     * */
    function getCardType($pan)
    {
        $pan = trim($pan);
        $type = 'Unknown';
        foreach (CreditcardType::$creditcardTypes as $card) {
            if (! in_array(strlen($pan), $card['cardLength'])) {
                continue;
            }
            $prefixes = '/^(' . implode('|', $card['cardPrefix']) . ')/';
            if (preg_match($prefixes, $pan) == 1) {
                $type = $card['Name'];
                break;
            }
        }
        return $type; 
    }
}


if(!function_exists('checkLuhn')){


     /**
     * Returns true if PAN number passed the Luhn Algorithm
     *
     * @param string PAN number
     *
     * @return bool true or false.
     *
     * */    
    function checkLuhn($number) {

        // Strip any non-digits (useful for credit card numbers with spaces and hyphens)
        $number=preg_replace('/\D/', '', $number);
    
        // Set the string length and parity
        $number_length=strlen($number);
        $parity=$number_length % 2;
    
        // Loop through each digit and do the maths
        $total=0;
        for ($i=0; $i<$number_length; $i++) {
        $digit=$number[$i];
        // Multiply alternate digits by two
        if ($i % 2 == $parity) {
            $digit*=2;
            // If the sum is two digits, add them together (in effect)
            if ($digit > 9) {
            $digit-=9;
            }
        }
        // Total up the digits
        $total+=$digit;
        }
    
        // If the total mod 10 equals 0, the number is valid
        return ($total % 10 == 0) ? TRUE : FALSE;
    
    }
}