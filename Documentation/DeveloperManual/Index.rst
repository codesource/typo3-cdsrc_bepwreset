.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _developer-manual:

Developer Manual
====================


Add a hook before password reset mail rendering
-----------------------------------

You can add a hook before the password reset email is render. This allow to add more variables or change partial
template path.

**Example:**

In ext_localconf.php:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cdsrc_bepwreset']['CDSRC\CdsrcBepwreset\Tool\ResetTool']['preRenderMail'][] =
    \CustomTemplate\TemplateDefault\Hooks\ResetToolHook::class . '->preMailRendering';

In template_default/Classes/Hooks/ResetToolHook:

.. code-block:: php

    <?php
    namespace CustomTemplate\TemplateDefault\Hooks;

    class ResetToolHook
    {
        /**
         * @param array $params
         * @param \CDSRC\CdsrcBepwreset\Tool\ResetTool $resetTool
         */
        public function preMailRendering(array $params, \CDSRC\CdsrcBepwreset\Tool\ResetTool $resetTool){
            /** @var \CDSRC\CdsrcBepwreset\View\MailStandaloneView $view */
            $view = $params['view'];
            $view->setPartialRootPaths(['EXT:template_default/Resources/Private/Partials/ResetTool/']);
            $params['variables']['additionalInformation'] = 'some data';
        }
    }


Translate emails
----------------

Once you have redefined the mail template, you can translate it by using Fluid condition based on user language.

.. code-block:: html

    <f:switch expression="{user.lang}">
       <f:case value="de">My text in german</f:case>
       <f:case value="fr">My text in french</f:case>
       <f:defaultCase>My default text</f:defaultCase>
    </f:switch>