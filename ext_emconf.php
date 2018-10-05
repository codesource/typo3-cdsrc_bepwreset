<?php

/* * *************************************************************
 * Extension Manager/Repository config file for ext "cdsrc_bepwreset".
 *
 * Auto generated 01-02-2015 10:24
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 * ************************************************************* */

$EM_CONF[$_EXTKEY] = [
    'title' => 'BE User Password Reset',
    'description' => 'Allow backend user to reset his password from login form. Option can be specified to force Backend user to change his password at first login.',
    'category' => 'Backend',
    'version' => '2.3.2',
    'state' => 'stable',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearCacheOnLoad' => false,
    'author' => 'Matthias Toscanelli',
    'author_email' => 'm.toscanelli@code-source.ch',
    'author_company' => 'Code-Source',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-9.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];

