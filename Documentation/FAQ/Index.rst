.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _faq:

FAQ
====================

What happens when a backend user asks for a password reset?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* System will check if username exists and if this user has a valid email address.
* System will send an email with a reset link.
* Once user clicks on this link, he will be able to reset his password.

.. warning:

The hash key has a validity of 1 hour.


What are the security in place?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* The visible hash key is a SHA1 hash based on TYPO3 encryptionKey.
* Stored hash key and username are never visible to user. 
  (If someone intercepts the email, he will not know the username.)
* The hash key has a validity of 1 hour.
* The hash key allows a one time password change.


Why the user does not receive email reset?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* The user does not have valid email address.
* TYPO3 is not configured for sending emails.
  (Go to installation's tool and check email options.)