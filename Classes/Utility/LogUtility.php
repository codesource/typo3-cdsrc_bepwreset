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
 */

use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Log message tool
 *
 * @author Matthias Toscanelli <m.toscanelli@code-source.ch>
 */
class LogUtility
{

    /**
     * Type used by belog to display LOGIN in list
     */
    const TYPE_CODE = 255;

    /**
     * Action used by belog to display in list
     */
    const ACTION_CODE = 55;

    /**
     * Extension key
     *
     * @var string
     */
    protected static $extKey = 'cdsrc_bepwreset';

    /**
     * @var Logger
     */
    protected static $logger;

    /**
     * Writes log message to the system log.
     * If developer log is enabled, messages are also sent there.
     *
     * This function accepts variable number of arguments and can format
     * parameters. The syntax is the same as for sprintf()
     *
     * @param string $message Message to output
     * @param integer $userId Backend user UID
     *
     * @return void
     * @see GeneralUtility::sysLog()
     */
    public static function writeLog($message, $userId = 0)
    {
        $message = call_user_func_array(self::class . '::extractMessage', func_get_args());
        if (TYPO3_MODE === 'BE') {
            self::getLogger()->notice($message,['extension' => self::$extKey]);
        } else {
            $GLOBALS['TT']->setTSlogMessage($message);
        }
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_DLOG']) {
            self::getLogger()->debug($message,['extension' => self::$extKey]);
        }
        self::writeToSysLog($message, $userId, 0);
    }

    /**
     * @param string $message
     * @param int $userId
     */
    public static function writeError($message, $userId = 0)
    {
        $message = call_user_func_array(self::class . '::extractMessage', func_get_args());
        self::writeToSysLog($message, $userId, 2);
    }

    /**
     * @return string
     */
    protected static function extractMessage()
    {
        $params = func_get_args();
        $message = array_shift($params);
        if (count($params) > 1) {
            array_shift($params);

            return vsprintf($message, $params);
        }

        return $message;
    }

    /**
     * Write to database sys_log
     *
     * @param string $message
     * @param integer $userId
     * @param integer $error
     *
     * @return integer
     */
    protected static function writeToSysLog($message, $userId = 0, $error = 0)
    {
        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var Connection $connection */
        $connection = $objectManager->get(ConnectionPool::class)->getConnectionForTable('sys_log');
        $connection->insert('sys_log', [
            'userid' => (int)$userId,
            'type' => self::TYPE_CODE,
            'action' => self::ACTION_CODE,
            'error' => (int)$error,
            'details_nr' => 1,
            'details' => $message,
            'log_data' => serialize(array()),
            'tablename' => '',
            'IP' => (string)GeneralUtility::getIndpEnv('REMOTE_ADDR'),
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'event_pid' => -1,
            'workspace' => '-99',
        ]);

        return $connection->lastInsertId('sys_log');
    }


    /**
     * @return Logger
     */
    protected static function getLogger()
    {
        if (self::$logger === null) {
            self::$logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        }

        return self::$logger;
    }
}
