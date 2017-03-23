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
        $whereClause = vsprintf("AND CAST(SHA1(CONCAT(%s,'::',%s,'::%s')) AS CHAR) = %s", [
            'be_users.username',
            'be_users.tx_cdsrcbepwreset_resetHash',
            md5($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']),
            $GLOBALS['TYPO3_DB']->fullQuoteStr($hash, 'be_users'),
        ]);
        $users = BackendUtility::getRecordsByField('be_users', 'deleted', 0, $whereClause);
        if (is_array($users) && count($users) === 1) {
            return $users[0];
        }

        return false;
    }

}
