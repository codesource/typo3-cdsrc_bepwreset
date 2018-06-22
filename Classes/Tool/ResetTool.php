<?php

namespace CDSRC\CdsrcBepwreset\Tool;

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
use CDSRC\CdsrcBepwreset\Tool\Exception\BackendUserNotInitializedException;
use CDSRC\CdsrcBepwreset\Tool\Exception\BeSecurePwException;
use CDSRC\CdsrcBepwreset\Tool\Exception\EmailNotSentException;
use CDSRC\CdsrcBepwreset\Tool\Exception\EmptyPasswordException;
use CDSRC\CdsrcBepwreset\Tool\Exception\InvalidBackendUserException;
use CDSRC\CdsrcBepwreset\Tool\Exception\InvalidPasswordConfirmationException;
use CDSRC\CdsrcBepwreset\Tool\Exception\InvalidResetCodeException;
use CDSRC\CdsrcBepwreset\Tool\Exception\InvalidUserEmailException;
use CDSRC\CdsrcBepwreset\Tool\Exception\InvalidUsernameException;
use CDSRC\CdsrcBepwreset\Tool\Exception\PasswordResetPreventedForAdminException;
use CDSRC\CdsrcBepwreset\Tool\Exception\ResetCodeNotUpdatedException;
use CDSRC\CdsrcBepwreset\Tool\Exception\UserHasNoEmailException;
use CDSRC\CdsrcBepwreset\Tool\Exception\UserInBlackListException;
use CDSRC\CdsrcBepwreset\Tool\Exception\UserNotInWhiteListException;
use CDSRC\CdsrcBepwreset\Utility\ExtensionConfigurationUtility;
use CDSRC\CdsrcBepwreset\Utility\HashUtility;
use CDSRC\CdsrcBepwreset\Utility\LogUtility;
use CDSRC\CdsrcBepwreset\View\MailStandaloneView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 *
 *
 * @author Matthias Toscanelli <m.toscanelli@code-source.ch>
 */
class ResetTool
{

    /**
     * Extention key
     *
     * @var string
     */
    protected $extKey = 'cdsrc_bepwreset';

    /**
     * Backend user datas
     *
     * @var array
     */
    protected $user;

