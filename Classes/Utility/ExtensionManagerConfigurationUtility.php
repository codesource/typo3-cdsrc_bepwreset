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
 * class providing configuration checks for cdsrc_bepwreset.
 *
 * @author Matthias Toscanelli <m.toscanelli@code-source.ch>
 */
class ExtensionManagerConfigurationUtility
{

    /**
     * Store javascript insertion status
     *
     * @var boolean
     */
    protected static $javascriptFunctionInserted = false;

    /**
     * Store javascript id index
     *
     * @var integer
     */
    protected static $javascriptIdIndex = 0;

    /**
     * @var array
     */
    protected $extConf = array();

    /**
     * Render a multiple select for backend groups
     *
     * @param array $params
     *
     * @return string
     */
    public function renderBackendGroupSelect($params)
    {
        $selectedGroups = $params['fieldValue'];
        if (!is_array($selectedGroups)) {
            $selectedGroups = explode(',', $selectedGroups);
        }

        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_groups');
        $groups = $queryBuilder->select('uid', 'title')
                               ->from('be_groups')
                               ->orderBy('title')
                               ->execute()
                               ->fetchAll();

        $id = 'cdsrc_bepwreset_' . self::$javascriptIdIndex;
        self::$javascriptIdIndex++;
        $content = '<select id="' . $id . '" name="none[' . $id . '][]" multiple="multiple" size="5" style="min-width:200px;">';
        if (is_array($groups)) {
            foreach ($groups as $group) {
                $content .= '<option value="' . $group['uid'] . '" ' . (in_array($group['uid'],
                        $selectedGroups) ? 'selected="selected"' : '') . '>' . $group['title'] . '</option>';
            }
        }
        $content .= '</select>';
        $content .= '<input id="' . $id . '_input" type="hidden" value="' . implode(',',
                array_unique($selectedGroups)) . '" name="' . $params['fieldName'] . '" />';
        $content .= $this->loadJavascriptFunction();
        $content .= '<script>
            CDSRC_CdsrcBepwreset.initializeSelect(\'' . $id . '\');
        </script>';

        return $content;
    }

    /**
     * Load javascript function once
     *
     * @return string
     */
    protected function loadJavascriptFunction()
    {
        if (!self::$javascriptFunctionInserted) {
            self::$javascriptFunctionInserted = true;

            return '<script>
                CDSRC_CdsrcBepwreset = {
                    initializeSelect: function(id){
                        var i,
                            values,
                            select = document.getElementById(id),
                            input = document.getElementById(id + \'_input\');
                        if(select && input){
                            values = (input.value || \'\').split(\',\');
                            if(select.options){
                                for(i=0; i<select.options.length; i++){
                                    if(values.indexOf(select.options[i].value) >= 0){
                                        select.options[i].selected = true;
                                    }
                                }
                            }
                            select.addEventListener(\'change\', function(){
                                values = [];
                                for(i=0; i<select.options.length; i++){
                                    if(select.options[i].selected){
                                        values.push(select.options[i].value);
                                    }
                                }
                                input.value = values.join(\',\');
                            });
                        }
                    }
                };
            </script>';
        }

        return '';
    }

    /**
     * Processes the information submitted by the user using a POST request and
     * transforms it to a TypoScript node notation.
     *
     * @param array $postArray Incoming POST information
     *
     * @return array Processed and transformed POST information
     */
    private function processPostData(array $postArray = array())
    {
        foreach ($postArray as $key => $value) {
            $parts = explode('.', $key, 2);
            if (count($parts) == 2) {
                $value = $this->processPostData(array($parts[1] => $value));
                $postArray[$parts[0] . '.'] = array_merge((array)$postArray[($parts[0] . '.')], $value);
            } else {
                $postArray[$parts[0]] = $value;
            }
        }

        return $postArray;
    }

}
