<?php

namespace CDSRC\CdsrcBepwreset\Utility;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 *
 */

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Manage hash code
 *
 * @author Matthias Toscanelli <m.toscanelli@code-source.ch>
 */
class HashUtility
{

    /**
     * Generate an hash code for username and code
     *
     * @param string $username
     * @param string $code
     *
     * @return string
     */
    public static function getHash($username, $code)
    {
        return sha1($username . '::' . $code . '::' . md5($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']));
    }

    /**
     * Retrieve user record from hash code
     *
     * @param string $hash
     *
     * @return mixed Return single user record, if no single record is found return FALSE.
     */
    public static function getUser($hash)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        // Search in PHP for the user with a validity code equals to hash
        $users = $queryBuilder->select('username', 'tx_cdsrcbepwreset_resetHash')
                              ->from('be_users')
                              ->where('tx_cdsrcbepwreset_resetHashValidity >= :validity')
                              ->setParameter(':validity', $GLOBALS['EXEC_TIME'])
                              ->execute()
                              ->fetchAll();
        if (is_array($users)) {
            foreach ($users as $user) {
                if (self::getHash($user['username'], $user['tx_cdsrcbepwreset_resetHash']) === $hash) {
                    return $user;
                }
            }

        }

        return false;
    }

}
