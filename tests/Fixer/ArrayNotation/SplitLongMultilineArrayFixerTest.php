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
 * @covers \PhpCsFixer\Fixer\ArrayNotation\SplitLongMultilineArrayFixer
 */
final class SplitLongMultilineArrayFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases()
    {
        return [
            ['<?php $x = array();'],
            [' <?php return ["0123456789_123456789_123456789","0123456789_123456789_123456789","0123456789_123456789_123456789"];'],
            [' <?php return ["0123456789_123456789_123456789","0123456789_123456789_123456789","0123456789_123456789_123456789",];'],
            ['
<?php
    $x = [
        "0123456789_123456789_123456789",
        "0123456789_123456789_123456789",
        "0123456789_123456789_123456789",
        "0123456789_123456789_123456789"
    ];            
            ','
<?php
    $x = ["0123456789_123456789_123456789","0123456789_123456789_123456789","0123456789_123456789_123456789","0123456789_123456789_123456789"];            
            '],
            // very ugly formatted input
            ['
<?php
    $x = [
        "property_of_same_name" => "0123456789_123456789_123456789",
        "another_property" => "0123456789_123456789_123456789",
        "one_more_property" => "0123456789_123456789_123456789",
        "last_one" => "0123456789_123456789_123456789"
    ];            
            ','
<?php
    $x = ["property_of_same_name" => "0123456789_123456789_123456789",
                "another_property" => "0123456789_123456789_123456789",
    "one_more_property" => "0123456789_123456789_123456789",              "last_one" => "0123456789_123456789_123456789"];            
            '],
            ['
<?php
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
            ','
<?php
    $x = ["id" => 1,"language" => "PHP","details" => ["0123456789_123456789_123456789","0123456789_123456789_123456789","0123456789_123456789_123456789","0123456789_123456789_123456789"]];            
            '],
            ['
<?php
    $x = [
        "id" => 1,
        "language" => "PHP",
        "details1" => ["0123456789_123456789_123456789","0123456789_123456789_123456789"],
        "details2" => ["0123456789_123456789_123456789","0123456789_123456789_123456789"]
    ];            
            ','
<?php
    $x = ["id" => 1, "language" => "PHP", "details1" => ["0123456789_123456789_123456789","0123456789_123456789_123456789"], "details2" => ["0123456789_123456789_123456789","0123456789_123456789_123456789"]];            
            '],
            ['
<?php
    $x = [
        new Vendor\BlogPost(1,"A not so long title", "Short description"),
        new Vendor\BlogPost(1,"A not so long title", "Short description"),
        new Vendor\BlogPost(1,"A not so long title", "Short description")
    ];            
            ','
<?php
    $x = [new Vendor\BlogPost(1,"A not so long title", "Short description"), new Vendor\BlogPost(1,"A not so long title", "Short description"), new Vendor\BlogPost(1,"A not so long title", "Short description")];            
            '],
            ["
<?php

class SomeClass
{
    public function itDoesSomeRandomWork()
    {
        \$expected = [
            'Vendor/',
            'Vendor/Namespace/',
            'Vendor/Namespace/Product/Product',
            'Vendor/Namespace/Product/ProductId',
            'Vendor/Namespace/User/',
            'Vendor/Namespace/User/User'
        ];
    }
}
            ","
<?php

class SomeClass
{
    public function itDoesSomeRandomWork()
    {
        \$expected = [ 'Vendor/',
            'Vendor/Namespace/', 'Vendor/Namespace/Product/Product',
                      'Vendor/Namespace/Product/ProductId', 'Vendor/Namespace/User/', 'Vendor/Namespace/User/User'
        ];
    }
}
            "],
            ["
<?php

class SomeClass
{
    public function itDoesSomeRandomWork()
    {
        \$expected = [
            'Vendor/',
            'Vendor/Namespace/',
            'Vendor/Namespace/Product/Product',
            'Vendor/Namespace/Product/ProductId',
            'Vendor/Namespace/User/',
            'Vendor/Namespace/User/User',
        ];
    }
}
            ","
<?php

class SomeClass
{
    public function itDoesSomeRandomWork()
    {
        \$expected = [ 'Vendor/', 'Vendor/Namespace/', 'Vendor/Namespace/Product/Product', 'Vendor/Namespace/Product/ProductId', 'Vendor/Namespace/User/', 'Vendor/Namespace/User/User',];
    }
}
            "],
        ];
    }
}
