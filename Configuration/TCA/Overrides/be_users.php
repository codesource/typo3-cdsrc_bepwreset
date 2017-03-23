<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

/**
 * Add extra field to force password reset at next login
 */
$newBeUsersColumns = array(
    'tx_cdsrcbepwreset_resetAtNextLogin' => array(
        'label' => 'LLL:EXT:cdsrc_bepwreset/Resources/Private/Language/locallang_db.xml:be_users.resetAtNextLogin',
        'config' => array(
            'type' => 'check',
            'default' => 0
        )
    ),
    'tx_cdsrcbepwreset_resetHash' => array(
        'config' => array(
            'type' => 'passthrough',
        )
    ),
    'tx_cdsrcbepwreset_resetHashValidity' => array(
        'config' => array(
            'type' => 'passthrough',
        )
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $newBeUsersColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users', 'tx_cdsrcbepwreset_resetAtNextLogin');

