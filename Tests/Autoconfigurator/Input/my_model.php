<?php
return [
    'columns' => [
        'tca_image_type_file' => [
            'label' => 'Image',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'maxitems' => 1,
                'minitems' => 0,
            ],
        ],
        'tca_image_type_inline' => [
            'label' => 'Image Inline',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'sys_file_reference',
                'maxitems' => 1,
                'minitems' => 0,    
            ],
        ],
        'tca_content_or_pages' => [
            'label' => 'Content or Pages',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages,tt_content',
                'size' => 5,
                'minitems' => 0,
                'maxitems' => 999,
            ],
        ],
        'tca_content_only' => [
            'label' => 'Content only',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tt_content',
                'size' => 5,
                'minitems' => 0,
                'maxitems' => 999,
            ],
        ],
    ],
];
