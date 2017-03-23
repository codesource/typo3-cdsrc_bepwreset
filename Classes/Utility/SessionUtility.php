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

use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * Manage session
 *
 * @author Matthias Toscanelli <m.toscanelli@code-source.ch>
 */
class SessionUtility
{

    /**
     * Session key
     *
     * @var string
     */
    protected static $sessionKey = 'cdsrc_bepwreset';

    /**
     * Retrieve call datas from session
     *
     * @return array
     */
    public static function getData()
    {
        self::init();
        $data = $_SESSION[self::$sessionKey];
        if (!is_array($data)) {
            $datas = array();
        }

        return $data;
    }

    /**
     * Store call datas to session
     *
     * @param string $command
     * @param string $username
     * @param string $code
     * @param integer $result
     * @param string $header
     * @param string $message
     * @param string $previous
     */
    public static function setData(
        $command,
        $username = '',
        $code = '',
        $result = 0,
        $header = '',
        $message = '',
        $previous = ''
    ) {
        self::init();
        $_SESSION[self::$sessionKey] = array(
            'command' => (string)$command,
            'username' => (string)$username,
            'code' => (string)$code,
            'result' => $result,
            'header' => (string)$header,
            'message' => (string)$message,
            'previous' => (string)$previous,
        );
    }

    /**
     * Store call datas to session and redirect to login form
     *
     * @param string $command
     * @param string $username
     * @param string $code
     * @param integer $result
     * @param string $header
     * @param string $message
     * @param string $previous
     */
    public static function setDataAndRedirect(
        $command,
        $username = '',
        $code = '',
        $result = 0,
        $header = '',
        $message = '',
        $previous = ''
    ) {
        self::init(true);
        self::setData($command, $username, $code, $result, $header, $message, $previous);
        HttpUtility::redirect('index.php');
        exit;
    }

    /**
     * Reset session datas
     */
    public static function reset()
    {
        self::init();
        if (isset($_SESSION[self::$sessionKey])) {
            unset($_SESSION[self::$sessionKey]);
        }
    }

    /**
     * Initialize session if needed
     */
    protected static function init($renew = false)
    {
//        var_dump(session_id());exit;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if($renew){
            session_regenerate_id();
        }
    }
}
