<?php
/**
 * @copyright Copyright (c) 2020 Code-Source
 */
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if(defined('TYPO3_branch') && TYPO3_branch === '8.7') {
    $GLOBALS['TBE_STYLES']['skins']['cdsrc_bepwreset'] = [
        'name' => 'Backend login form fixes',
        'stylesheetDirectories' => [
            'visual' => 'EXT:cdsrc_bepwreset/Resources/Public/Styles',
        ],
    ];
}