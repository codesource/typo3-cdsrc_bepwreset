<?php
/**
 * @copyright Copyright (c) 2016 Code-Source
 */

namespace CDSRC\CdsrcBepwreset\LoginProvider;


use CDSRC\CdsrcBepwreset\Tool\Exception\BeSecurePwException;
use CDSRC\CdsrcBepwreset\Tool\Exception\EmailNotSentException;
use CDSRC\CdsrcBepwreset\Tool\Exception\EmptyPasswordException;
use CDSRC\CdsrcBepwreset\Tool\Exception\InvalidBackendUserException;
use CDSRC\CdsrcBepwreset\Tool\Exception\InvalidPasswordConfirmationException;
use CDSRC\CdsrcBepwreset\Tool\Exception\InvalidResetCodeException;
use CDSRC\CdsrcBepwreset\Tool\Exception\InvalidUserEmailException;
use CDSRC\CdsrcBepwreset\Tool\Exception\InvalidUsernameException;
use CDSRC\CdsrcBepwreset\Tool\Exception\PasswordResetPreventedForAdminException;
use CDSRC\CdsrcBepwreset\Tool\Exception\UserHasNoEmailException;
use CDSRC\CdsrcBepwreset\Tool\Exception\UserInBlackListException;
use CDSRC\CdsrcBepwreset\Tool\Exception\UserNotInWhiteListException;
use CDSRC\CdsrcBepwreset\Tool\ResetTool;
use CDSRC\CdsrcBepwreset\Utility\ExtensionConfigurationUtility;
use CDSRC\CdsrcBepwreset\Utility\HashUtility;
use CDSRC\CdsrcBepwreset\Utility\SessionUtility;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\UsernamePasswordLoginProvider as BaseUsernamePasswordLoginProvider;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException;
use TYPO3\CMS\Fluid\View\StandaloneView;

class UsernamePasswordLoginProvider extends BaseUsernamePasswordLoginProvider
{
    const RESULT_NONE = 0;
    const RESULT_OK = 1;
    const RESULT_ERROR = 2;

    /**
     * @var string
     */
    protected $command = '';

    /**
     * @var array
     */
    protected $parameters = [
        'username' => '',
        'code' => '',
        'result' => self::RESULT_NONE,
        'header' => '',
        'message' => '',
        'previous' => '',
    ];

