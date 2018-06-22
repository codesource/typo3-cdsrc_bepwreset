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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
        if (class_exists(ConnectionPool::class)) {
            /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
            $users = $queryBuilder->select('*')
                                   ->from('be_users')
                                   ->where($queryBuilder->expr()
                                        ->eq($this::getHash('be_users.username','be_users.tx_cdsrcbepwreset_resetHash'),
                                                $queryBuilder->createNamedParameter($hash,\PDO::PARAM_STR))
                                   )->execute()
                                   ->fetchAll();
        } else {
            $users = BackendUtility::getRecordsByField('be_users', 'deleted', 0, ' AND ' . $whereClause);
        }
        if (is_array($users) && count($users) === 1) {
            return $users[0];
        }

        return false;
    }

}
