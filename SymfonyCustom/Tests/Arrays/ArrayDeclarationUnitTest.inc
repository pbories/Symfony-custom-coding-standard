<?php

function test()
{
    $a = array();

    $b = Array ();

    $c = Array(
        'a' => 1,
    );
}


class TestClass
{
    public $good = array(
        'width'  => '',
        'height' => '',
    );

    private $_bad = Array(
    'width' => '',
    'height' => ''
     );


    public function test()
    {
        $truck = array(
        'width' => '',
            'height' => '',
        );

        $plane = Array(
       'width' => '',
            'height' => '',
        );

        $car = array(
            'width' => '',
            'height' => '',
        );

        $bus = array(
            'width'  =>   '',
            'height' => ''
        );

        $train = array  (
                TRUE,
            FALSE,
            'aaa'
        );

        $inline = array('aaa', 'bbb', 'ccc');
        $inline = array('aaa');
        $inline = Array('aaa');

        $bigone = array(
            'name' => 'bigone',
            'children'   => Array(
            '1a' => 'child',
            '11b' => 'child',
            '111c'      => 'child',
            'children'  => Array(
                            'child' => 'aaa',
                           ),
            ),
            'short_name' => 'big'
        );

        $array1 = [
            [
                '<span>',
            ]
            ,
            [
                '<span>',
            ],
        ];

        $array2 = array(
            [
                '<span>',
            ],[
                '<span>',
            ],
        );

        $array3 = array(
            'a' => ['<span>']
            ,['<span>'],
            'b' => ['<span>']
            ,['<span>'],
        );

        $array4 = array(
            ['<span>']
            ,'a' => ['<span>'],
            ['<span>']
            ,'b' => ['<span>']
        );

        $utf8 = array(
            '/[áàâãªäå]/u' => 'a'
            ,'/[ÁÀÂÃÄÅ]/u'  => 'A',
            '/[ÍÌÎÏ]/u'    => 'I',
            '/[íìîï]/u'    => 'i',
            '/[éèêë]/u'    => 'e',
            '/[ÉÈÊË]/u'    => 'E',
            '/[óòôõºöø]/u' => 'o',
            '/[ÓÒÔÕÖØ]/u'  => 'O',
            '/ÿ/'          => 'y',
            '/Ÿ/'          => 'Y',
            '/æ/'          => 'ae',
            '/Æ/'          => 'AE',
            '/œ/'          => 'oe',
            '/Œ/'          => 'OE',
            '/[úùûü]/u'    => 'u',
            '/[ÚÙÛÜ]/u'    => 'U',
            '/ç/'          => 'c',
            '/Ç/'          => 'C',
            '/ñ/'          => 'n',
            '/Ñ/'          => 'N',
            '/–/'          => '-',
            '/[’‘‹›‚]/u'   => ' ',
            '/[“”«»„]/u'   => ' ',
            '/ +/'         => ' ',
        );
    }
}

return [
    DAMA\DoctrineTestBundle\DAMADoctrineTestBundle::class          => ['test' => true],
    Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle::class => ['all' => true],
];
