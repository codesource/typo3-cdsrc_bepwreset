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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extension configuration
 *
 * @author Matthias Toscanelli <m.toscanelli@code-source.ch>
 */
class ExtensionConfigurationUtility
{

    /**
     * Extension configuration array
     *
     * @var array
     */
    protected static $extConf;

    /**
     * Store user groups
     *
     * @var array
     */
    protected static $usergroups = array();

    /**
     * Can user reset theyr password from login form
     *
     * @return boolean
     */
    public static function isResetPasswordFromLoginFormEnable()
    {
        self::init();

        return isset(self::$extConf['enablePasswordResetFromLoginForm']) && self::$extConf['enablePasswordResetFromLoginForm'];
    }

    /**
     * Can admin user reset password
     *
     * @return boolean
     */
    public static function isAdminAllowedToResetPassword()
    {
        self::init();

        return isset(self::$extConf['enablePasswordResetForAdmin']) && self::$extConf['enablePasswordResetForAdmin'];
    }

    /**
     * Check if user is in WhiteList Groups
     *
     * @param array $user
     *
     * @return bool
     */
    public static function isUserInWhiteList($user)
    {
        self::init();
        if (is_array($user) && isset(self::$extConf['backendGroupsWhiteList'])) {
            $whiteList = GeneralUtility::trimExplode(',', (string)self::$extConf['backendGroupsWhiteList'], true);

            return self::isUserInList($user, $whiteList);
        }

        return false;
    }

    /**
     * Check if user is in WhiteList Groups
     *
     * @param array $user
     *
     * @return bool
     */
    public static function isUserInBlackList($user)
    {
        self::init();
        if (is_array($user) && isset(self::$extConf['backendGroupsBlackList'])) {
            $blackList = GeneralUtility::trimExplode(',', (string)self::$extConf['backendGroupsBlackList'], true);

            return self::isUserInList($user, $blackList, false);
        }

        return false;
    }

    public static function checkAreBypassedOnResetAtNextLogin()
    {
        self::init();

        return isset(self::$extConf['bypassCheckOnResetAtNextLogin']) && self::$extConf['bypassCheckOnResetAtNextLogin'];
    }

    /**
     * Initialize configuration array
     */
    protected static function init()
    {
        if (empty(self::$extConf)) {
            self::$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cdsrc_bepwreset']);
        }
    }

    /**
     * Check if user is in list of group
     *
     * @param array $user
     * @param array $list
     * @param boolean $acceptEmptyList
     *
     * @return boolean
     */
    protected static function isUserInList($user, $list, $acceptEmptyList = true)
    {
        if (is_array($user) && is_array($list)) {
            if (count($list) === 0) {
                return $acceptEmptyList;
            } elseif ($user['uid'] > 0) {
                if (!isset(self::$usergroups[$user['uid']])) {
                    self::$usergroups[$user['uid']] = array();
                    $usergroups = GeneralUtility::trimExplode(',', (string)$user['usergroup'], true);
                    if (count($usergroups) > 0) {
                        self::setBackendGroups($usergroups, self::$usergroups[$user['uid']]);
                    }
                }

                return count(array_intersect($list, self::$usergroups[$user['uid']])) > 0;
            }
        }

        return false;
    }

    /**
     * Search for all subgroup and store them in finalGroupIds
     *
     * @param array $groupIds
     * @param array $finalGroupIds
     */
    protected static function setBackendGroups($groupIds, &$finalGroupIds)
    {
        if (is_array($groupIds) && count($groupIds) > 0) {
            if (class_exists(ConnectionPool::class)) {
                /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_groups');
                $groups = $queryBuilder->select('uid', 'subgroup')
                                       ->from('be_groups')
                                       ->where($queryBuilder->expr()->in('uid', $groupIds))
                                       ->execute()
                                       ->fetchAll();
            } else {
                $groups = BackendUtility::getRecordsByField(
                    'be_groups',
                    'deleted',
                    0,
                    ' AND uid IN(' . implode(',', $groupIds) . ')'
                );
            }
            $subGroupIds = array();
            if (is_array($groups)) {
                foreach ($groups as $group) {
                    $finalGroupIds[] = $group['uid'];
                    $subGroupIds = array_merge($subGroupIds,
                        GeneralUtility::trimExplode(',', $group['subgroup'], true));
                }
                $subGroupIds = array_diff($subGroupIds, $finalGroupIds);
                self::setBackendGroups($subGroupIds, $finalGroupIds);
            }
        }
    }

}