    /**
     * Initialize call parameter from request or session
     *
     */
    protected function initializeFromRequestOrSession()
    {
        $GLOBALS['LANG']->includeLLFile('EXT:cdsrc_bepwreset/Resources/Private/Language/locallang.xlf');
        $command = (string)GeneralUtility::_GP('commandRS');
        switch ($command) {
            case 'change':
                // Password change link has been clicked
                $user = HashUtility::getUser(GeneralUtility::_GP('hash'));
                if ($user === false) {
                    $user = array('username' => '', 'tx_cdsrcbepwreset_resetHash' => '');
                }
                SessionUtility::setDataAndRedirect($command, $user['username'], $user['tx_cdsrcbepwreset_resetHash']);
                break;
            case 'send':
                // Password reset has been sent for username
                SessionUtility::setDataAndRedirect($command, trim(GeneralUtility::_GP('r_username')));
                break;
            case 'request':
                $this->command = 'request';
                break;
            default:
                $sessionParameters = SessionUtility::getData();
                if (is_array($sessionParameters)) {
                    $this->command = (string)$sessionParameters['command'];
                    $this->parameters = [
                        'username' => (string)$sessionParameters['username'],
                        'code' => (string)$sessionParameters['code'],
                        'result' => (int)($sessionParameters['result']),
                        'header' => (string)$sessionParameters['header'],
                        'message' => (string)$sessionParameters['message'],
                        'previous' => (string)$sessionParameters['previous'],
                    ];
                }
                // Make sure that "Back to login form" work
                if ($this->command === 'reset' && $this->command !== $command) {
                    $this->command = '';
                }
                SessionUtility::reset();
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function render(StandaloneView $view, PageRenderer $pageRenderer, LoginController $loginController)
    {
        parent::render($view, $pageRenderer, $loginController);

        $this->initializeFromRequestOrSession();
        $this->process($view, $pageRenderer);

        $view->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:cdsrc_bepwreset/Resources/Private/Layouts')]);
    }

    /**
     * Process reset command
     *
     * @param \TYPO3\CMS\Fluid\View\StandaloneView $view
     * @param \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer
     */
    protected function process(StandaloneView $view, PageRenderer $pageRenderer)
    {
        /** @var ResetTool $resetTool */
        $resetTool = GeneralUtility::makeInstance(ResetTool::class);
        try {
            switch ($this->command) {
                case 'change':
                case 'force':
                    if (!$resetTool->isCodeValidForUser($this->parameters['username'], $this->parameters['code'])) {
                        SessionUtility::setDataAndRedirect(
                            '', '', '', self::RESULT_ERROR,
                            $GLOBALS['LANG']->getLL('warning.resetPassword'),
                            $GLOBALS['LANG']->getLL('warning.resetPassword.invalidResetCode')
                        );
                    } else {
                        SessionUtility::setData(
                            'reset', $this->parameters['username'], $this->parameters['code'], self::RESULT_NONE,
                            '', '', $this->command
                        );
                    }
                    $pageRenderer->loadRequireJsModule('TYPO3/CMS/CdsrcBepwreset/ResetPassword');
                    $view->assignMultiple([
                        'formType' => 'PasswordChangeForm',
                        'passwordResetHeader' => $this->command === 'force' ? $GLOBALS['LANG']->getLL('labels.changePasswordAtFirstLogin') : '',
                    ]);
                    $this->setBeSecurePwNotice($view);
                    if ($this->parameters['result'] !== self::RESULT_NONE) {
                        $view->assignMultiple([
                            'passwordResetStatus' => $this->parameters['result'] === self::RESULT_OK ? 'ok' : 'error',
                            'passwordResetHeader' => $this->parameters['header'],
                            'passwordResetMessage' => $this->parameters['message'],
                        ]);
                    }
                    break;
                case 'reset':
                    $resetTool->resetPassword(
                        $this->parameters['username'],
                        GeneralUtility::_GP('r_password'),
                        GeneralUtility::_GP('r_password_confirmation'),
                        $this->parameters['code']
                    );
                    SessionUtility::setDataAndRedirect(
                        '', '', '', self::RESULT_OK,
                        $GLOBALS['LANG']->getLL('ok.resetPassword'),
                        $GLOBALS['LANG']->getLL('ok.resetPasswordMessage')
                    );
                    break;
                case 'send':
                    if (ExtensionConfigurationUtility::isResetPasswordFromLoginFormEnable()) {
                        $resetTool->sendResetCode($this->parameters['username']);
                        SessionUtility::setDataAndRedirect(
                            '', '', '', self::RESULT_OK,
                            $GLOBALS['LANG']->getLL('ok.sendCode'),
                            $GLOBALS['LANG']->getLL('ok.sendCodeMessage')
                        );
                        break;
                    }
                case 'request':
                    if (ExtensionConfigurationUtility::isResetPasswordFromLoginFormEnable()) {
                        $view->assign('formType', 'PasswordRequestForm');
                    }
                default:
                    $view->assign('passwordCanBeReset',
                        ExtensionConfigurationUtility::isResetPasswordFromLoginFormEnable());
                    if ($this->parameters['result'] !== self::RESULT_NONE) {
                        $view->assignMultiple([
                            'passwordResetStatus' => $this->parameters['result'] === self::RESULT_OK ? 'ok' : 'error',
                            'passwordResetHeader' => $this->parameters['header'],
                            'passwordResetMessage' => $this->parameters['message'],
                        ]);
                    }
                    break;
            }
        } catch (\Exception $e) {
            if ($this->command === $this->parameters['previous'] || $this->command === 'change') {
                $command = '';
            } elseif ($this->command === 'reset') {
                $command = 'change';
            } elseif ($this->command === 'send') {
                $command = 'request';
            } else {
                $command = $this->command;
            }

            // Make call slower to prevent multiple fast call
            sleep(5);

            SessionUtility::setDataAndRedirect(
                $command,
                $this->parameters['username'],
                $this->parameters['code'],
                self::RESULT_ERROR,
                $GLOBALS['LANG']->getLL('warning.resetPassword'),
                $this->catchResetToolException($e),
                $this->command
            );
        }
    }

    /**
     * Catch exception from ResetTool and return a message
     *
     * @param \Exception $e
     *
     * @return string
     *
     */
    protected function catchResetToolException(\Exception $e)
    {
        if ($e instanceof InvalidUsernameException) {
            return $GLOBALS['LANG']->getLL('warning.resetPassword.userDoNotExists');
        } elseif ($e instanceof InvalidBackendUserException) {
            return $GLOBALS['LANG']->getLL('warning.resetPassword.userDoNotExists');
        } elseif ($e instanceof PasswordResetPreventedForAdminException) {
            return $GLOBALS['LANG']->getLL('warning.resetPassword.passwordResetPreventedForAdmin');
        } elseif ($e instanceof UserInBlackListException) {
            return $GLOBALS['LANG']->getLL('warning.resetPassword.userIsInBlackList');
        } elseif ($e instanceof UserNotInWhiteListException) {
            return $GLOBALS['LANG']->getLL('warning.resetPassword.userIsNotInBlackList');
        } elseif ($e instanceof InvalidUserEmailException) {
            return $GLOBALS['LANG']->getLL('warning.resetPassword.invalidUserEmail');
        } elseif ($e instanceof UserHasNoEmailException) {
            return $GLOBALS['LANG']->getLL('warning.resetPassword.userHasNoEmail');
        } elseif ($e instanceof InvalidResetCodeException) {
            return $GLOBALS['LANG']->getLL('warning.resetPassword.invalidResetCode');
        } elseif ($e instanceof InvalidPasswordConfirmationException) {
            return $GLOBALS['LANG']->getLL('warning.resetPassword.invalidPasswordConfirmation');
        } elseif ($e instanceof EmptyPasswordException) {
            return $GLOBALS['LANG']->getLL('warning.resetPassword.emptyPassword');
        } elseif ($e instanceof BeSecurePwException) {
            return $GLOBALS['LANG']->getLL('warning.resetPassword.beSecurePw');
        } elseif ($e instanceof EmailNotSentException) {
            return $GLOBALS['LANG']->getLL('warning.resetPassword.emailNotSent');
        } elseif ($e instanceof InvalidTemplateResourceException) {
            return $e->getMessage();
        } else {
            return $GLOBALS['LANG']->getLL('warning.resetPassword.unknown');
        }
    }

    /**
     * Assign variables to view for be_secure_pw extension notice
     *
     * @param \TYPO3\CMS\Fluid\View\StandaloneView $view
     */
    protected function setBeSecurePwNotice(StandaloneView $view)
    {
        if (ExtensionManagementUtility::isLoaded('be_secure_pw')) {
            // get configuration of a secure password
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['be_secure_pw']);

            // how many parameters have to be checked
            $toCheckParams = ['lowercaseChar', 'capitalChar', 'digit', 'specialChar'];
            $checkParameter = array();
            foreach ($toCheckParams as $parameter) {
                if ((bool)$extConf[$parameter]) {
                    $checkParameter[] = $GLOBALS['LANG']->sL('LLL:EXT:be_secure_pw/Resources/Private/Language/locallang.xml:' . $parameter);
                }
            }
            $view->assignMultiple([
                'BeSecurePwEnable' => true,
                'BeSecurePwLength' => (int)$extConf['passwordLength'],
                'BeSecurePwChecks' => implode(', ', $checkParameter),
                'BeSecurePwPatterns' => (int)$extConf['patterns'],
            ]);
        }
    }
}
