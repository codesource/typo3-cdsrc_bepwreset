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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Log message tool
 *
 * @author Matthias Toscanelli <m.toscanelli@code-source.ch>
 */
class LogUtility {

    /**
     * Extention key
     * 
     * @var string
     */
    protected static $extKey = 'cdsrc_bepwreset';

    /**
     * Writes log message to the system log. 
     * If developer log is enabled, messages are also sent there.
     *
     * This function accepts variable number of arguments and can format
     * parameters. The syntax is the same as for sprintf()
     *
     * @param string $message Message to output
     * @param integer $userId Backend user UID
     * @return void
     * @see GeneralUtility::sysLog()
     */
    public static function writeLog($message, $userId = 0) {
        if (func_num_args() > 2) {
            $params = func_get_args();
            array_shift($params);
            array_shift($params);
            $message = vsprintf($message, $params);
        }
        if (TYPO3_MODE === 'BE') {
            GeneralUtility::sysLog($message, self::$extKey, GeneralUtility::SYSLOG_SEVERITY_NOTICE);
        } else {
            $GLOBALS['TT']->setTSlogMessage($message);
        }
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_DLOG']) {
            GeneralUtility::devLog($message, self::$extKey, GeneralUtility::SYSLOG_SEVERITY_NOTICE);
        }
        self::writeToSysLog($message, $userId);
    }

    /**
     * Write to database sys_log
     * 
     * @param string $message
     * @param integer $userId
     * @param integer $type
     * @param integer $action
     * @param integer $error
     * 
     * @return integer
     */
    protected static function writeToSysLog($message, $userId = 0, $type = 255, $action = 0, $error = 0) {
        $fields_values = array(
            'userid' => (int) $userId,
            'type' => (int) $type,
            'action' => (int) $action,
            'error' => (int) $error,
            'details_nr' => 1,
            'details' => $message,
            'log_data' => serialize(array()),
            'tablename' => '',
            'IP' => (string) GeneralUtility::getIndpEnv('REMOTE_ADDR'),
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'event_pid' => -1,
            'workspace' => '-99'
        );
        $GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_log', $fields_values);
        return $GLOBALS['TYPO3_DB']->sql_insert_id();
    }

}
