.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _configuration-manual:

Configuration manual
============

cdsrc_bepwreset offers some basic inside the Extension Manager. 
Those are described in this chapter. To be able to set this configuration, 
switch to the Extension Manager and search for the extension "cdsrc_bepwreset". 
Click on it to see the available settings.


Properties
^^^^^^^^^^

.. container:: ts-properties

	===================================================== ================================================ ===================
	Property                                              Data type                                        Default
	===================================================== ================================================ ===================
	enablePasswordResetFromLoginForm_                       :ref:`t3tsref:data-type-boolean`                 TRUE
	enablePasswordResetForAdmin_                            :ref:`t3tsref:data-type-boolean`                 FALSE
	backendGroupsWhiteList_                                 :ref:`t3tsref:data-type-list`                  
	backendGroupsBlackList_                                 :ref:`t3tsref:data-type-list`
	bypassCheckOnResetAtNextLogin_                          :ref:`t3tsref:data-type-boolean`                 FALSE
	===================================================== ================================================ ===================


Property details
^^^^^^^^^^^^^^^^ 

.. _settings-enablePasswordResetFromLoginForm:

enablePasswordResetFromLoginForm_
""""""""""""""""""""""""""""""""

If set, backend users will be able to ask for a password reset in login form.



.. _settings-enablePasswordResetForAdmin:

enablePasswordResetForAdmin_
""""""""""""""""""""""""""""""""

If set, backend administrator will be able to ask for a password reset.

.. warning::

For security reason, make sure to uncheck this option if not needed.



.. _settings-backendGroupsWhiteList:

backendGroupsWhiteList_
""""""""""""""""""""""""""""""""

You can restrict password reset by setting a comma separated uid list 
of authorised backend group.



.. _settings-backendGroupsBlackList:

backendGroupsBlackList_
""""""""""""""""""""""""""""""""

You can restrict password reset by setting a comma separated uid list of 
unauthorised backend group.

.. warning::

**This list overrules white list**, a backend group present 
in both list will be unauthorised.



.. _settings-bypassCheckOnResetAtNextLogin:

bypassCheckOnResetAtNextLogin_
""""""""""""""""""""""""""""""""

If set, all user will be able to reset his password in case he is forced to 
change his password at next login. This overrules administrator's restriction, 
white list and black list.
