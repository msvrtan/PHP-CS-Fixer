<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests\Fixer\ArrayNotation;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @author Miro Svrtan <miro@mirosvrtan.me>
 *
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\ArrayNotation\LongLinesFixer
 */
final class LongLinesFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideMultiLineArrayExamplesThatWillNotBeCompressed
     * @dataProvider provideMultiLineArrayExamples
     * @dataProvider provideSingleLineArrayExamplesThatWillNotBeDecompressed
     * @dataProvider provideSingleLineArrayExamples
     * @dataProvider provideMethodDefinitionExamplesThatWillNotBeMultilined
     * @dataProvider provideMethodDefinitionExamplesThatNeedToBeMultilined
     * @dataProvider provideMethodDefinitionExamplesThatNeedToBeSingleLined
     */
    public function testFix($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideMultiLineArrayExamplesThatWillNotBeCompressed()
    {

        yield [
            '<?php 
$x = [
    "property_of_same_name" => "0123456789_123456789_123456789",
    "another_property" => "0123456789_123456789_123456789",
    "one_more_property" => "0123456789_123456789_123456789",
    "last_one" => "0123456789_123456789_123456789"
];   

',
        ];

        // very ugly formatted input will not be changed
        yield  [
            '
<?php
    $x = ["property_of_same_name" => "0123456789_123456789_123456789",
                "another_property" => "0123456789_123456789_123456789",
    "one_more_property" => "0123456789_123456789_123456789",              "last_one" => "0123456789_123456789_123456789"];            
            ',
        ];
    }

    public function provideMultiLineArrayExamples()
    {

        // Long array syntax example
        yield [
            '<?php 
$x = array(1, 2, 3);
',
            '<?php 
$x = array(1,
2,
3);
',
        ];

        // Short array syntax example
        yield [
            '<?php 
$x = [1, 2, 3];
',
            '<?php 
$x = [1,
2,
3];
',
        ];

        // It will keep whitespace on the start of the array
        yield [
            '<?php 
$x = [ 1, 2, 3];
',
            '<?php 
$x = [
1,
2,
3];
',
        ];

        // It will keep whitespace on the closing of the array
        yield [
            '<?php 
$x = [ 1, 2, 3 ];
',
            '<?php 
$x = [
1,
2,
3
];
',
        ];

        // It will compress it even when there is a lot of whitespace
        yield [
            '<?php 
$x = [ 1, 2, 3];
',
            '<?php 
$x = [
                                                                             1,
                                                                             2,
                                                                             3];
',
        ];

        // Example with 3rd level of indentation
        yield [
            '<?php 
class Something
{
    function getRandomData()
    {
        return [ "0123456789_123456789_123456789", "0123456789_123456789_123456789" ];
    }
}
',
            '<?php 
class Something
{
    function getRandomData()
    {
        return [
            "0123456789_123456789_123456789", 
            "0123456789_123456789_123456789"
        ];
    }
}
',
        ];

    }

    public function provideSingleLineArrayExamplesThatWillNotBeDecompressed()
    {

        // Simple example using long array syntax
        yield [
            '<?php 
$x = array();',
        ];

        // Simple example using short array syntax
        yield[
            '<?php 
return ["0123456789_123456789_123456789","0123456789_123456789_123456789","0123456789_123456789_123456789"];',
        ];

        // Simple example with 2 levels of indentation
        yield[
            '<?php 
class Something
{
    function getSomeReallyRandomData()
    {
        return ["0123456789_123456789_123456789", "0123456789_123456789_123456789", "0123456789_123456789_123456789"];
    }
}
',
        ];
    }

    public function provideSingleLineArrayExamples()
    {

        yield[
            '<?php 
class Something
{
    function getSomeRandomData($input)
    {
        if( $input === 1 ){
            return [
                "0123456789_123456789_123456789",
                "1123456789_123456789_123456789",
                "2123456789_123456789_123456789"
            ];
        }
    }
}
',
            '<?php 
class Something
{
    function getSomeRandomData($input)
    {
        if( $input === 1 ){
            return ["0123456789_123456789_123456789", "1123456789_123456789_123456789", "2123456789_123456789_123456789"];
        }
    }
}
',
        ];

        // Example with sub array over the limit
        yield [
            '<?php
    $x = [
        "id" => 1,
        "language" => "PHP",
        "details" => [
            "0123456789_123456789_123456789",
            "0123456789_123456789_123456789",
            "0123456789_123456789_123456789",
            "0123456789_123456789_123456789"
        ]
    ];            
            ',
            '<?php
    $x = ["id" => 1,"language" => "PHP","details" => ["0123456789_123456789_123456789","0123456789_123456789_123456789","0123456789_123456789_123456789","0123456789_123456789_123456789"]];            
            ',
        ];

        // Example with sub array under the limit where items of subarray will not be multilined
        yield      [
            '
<?php
    $x = [
        "id" => 1,
        "language" => "PHP",
        "details1" => ["0123456789_123456789_123456789","0123456789_123456789_123456789"],
        "details2" => ["0123456789_123456789_123456789","0123456789_123456789_123456789"]
    ];            
            ',
            '
<?php
    $x = ["id" => 1, "language" => "PHP", "details1" => ["0123456789_123456789_123456789","0123456789_123456789_123456789"], "details2" => ["0123456789_123456789_123456789","0123456789_123456789_123456789"]];            
            ',
        ];

        // Example with object instantiation
        yield       [
            '
<?php
    $x = [
        new Vendor\BlogPost(1,"A not so long title", "Short description"),
        new Vendor\BlogPost(1,"A not so long title", "Short description"),
        new Vendor\BlogPost(1,"A not so long title", "Short description")
    ];            
            ',
            '
<?php
    $x = [new Vendor\BlogPost(1,"A not so long title", "Short description"), new Vendor\BlogPost(1,"A not so long title", "Short description"), new Vendor\BlogPost(1,"A not so long title", "Short description")];            
            ',
        ];
    }

    public function provideMethodDefinitionExamplesThatWillNotBeMultilined()
    {
        yield [
            '<?php 
class Something
{
    function getSomeReallyRandomData()
    {
    }
}
',
        ];
    }

    public function provideMethodDefinitionExamplesThatNeedToBeMultilined()
    {
        yield [
            '<?php 
class Something
{
    public function getSomeReallyRandomData(
        string $someVeryLongVariableName,
        $anotherVeryLongVariableName,
        $evenLongerVariableNameToLookAt
    )
    {
        return "1";
    }
}
',
            '<?php 
class Something
{
    public function getSomeReallyRandomData(string $someVeryLongVariableName, $anotherVeryLongVariableName, $evenLongerVariableNameToLookAt)
    {
        return "1";
    }
}
',
        ];
    }

    public function provideMethodDefinitionExamplesThatNeedToBeSingleLined()
    {
        yield   [
            '<?php 
class Something
{
    public function getRandom( string $someVeryLongVariableName, $anotherVeryLongVariableName )
    {
        return "1";
    }
}
',
            '<?php 
class Something
{
    public function getRandom(
        string $someVeryLongVariableName,
        $anotherVeryLongVariableName
    )
    {
        return "1";
    }
}
',
        ];
    }

}