    /**
     * Send a new reset code to user by email
     *
     * @param string $username
     *
     * @throws EmailNotSentException
     * @throws InvalidBackendUserException
     * @throws InvalidUserEmailException
     * @throws InvalidUsernameException
     * @throws PasswordResetPreventedForAdminException
     * @throws ResetCodeNotUpdatedException
     * @throws UserHasNoEmailException
     * @throws UserInBlackListException
     * @throws UserNotInWhiteListException
     */
    public function sendResetCode($username)
    {
        // This call disable bypassedOnResetAtNextLogin
        $this->initUser($username, false, true);
        $GLOBALS['LANG']->includeLLFile('EXT:cdsrc_bepwreset/Resources/Private/Language/locallang.xlf');

        if (($fields = $this->updateResetCode()) === false) {
            throw new ResetCodeNotUpdatedException('Enable to append reset code to user.', 1424785971);
        }

        /** @var MailStandaloneView $view */
        $view = GeneralUtility::makeInstance(MailStandaloneView::class);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:cdsrc_bepwreset/Resources/Private/Partials')]);

        $hash = HashUtility::getHash($this->user['username'], $fields['tx_cdsrcbepwreset_resetHash']);
        $variables = [
            'user' => $this->user,
            'validity' => (new \DateTime())->setTimestamp($fields['tx_cdsrcbepwreset_resetHashValidity']),
            'hash' => $hash,
            'url' => sprintf(
                '%s/typo3/index.php?commandRS=change&hash=%s',
                GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'),
                $hash
            ),
            'siteName' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
        ];

        if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cdsrc_bepwreset']['CDSRC\CdsrcBepwreset\Tool\ResetTool']['preRenderMail'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cdsrc_bepwreset']['CDSRC\CdsrcBepwreset\Tool\ResetTool']['preRenderMail'] as $reference) {
                $hookParameters = [
                    'variables' => &$variables,
                    'view' => &$view,
                ];
                GeneralUtility::callUserFunction($reference, $hookParameters, $this);
            }
        }

        $subject = trim($view->renderPartial('MailRequest.html', 'Subject', $variables));
        $bodyHtml = $view->renderPartial('MailRequest.html', 'Html', $variables);
        $bodyPlain = $view->renderPartial('MailRequest.html', 'Plain', $variables);
        $from = \TYPO3\CMS\Core\Utility\MailUtility::getSystemFrom();

        /** @var $mail \TYPO3\CMS\Core\Mail\MailMessage */
        $mail = GeneralUtility::makeInstance(MailMessage::class);
        $mail
            ->setTo($this->user['email'])
            ->setFrom($from)
            ->setSubject($subject)
            ->setBody($bodyPlain, 'text/plain')
            ->addPart($bodyHtml, 'text/html');
        $mail->send();
        if (!$mail->isSent()) {
            throw new EmailNotSentException('Email not sent.', 1424721934);
        }
    }

    /**
     * Set a new reset code to user and return datas
     *
     * @param string $username
     *
     * @return mixed Array of updated fields or FALSE if error happens
     *
     * @throws InvalidBackendUserException
     * @throws InvalidUsernameException
     * @throws InvalidUserEmailException
     * @throws ResetCodeNotUpdatedException
     * @throws PasswordResetPreventedForAdminException
     * @throws UserHasNoEmailException
     * @throws UserInBlackListException
     * @throws UserNotInWhiteListException
     */
    public function updateResetCodeForUser($username)
    {
        $this->initUser($username, ExtensionConfigurationUtility::checkAreBypassedOnResetAtNextLogin(), false);
        if (($fields = $this->updateResetCode()) === false) {
            throw new ResetCodeNotUpdatedException('Enable to append reset code to user.', 1424785971);
        }

        return $fields;
    }

    /**
     * Reset password for backend user
     *
     * @param string $username
     * @param string $password
     * @param string $passwordConfirmation
     * @param string $code
     *
     * @throws BackendUserNotInitializedException
     * @throws BeSecurePwException
     * @throws EmptyPasswordException
     * @throws InvalidBackendUserException
     * @throws InvalidPasswordConfirmationException
     * @throws InvalidResetCodeException
     * @throws InvalidUsernameException
     * @throws InvalidUserEmailException
     * @throws PasswordResetPreventedForAdminException
     * @throws UserHasNoEmailException
     * @throws UserInBlackListException
     * @throws UserNotInWhiteListException
     */
    public function resetPassword($username, $password, $passwordConfirmation, $code)
    {
        $trimedPassword = trim($password);
        $this->initUser($username, ExtensionConfigurationUtility::checkAreBypassedOnResetAtNextLogin(), false);

        if (!$this->isValidResetCode($code)) {
            throw new InvalidResetCodeException('"' . $code . '" is not valid.', 1424710407);
        }
        if (strlen($trimedPassword) === 0) {
            throw new EmptyPasswordException('Password is empty.', 1424718754);
        }
        if ($trimedPassword !== trim($passwordConfirmation)) {
            throw new InvalidPasswordConfirmationException('Confirmation password is not valid.', 1424718822);
        }

        if (ExtensionManagementUtility::isLoaded('be_secure_pw')) {
            $set = true;
            $is_in = '';
            $eval = GeneralUtility::makeInstance('SpoonerWeb\BeSecurePw\Evaluation\PasswordEvaluator');
            $check = $eval->evaluateFieldValue($trimedPassword, $is_in, $set);
            if (strlen($check) === 0) {
                throw new BeSecurePwException('Password is not enough strong.', 1424736449);
            }
        }
        if (!is_object($GLOBALS['BE_USER'])) {
            throw new BackendUserNotInitializedException('Backend user object is not initialized.', 1424720202);
        }
        $storeRec = array(
            'be_users' => array(
                $this->user['uid'] => array(
                    'password' => $trimedPassword,
                    'tx_cdsrcbepwreset_resetHash' => '',
                    'tx_cdsrcbepwreset_resetHashValidity' => 0,
                    'tx_cdsrcbepwreset_resetAtNextLogin' => 0,
                ),
            ),
        );
        // Make instance of TCE for storing the changes.
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $tce->stripslashes_values = 0;
        // This is so the user can actually update his user record.
        $GLOBALS['BE_USER']->user['admin'] = 1;
        $tce->start($storeRec, array(), $GLOBALS['BE_USER']);
        // Desactivate history
        $tce->checkSimilar = false;
        // This is to make sure that the users record can be updated even if in another workspace. This is tolerated.
        $tce->bypassWorkspaceRestrictions = true;
        $tce->process_datamap();
        unset($tce);
        LogUtility::writeLog(
            'Password has been reset for "%s (%s)" from %s',
            $this->user['uid'],
            $this->user['username'], $this->user['uid'],
            (string)GeneralUtility::getIndpEnv('REMOTE_ADDR')
        );
    }

    /**
     * Static function to check if code is valid for an user
     *
     * @param string $username
     * @param string $code
     *
     * @return boolean
     *
     * @throws InvalidBackendUserException
     * @throws InvalidUsernameException
     * @throws InvalidUserEmailException
     * @throws PasswordResetPreventedForAdminException
     * @throws UserHasNoEmailException
     * @throws UserInBlackListException
     * @throws UserNotInWhiteListException
     */
    public function isCodeValidForUser($username, $code)
    {
        $this->initUser($username, ExtensionConfigurationUtility::checkAreBypassedOnResetAtNextLogin(), false);

        return $this->isValidResetCode($code);
    }

    /**
     * Add a new reset code to current user
     *
     * @return mixed Array of updated fields or FALSE if error happens
     */
    protected function updateResetCode()
    {
        if (!empty($this->user)) {
            /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
            $updateQuery = $queryBuilder->update('be_users')
                                ->where($queryBuilder->expr()->eq('uid',$queryBuilder->createNamedParameter(intval($this->user['uid'],\PDO::PARAM_INT))))
                                ->set('tstamp',$queryBuilder->createNamedParameter($EXEC_TIME,\PDO::PARAM_INT))
                                ->set('tx_cdsrcbepwreset_resetHash',$queryBuilder->createNamedParameter(md5((String)$EXEC_TIME . '-' . mt_rand(1000, 100000),\PDO::PARAM_STR)))
                                ->set('tx_cdsrcbepwreset_resetHashValidity',$queryBuilder->createNamedParameter(($EXEC_TIME + 3600),\PDO:PARAM_INT));
            if ($updateQuery->execute()) {
                return $fields;
            }
        }

        return false;
    }

    /**
     * Initialize User
     *
     * @param string $username
     * @param bool $bypassCheckOnResetAtNextLogin
     * @param bool $emailRequired
     *
     * @throws InvalidBackendUserException
     * @throws InvalidUsernameException
     * @throws InvalidUserEmailException
     * @throws PasswordResetPreventedForAdminException
     * @throws UserHasNoEmailException
     * @throws UserInBlackListException
     * @throws UserNotInWhiteListException
     */
    protected function initUser($username, $bypassCheckOnResetAtNextLogin = true, $emailRequired = true)
    {
        $username = trim($username);
        if (strlen($username) === 0) {
            throw new InvalidUsernameException('Username is empty.', 1424708826);
        }
        if (class_exists(ConnectionPool::class)) {
            /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
            $users = $queryBuilder->select('*')
                                  ->from('be_users')
                                  ->where($queryBuilder->expr()->eq(
                                      'username',
                                      $queryBuilder->createNamedParameter($username, \PDO::PARAM_STR)
                                  ))
                                  ->execute()
                                  ->fetchAll();
        } else {
            $users = BackendUtility::getRecordsByField('be_users', 'username', $username);
        }
        $count = count($users);
        if ($count === 0) {
            throw new InvalidBackendUserException('User do not exists.', 1424709938);
        }
        if ($count > 1) {
            throw new InvalidBackendUserException('Multiple record found.', 1424709961);
        }

        $this->user = $users[0];

        // Administrator, white list and black list are not checked if user require a password reset at next login
        if (intval($this->user['tx_cdsrcbepwreset_resetAtNextLogin']) === 0 || !$bypassCheckOnResetAtNextLogin) {
            if ($this->user['admin'] && !ExtensionConfigurationUtility::isAdminAllowedToResetPassword()) {
                throw new PasswordResetPreventedForAdminException('Admin is not allowed to reset password.',
                    1424814441);
            }

            if (!ExtensionConfigurationUtility::isUserInWhiteList($this->user)) {
                throw new UserNotInWhiteListException('White list is configured and user is not in.', 1424825158);
            }

            if (ExtensionConfigurationUtility::isUserInBlackList($this->user)) {
                throw new UserInBlackListException('Black list is configured and user is in.', 1424825189);
            }
        }

        if ($emailRequired) {
            if (strlen(trim($this->user['email'])) === 0) {
                throw new UserHasNoEmailException('"' . $this->user['username'] . '" has no email defined.',
                    1424708950);
            } elseif (!GeneralUtility::validEmail($this->user['email'])) {
                throw new InvalidUserEmailException('"' . $this->user['username'] . '" has no valid email address.',
                    1424710072);
            }
        }
    }

    /**
     * Check if given code is valid for backend user
     *
     * @param string $code
     *
     * @return boolean
     */
    protected function isValidResetCode($code)
    {
        if (!empty($this->user)) {
            return strlen($this->user['tx_cdsrcbepwreset_resetHash']) > 0 &&
                   $this->user['tx_cdsrcbepwreset_resetHash'] === $code &&
                   $this->user['tx_cdsrcbepwreset_resetHashValidity'] >= $GLOBALS['EXEC_TIME'];
        }

        return false;
    }

}
