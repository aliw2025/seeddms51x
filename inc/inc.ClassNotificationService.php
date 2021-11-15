<?php
/**
 * Implementation of notification service
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2016 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Implementation of notification service
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2016 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_NotificationService {
	/**
	 * List of services for sending notification
	 */
	protected $services;

	/*
	 * List of servives with errors
	 */
	protected $errors;

	/*
	 * Service for logging
	 */
	protected $logger;

	/*
	 * Configuration
	 */
	protected $settings;

	/*
	 * Possible types of receivers
	 */
	const RECV_ANY = 0;
	const RECV_NOTIFICATION = 1;
	const RECV_OWNER = 2;
	const RECV_REVIEWER = 3;
	const RECV_APPROVER = 4;
	const RECV_WORKFLOW = 5;

	public function __construct($logger = null, $settings = null) { /* {{{ */
		$this->services = array();
		$this->errors = array();
		$this->logger = $logger;
		$this->settings = $settings;
	} /* }}} */

	public function addService($service, $name='') { /* {{{ */
		if(!$name)
			$name = md5(uniqid());
		$this->services[$name] = $service;
		$this->errors[$name] = true;
	} /* }}} */

	public function getServices() { /* {{{ */
		return $this->services;
	} /* }}} */

	public function getErrors() { /* {{{ */
		return $this->errors;
	} /* }}} */

	public function toIndividual($sender, $recipient, $subject, $message, $params=array(), $recvtype=0) { /* {{{ */
		$error = true;
		foreach($this->services as $name => $service) {
			/* Set $to to email address of user or the string passed in $recipient
			 * This is only used for logging
			 */
			if(is_object($recipient) && $recipient->isType('user') && !$recipient->isDisabled() && $recipient->getEmail()!="") {
				$to = $recipient->getEmail();
			} elseif(is_string($recipient) && trim($recipient) != "") {
				$to = $recipient;
			} else {
				$to = '';
			}

			/* Call filter of notification service if set */
			if(!is_callable([$service, 'filter']) || $service->filter($sender, $recipient, $subject, $message, $params, $recvtype)) {
				if(!$service->toIndividual($sender, $recipient, $subject, $message, $params)) {
					$error = false;
					$this->errors[$name] = false;
					$this->logger->log('Notification service \''.$name.'\': Sending notification \''.$subject.'\' to user \''.$to.'\' ('.$recvtype.') failed.', PEAR_LOG_ERR);
				} else {
					$this->logger->log('Notification service \''.$name.'\': Sending notification \''.$subject.'\' to user \''.$to.'\' ('.$recvtype.') successful.', PEAR_LOG_INFO);
					$this->errors[$name] = true;
				}
			} else {
				$this->logger->log('Notification service \''.$name.'\': Notification \''.$subject.'\' to user \''.$to.'\' ('.$recvtype.') filtered out.', PEAR_LOG_INFO);
			}
		}
		return $error;
	} /* }}} */

	/**
	 * Send a notification to each user of a group
	 *
	 */
	public function toGroup($sender, $groupRecipient, $subject, $message, $params=array(), $recvtype=0) { /* {{{ */
		$error = true;
		$ret = true;
		foreach ($groupRecipient->getUsers() as $recipient) {
			$ret &= $this->toIndividual($sender, $recipient, $subject, $message, $params, $recvtype);
		}
		if(!$ret) {
			$error = false;
		}
		return $error;
	} /* }}} */

	/**
	 * Send a notification to a list of recipients
	 *
	 * The list of recipients may contain both email addresses and users
	 *
	 * @param string|object $sender either an email address or a user
	 * @param array $recipients list of recipients
	 * @param string $subject key of translatable phrase for the subject
	 * @param string $message key of translatable phrase for the message body
	 * @param array $params list of parameters filled into the subject and body
	 * @param int $recvtype type of receiver
	 * @return boolean true on success, otherwise false
	 */
	public function toList($sender, $recipients, $subject, $message, $params=array(), $recvtype=0) { /* {{{ */
		$error = true;
		$ret = true;
		foreach ($recipients as $recipient) {
			$ret &= $this->toIndividual($sender, $recipient, $subject, $message, $params, $recvtype);
		}
		if(!$ret) {
			$error = false;
		}
		return $error;
	} /* }}} */

	/**
	 * This notification is sent when a workflow action is needed.
	 */
	public function sendRequestWorkflowActionMail($content, $user) { /* {{{ */
		$document = $content->getDocument();
		$folder = $document->getFolder();

		/* Send mail only if enabled in the configuration */
		if($this->settings->_enableNotificationWorkflow && ($workflow = $content->getWorkflow())) {
			$subject = "request_workflow_action_email_subject";
			$message = "request_workflow_action_email_body";
			$params = array();
			$params['name'] = $document->getName();
			$params['version'] = $content->getVersion();
			$params['workflow'] = $workflow->getName();
			$params['folder_path'] = $folder->getFolderPathPlain();
			$params['current_state'] = $workflow->getInitState()->getName();
			$params['username'] = $user->getFullName();
			$params['sitename'] = $this->settings->_siteName;
			$params['http_root'] = $this->settings->_httpRoot;
			$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();

			foreach($workflow->getNextTransitions($workflow->getInitState()) as $ntransition) {
				foreach($ntransition->getUsers() as $tuser) {
					$this->toIndividual($user, $tuser->getUser(), $subject, $message, $params, SeedDMS_NotificationService::RECV_WORKFLOW);
				}
				foreach($ntransition->getGroups() as $tuser) {
					$this->toGroup($user, $tuser->getGroup(), $subject, $message, $params, SeedDMS_NotificationService::RECV_WORKFLOW);
				}
			}
		}
	} /* }}} */

	/**
	 * This notification is sent when a review or approval is needed.
	 */
	public function sendRequestRevAppActionMail($content, $user) { /* {{{ */
		$document = $content->getDocument();
		$folder = $document->getFolder();

		if($this->settings->_enableNotificationAppRev) {
			/* Reviewers and approvers will be informed about the new document */
			$reviewers = $content->getReviewers(); //$controller->getParam('reviewers');
			$approvers = $content->getApprovers(); //$controller->getParam('approvers');
			if($reviewers['i'] || $reviewers['g']) {
				$subject = "review_request_email_subject";
				$message = "review_request_email_body";
				$params = array();
				$params['name'] = $document->getName();
				$params['folder_path'] = $folder->getFolderPathPlain();
				$params['version'] = $content->getVersion();
				$params['comment'] = $document->getComment();
				$params['username'] = $user->getFullName();
				$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
				$params['sitename'] = $this->settings->_siteName;
				$params['http_root'] = $this->settings->_httpRoot;

				foreach($reviewers['i'] as $reviewer) {
					$this->toIndividual($user, $reviewer, $subject, $message, $params, SeedDMS_NotificationService::RECV_REVIEWER);
				}
				foreach($reviewers['g'] as $reviewergrp) {
					$this->toGroup($user, $reviewergrp, $subject, $message, $params, SeedDMS_NotificationService::RECV_REVIEWER);
				}
			}

			elseif($approvers['i'] || $approvers['g']) {
				$subject = "approval_request_email_subject";
				$message = "approval_request_email_body";
				$params = array();
				$params['name'] = $document->getName();
				$params['folder_path'] = $folder->getFolderPathPlain();
				$params['version'] = $content->getVersion();
				$params['comment'] = $document->getComment();
				$params['username'] = $user->getFullName();
				$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
				$params['sitename'] = $this->settings->_siteName;
				$params['http_root'] = $this->settings->_httpRoot;

				foreach($approvers['i'] as $approver) {
					$this->toIndividual($user, $approver, $subject, $message, $params, SeedDMS_NotificationService::RECV_APPROVER);
				}
				foreach($approvers['g'] as $approvergrp) {
					$this->toGroup($user, $approvergrp, $subject, $message, $params, SeedDMS_NotificationService::RECV_APPROVER);
				}
			}
		}
	} /* }}} */

	/**
	 * This notification is sent when a new document is created.
	 */
	public function sendNewDocumentMail($document, $user) { /* {{{ */
		$folder = $document->getFolder();
		$fnl = $folder->getNotifyList();
		$dnl = $document->getNotifyList();
		$nl = array(
			'users'=>array_unique(array_merge($dnl['users'], $fnl['users']), SORT_REGULAR),
			'groups'=>array_unique(array_merge($dnl['groups'], $fnl['groups']), SORT_REGULAR)
		);

		$lc = $document->getLatestContent();
		$subject = "new_document_email_subject";
		$message = "new_document_email_body";
		$params = array();
		$params['name'] = $document->getName();
		$params['folder_name'] = $folder->getName();
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['comment'] = $document->getComment();
		$params['version_comment'] = $lc->getComment();
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;
		$this->toList($user, $nl["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($nl["groups"] as $grp) {
			$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}

		$this->sendRequestWorkflowActionMail($lc, $user);

		$this->sendRequestRevAppActionMail($lc, $user);

	} /* }}} */

	/**
	 * This notification is sent when a new document version is created.
	 */
	public function sendNewDocumentVersionMail($document, $user) { /* {{{ */
		$lc = $document->getLatestContent();
		$folder = $document->getFolder();
		$notifyList = $document->getNotifyList();

		$subject = "document_updated_email_subject";
		$message = "document_updated_email_body";
		$params = array();
		$params['name'] = $document->getName();
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['comment'] = $document->getComment();
		$params['version'] = $lc->getVersion();
		$params['version_comment'] = $lc->getComment();
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;
		$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($notifyList["groups"] as $grp) {
			$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}
//	 if user is not owner send notification to owner
//		if ($user->getID() != $document->getOwner()->getID()) 
//			$this->toIndividual($user, $document->getOwner(), $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);

		$this->sendRequestWorkflowActionMail($lc, $user);

		$this->sendRequestRevAppActionMail($lc, $user);

	} /* }}} */

	/**
	 * This notification is sent when a document is deleted.
	 * Keep in mind that $document refers to a document which has just been
	 * deleted from the database, but all the data needed is still in the
	 * object.
	 */
	public function sendDeleteDocumentMail($document, $user) { /* {{{ */
		$folder = $document->getFolder();
		$dnl =	$document->getNotifyList();
		$fnl =	$folder->getNotifyList();
		$nl = array(
			'users'=>array_unique(array_merge($dnl['users'], $fnl['users']), SORT_REGULAR),
			'groups'=>array_unique(array_merge($dnl['groups'], $fnl['groups']), SORT_REGULAR)
		);
		$subject = "document_deleted_email_subject";
		$message = "document_deleted_email_body";
		$params = array();
		$params['name'] = $document->getName();
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewFolder.php?folderid=".$folder->getID();
		$this->toList($user, $nl["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($nl["groups"] as $grp) {
			$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}
	} /* }}} */

	/**
	 * This notification is sent when a document version is deleted.
	 * Keep in mind that $document refers to a document which has just been
	 * deleted from the database, but all the data needed is still in the
	 * object.
	 */
	public function sendDeleteDocumentVersionMail($document, $user) { /* {{{ */
	} /* }}} */

	/**
	 * This notification is sent when a new folder is created.
	 */
	public function sendNewFolderMail($folder, $user) { /* {{{ */
		$parent = $folder->getParent();
		$fnl = $parent->getNotifyList();
		$snl = $folder->getNotifyList();
		$nl = array(
			'users'=>array_unique(array_merge($snl['users'], $fnl['users']), SORT_REGULAR),
			'groups'=>array_unique(array_merge($snl['groups'], $fnl['groups']), SORT_REGULAR)
		);

		$subject = "new_subfolder_email_subject";
		$message = "new_subfolder_email_body";
		$params = array();
		$params['name'] = $folder->getName();
		$params['folder_name'] = $parent->getName();
		$params['folder_path'] = $parent->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['comment'] = $folder->getComment();
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewFolder.php?folderid=".$folder->getID();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;
		$this->toList($user, $nl["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($nl["groups"] as $grp) {
			$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}
	} /* }}} */

	/**
	 * This notification is sent when a folder is deleted.
	 * Keep in mind that $folder refers to a folder which has just been
	 * deleted from the database, but all the data needed is still in the
	 * object.
	 */
	public function sendDeleteFolderMail($folder, $user) { /* {{{ */
		$parent = $folder->getParent();
		$fnl = $folder->getNotifyList();
		$pnl = $parent->getNotifyList();
		$nl = array(
			'users'=>array_unique(array_merge($fnl['users'], $pnl['users']), SORT_REGULAR),
			'groups'=>array_unique(array_merge($fnl['groups'], $pnl['groups']), SORT_REGULAR)
		);

		$subject = "folder_deleted_email_subject";
		$message = "folder_deleted_email_body";
		$params = array();
		$params['name'] = $folder->getName();
		$params['folder_path'] = $parent->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewFolder.php?folderid=".$parent->getID();
		$this->toList($user, $nl["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($nl["groups"] as $grp) {
			$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}
	} /* }}} */

	/**
	 * This notification is sent when a new attachment is created.
	 */
	public function sendNewFileMail($file, $user) { /* {{{ */
		$document = $file->getDocument();
		$folder = $document->getFolder();
		$notifyList = $document->getNotifyList();

		$subject = "new_file_email_subject";
		$message = "new_file_email_body";
		$params = array();
		$params['name'] = $file->getName();
		$params['document'] = $document->getName();
		$params['folder_name'] = $folder->getName();
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['comment'] = $file->getComment();
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;
		// if user is not owner and owner not already in list of notifiers, then
		// send notification to owner
		if ($user->getID() != $document->getOwner()->getID() &&
			false === SeedDMS_Core_DMS::inList($document->getOwner(), $notifyList['users'])) {
			$this->toIndividual($user, $document->getOwner(), $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);
		}
		$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($notifyList["groups"] as $grp) {
			$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}
	} /* }}} */

	/**
	 * This notification is sent when a document content is replaced.
	 */
	public function sendReplaceContentMail($content, $user) { /* {{{ */
		$document = $content->getDocument();
		$folder = $document->getFolder();
		$notifyList = $document->getNotifyList();

		$subject = "replace_content_email_subject";
		$message = "replace_content_email_body";
		$params = array();
		$params['name'] = $document->getName();
		$params['folder_name'] = $folder->getName();
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['comment'] = $document->getComment();
		$params['version'] = $content->getVersion();
		$params['version_comment'] = $content->getComment();
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;
		$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($notifyList["groups"] as $grp) {
			$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}
	} /* }}} */

	/**
	 * This notification is sent when a new attachment is created.
	 */
	public function sendDeleteFileMail($file, $user) { /* {{{ */
		$document = $file->getDocument();
		$notifyList = $document->getNotifyList();

		$subject = "removed_file_email_subject";
		$message = "removed_file_email_body";
		$params = array();
		$params['document'] = $document->getName();
		$params['username'] = $user->getFullName();
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;
		$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($notifyList["groups"] as $grp) {
			$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}
	} /* }}} */

	public function sendChangedExpiryMail($document, $user, $oldexpires) { /* {{{ */
		$folder = $document->getFolder();
		$notifyList = $document->getNotifyList();

		if($oldexpires != $document->getExpires()) {
			// Send notification to subscribers.
			$subject = "expiry_changed_email_subject";
			$message = "expiry_changed_email_body";
			$params = array();
			$params['name'] = $document->getName();
			$params['folder_path'] = $folder->getFolderPathPlain();
			$params['username'] = $user->getFullName();
			$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
			$params['sitename'] = $this->settings->_siteName;
			$params['http_root'] = $this->settings->_httpRoot;
			// if user is not owner and owner not already in list of notifiers, then
			// send notification to owner
			if ($user->getID() != $document->getOwner()->getID() &&
				false === SeedDMS_Core_DMS::inList($document->getOwner(), $notifyList['users'])) {
				$this->toIndividual($user, $document->getOwner(), $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);
			}
			$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			foreach ($notifyList["groups"] as $grp) {
				$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			}
		}
	} /* }}} */

	public function sendChangedAttributesMail($document, $user, $oldattributes) { /* {{{ */
		$dms = $document->getDMS();
		$folder = $document->getFolder();
		$notifyList = $document->getNotifyList();

		$newattributes = $document->getAttributes();
		if($oldattributes) {
			foreach($oldattributes as $attrdefid=>$attribute) {
				if(!isset($newattributes[$attrdefid]) || $newattributes[$attrdefid]->getValueAsArray() !== $oldattributes[$attrdefid]->getValueAsArray()) {
					$subject = "document_attribute_changed_email_subject";
					$message = "document_attribute_changed_email_body";
					$params = array();
					$params['name'] = $document->getName();
					$params['attribute_name'] = $attribute->getAttributeDefinition()->getName();
					$params['attribute_old_value'] = $oldattributes[$attrdefid]->getValue();
					$params['attribute_new_value'] = isset($newattributes[$attrdefid]) ? $newattributes[$attrdefid]->getValue() : '';
					$params['folder_path'] = $folder->getFolderPathPlain();
					$params['username'] = $user->getFullName();
					$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
					$params['sitename'] = $this->settings->_siteName;
					$params['http_root'] = $this->settings->_httpRoot;

					$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
					foreach ($notifyList["groups"] as $grp) {
						$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
					}
				}
			}
		}
		/* Check for new attributes which didn't have a value before */
		if($newattributes) {
			foreach($newattributes as $attrdefid=>$attribute) {
				if(!isset($oldattributes[$attrdefid]) && $attribute) {
					$subject = "document_attribute_changed_email_subject";
					$message = "document_attribute_changed_email_body";
					$params = array();
					$params['name'] = $document->getName();
					$params['attribute_name'] = $dms->getAttributeDefinition($attrdefid)->getName();
					$params['attribute_old_value'] = '';
					$params['attribute_new_value'] = $attribute->getValue();
					$params['folder_path'] = $folder->getFolderPathPlain();
					$params['username'] = $user->getFullName();
					$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
					$params['sitename'] = $this->settings->_siteName;
					$params['http_root'] = $this->settings->_httpRoot;

					$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
					foreach ($notifyList["groups"] as $grp) {
						$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
					}
				}
			}
		}
	} /* }}} */

	public function sendChangedFolderAttributesMail($folder, $user, $oldattributes) { /* {{{ */
		$dms = $folder->getDMS();
		$notifyList = $folder->getNotifyList();

		$newattributes = $folder->getAttributes();
		if($oldattributes) {
			foreach($oldattributes as $attrdefid=>$attribute) {
				if(!isset($newattributes[$attrdefid]) || $newattributes[$attrdefid]->getValueAsArray() !== $oldattributes[$attrdefid]->getValueAsArray()) {
					$subject = "folder_attribute_changed_email_subject";
					$message = "folder_attribute_changed_email_body";
					$params = array();
					$params['name'] = $folder->getName();
					$params['attribute_name'] = $attribute->getAttributeDefinition()->getName();
					$params['attribute_old_value'] = $oldattributes[$attrdefid]->getValue();
					$params['attribute_new_value'] = isset($newattributes[$attrdefid]) ? $newattributes[$attrdefid]->getValue() : '';
					$params['folder_path'] = $folder->getFolderPathPlain();
					$params['username'] = $user->getFullName();
					$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewFolder.php?folderid=".$folder->getID();
					$params['sitename'] = $this->settings->_siteName;
					$params['http_root'] = $this->settings->_httpRoot;

					$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
					foreach ($notifyList["groups"] as $grp) {
						$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
					}
				}
			}
		}
		/* Check for new attributes which didn't have a value before */
		if($newattributes) {
			foreach($newattributes as $attrdefid=>$attribute) {
				if(!isset($oldattributes[$attrdefid]) && $attribute) {
					$subject = "folder_attribute_changed_email_subject";
					$message = "folder_attribute_changed_email_body";
					$params = array();
					$params['name'] = $folder->getName();
					$params['attribute_name'] = $dms->getAttributeDefinition($attrdefid)->getName();
					$params['attribute_old_value'] = '';
					$params['attribute_new_value'] = $attribute->getValue();
					$params['folder_path'] = $folder->getFolderPathPlain();
					$params['username'] = $user->getFullName();
					$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewFolder.php?folderid=".$folder->getID();
					$params['sitename'] = $this->settings->_siteName;
					$params['http_root'] = $this->settings->_httpRoot;

					$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
					foreach ($notifyList["groups"] as $grp) {
						$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
					}
				}
			}
		}
	} /* }}} */

	public function sendChangedCommentMail($document, $user, $oldcomment) { /* {{{ */
		if($oldcomment != $document->getComment()) {
			$notifyList = $document->getNotifyList();
			$folder = $document->getFolder();
			$subject = "document_comment_changed_email_subject";
			$message = "document_comment_changed_email_body";
			$params = array();
			$params['name'] = $document->getName();
			$params['folder_path'] = $folder->getFolderPathPlain();
			$params['old_comment'] = $oldcomment;
			$params['new_comment'] = $document->getComment();
			$params['username'] = $user->getFullName();
			$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
			$params['sitename'] = $this->settings->_siteName;
			$params['http_root'] = $this->settings->_httpRoot;

			// if user is not owner send notification to owner
			if ($user->getID() != $document->getOwner()->getID() &&
				false === SeedDMS_Core_DMS::inList($document->getOwner(), $notifyList['users'])) {
				$this->toIndividual($user, $document->getOwner(), $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);
			}
			$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			foreach ($notifyList["groups"] as $grp) {
				$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			}
		}
	} /* }}} */

	public function sendChangedFolderCommentMail($folder, $user, $oldcomment) { /* {{{ */
		if($oldcomment != $folder->getComment()) {
			$notifyList = $folder->getNotifyList();

			$subject = "folder_comment_changed_email_subject";
			$message = "folder_comment_changed_email_body";
			$params = array();
			$params['name'] = $folder->getName();
			$params['folder_path'] = $folder->getFolderPathPlain();
			$params['old_comment'] = $oldcomment;
			$params['new_comment'] = $comment;
			$params['username'] = $user->getFullName();
			$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewFolder.php?folderid=".$folder->getID();
			$params['sitename'] = $this->settings->_siteName;
			$params['http_root'] = $this->settings->_httpRoot;
			$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			foreach ($notifyList["groups"] as $grp) {
				$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			}
			// if user is not owner send notification to owner
	//		if ($user->getID() != $folder->getOwner()->getID()) 
	//			$notifier->toIndividual($user, $folder->getOwner(), $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);
		}
	} /* }}} */

	public function sendChangedVersionCommentMail($content, $user, $oldcomment) { /* {{{ */
		// FIXME: use extra mail template which includes the version
		if($oldcomment != $content->getComment()) {
			$document = $content->getDocument();
			$notifyList = $document->getNotifyList();
			$folder = $document->getFolder();
			$subject = "document_comment_changed_email_subject";
			$message = "document_comment_changed_email_body";
			$params = array();
			$params['name'] = $document->getName();
			$params['version'] = $content->getVersion();
			$params['folder_path'] = $folder->getFolderPathPlain();
			$params['old_comment'] = $oldcomment;
			$params['new_comment'] = $content->getComment();
			$params['username'] = $user->getFullName();
			$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
			$params['sitename'] = $this->settings->_siteName;
			$params['http_root'] = $this->settings->_httpRoot;

			// if user is not owner send notification to owner
			if ($user->getID() != $document->getOwner()->getID() &&
				false === SeedDMS_Core_DMS::inList($document->getOwner(), $notifyList['users'])) {
				$this->toIndividual($user, $document->getOwner(), $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);
			}
			$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			foreach ($notifyList["groups"] as $grp) {
				$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			}
		}
	} /* }}} */

	public function sendChangedNameMail($document, $user, $oldname) { /* {{{ */
		if($oldname != $document->getName()) {
			$notifyList = $document->getNotifyList();
			$folder = $document->getFolder();
			$subject = "document_renamed_email_subject";
			$message = "document_renamed_email_body";
			$params = array();
			$params['name'] = $document->getName();
			$params['old_name'] = $oldname;
			$params['folder_path'] = $folder->getFolderPathPlain();
			$params['username'] = $user->getFullName();
			$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
			$params['sitename'] = $this->settings->_siteName;
			$params['http_root'] = $this->settings->_httpRoot;

			// if user is not owner send notification to owner
			if ($user->getID() != $document->getOwner()->getID() &&
				false === SeedDMS_Core_DMS::inList($document->getOwner(), $notifyList['users'])) {
				$this->toIndividual($user, $document->getOwner(), $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);
			}
			$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			foreach ($notifyList["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			}
		}
	} /* }}} */

	public function sendChangedFolderNameMail($folder, $user, $oldname) { /* {{{ */
		if($oldname != $folder->getName()) {
			$notifyList = $folder->getNotifyList();

			$subject = "folder_renamed_email_subject";
			$message = "folder_renamed_email_body";
			$params = array();
			$params['name'] = $folder->getName();
			$params['old_name'] = $oldname;
			$params['folder_path'] = $folder->getFolderPathPlain();
			$params['username'] = $user->getFullName();
			$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewFolder.php?folderid=".$folder->getID();
			$params['sitename'] = $this->settings->_siteName;
			$params['http_root'] = $this->settings->_httpRoot;
			$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			foreach ($notifyList["groups"] as $grp) {
				$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			}
			// if user is not owner send notification to owner
	//		if ($user->getID() != $folder->getOwner()->getID()) 
	//			$this->toIndividual($user, $folder->getOwner(), $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);
		}
	} /* }}} */

	public function sendMovedDocumentMail($document, $user, $oldfolder) { /* {{{ */
		$targetfolder = $document->getFolder();
		if($targetfolder->getId() == $oldfolder->getId())
			return;

		$nl1 = $oldfolder->getNotifyList();
		$nl2 = $document->getNotifyList();
		$nl3 = $targetfolder->getNotifyList();
		$nl = array(
			'users'=>array_unique(array_merge($nl1['users'], $nl2['users'], $nl3['users']), SORT_REGULAR),
			'groups'=>array_unique(array_merge($nl1['groups'], $nl2['groups'], $nl3['groups']), SORT_REGULAR)
		);
		$subject = "document_moved_email_subject";
		$message = "document_moved_email_body";
		$params = array();
		$params['name'] = $document->getName();
		$params['old_folder_path'] = $oldfolder->getFolderPathPlain();
		$params['new_folder_path'] = $targetfolder->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;
		$this->toList($user, $nl["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($nl["groups"] as $grp) {
			$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}
		// if user is not owner send notification to owner
		if ($user->getID() != $document->getOwner()->getID() &&
			false === SeedDMS_Core_DMS::inList($document->getOwner(), $notifyList['users'])) {
			$this->toIndividual($user, $document->getOwner(), $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);
		}
	} /* }}} */

	public function sendMovedFolderMail($folder, $user, $oldfolder) { /* {{{ */
		$targetfolder = $folder->getParent();
		if($targetfolder->getId() == $oldfolder->getId())
			return;

		$nl1 = $oldfolder->getNotifyList();
		$nl2 = $folder->getNotifyList();
		$nl3 = $targetfolder->getNotifyList();
		$nl = array(
			'users'=>array_unique(array_merge($nl1['users'], $nl2['users'], $nl3['users']), SORT_REGULAR),
			'groups'=>array_unique(array_merge($nl1['groups'], $nl2['groups'], $nl3['groups']), SORT_REGULAR)
		);
		$subject = "folder_moved_email_subject";
		$message = "folder_moved_email_body";
		$params = array();
		$params['name'] = $folder->getName();
		$params['old_folder_path'] = $oldfolder->getFolderPathPlain();
		$params['new_folder_path'] = $targetfolder->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewFolder.php?folderid=".$folder->getID();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;
		$this->toList($user, $nl["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($nl["groups"] as $grp) {
			$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}
		// if user is not owner send notification to owner
		if ($user->getID() != $folder->getOwner()->getID() &&
			false === SeedDMS_Core_DMS::inList($folder->getOwner(), $notifyList['users'])) {
			$this->toIndividual($user, $folder->getOwner(), $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);
		}
	} /* }}} */

	public function sendTransferDocumentMail($document, $user, $oldowner) { /* {{{ */
		$folder = $document->getFolder();
		$nl =	$document->getNotifyList();
		$subject = "document_transfered_email_subject";
		$message = "document_transfered_email_body";
		$params = array();
		$params['name'] = $document->getName();
		$params['newuser'] = $document->getOwner()->getFullName();
		$params['olduser'] = $oldowner->getFullName();
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
		$this->toList($user, $nl["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($nl["groups"] as $grp) {
			$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}
	} /* }}} */

	public function sendChangedDocumentStatusMail($content, $user, $oldstatus) { /* {{{ */
		$document = $content->getDocument();
		$overallStatus = $content->getStatus();
		$nl = $document->getNotifyList();
		$folder = $document->getFolder();
		$subject = "document_status_changed_email_subject";
		$message = "document_status_changed_email_body";
		$params = array();
		$params['name'] = $document->getName();
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['status'] = getOverallStatusText($overallStatus["status"]);
		$params['old_status'] = getOverallStatusText($oldstatus);
		$params['new_status_code'] = $overallStatus["status"];
		$params['old_status_code'] = $oldstatus;
		$params['username'] = $user->getFullName();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
		$this->toList($user, $nl["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($nl["groups"] as $grp) {
			$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}

//		$notifier->toIndividual($user, $content->getUser(), $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);
	} /* }}} */

	public function sendNewDocumentNotifyMail($document, $user, $obj) { /* {{{ */
		$folder = $document->getFolder();
		$subject = "notify_added_email_subject";
		$message = "notify_added_email_body";
		$params = array();
		$params['name'] = $document->getName();
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;

		if($obj->isType('user'))
			$this->toIndividual($user, $obj, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		elseif($obj->isType('group'))
			$notifier->toGroup($user, $obj, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
	} /* }}} */

	public function sendNewFolderNotifyMail($folder, $user, $obj) { /* {{{ */
		$subject = "notify_added_email_subject";
		$message = "notify_added_email_body";
		$params = array();
		$params['name'] = $folder->getName();
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewFolder.php?folderid=".$folder->getID();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;

		if($obj->isType('user'))
			$this->toIndividual($user, $obj, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		elseif($obj->isType('group'))
			$notifier->toGroup($user, $obj, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
	} /* }}} */

	public function sendDeleteDocumentNotifyMail($document, $user, $obj) { /* {{{ */
		$folder = $document->getFolder();
		$subject = "notify_deleted_email_subject";
		$message = "notify_deleted_email_body";
		$params = array();
		$params['name'] = $folder->getName();
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;

		if($obj->isType('user'))
			$this->toIndividual($user, $obj, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		elseif($obj->isType('group'))
			$notifier->toGroup($user, $obj, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
	} /* }}} */

	public function sendDeleteFolderNotifyMail($folder, $user, $obj) { /* {{{ */
		$subject = "notify_deleted_email_subject";
		$message = "notify_deleted_email_body";
		$params = array();
		$params['name'] = $folder->getName();
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewFolder.php?folderid=".$folder->getID();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;

		if($obj->isType('user'))
			$this->toIndividual($user, $obj, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		elseif($obj->isType('group'))
			$notifier->toGroup($user, $obj, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
	} /* }}} */

	public function sendSubmittedReviewMail($content, $user, $reviewlog) { /* {{{ */
		$document = $content->getDocument();
		$nl=$document->getNotifyList();
		$folder = $document->getFolder();
		$subject = "review_submit_email_subject";
		$message = "review_submit_email_body";
		$params = array();
		$params['name'] = $document->getName();
		$params['version'] = $content->getVersion();
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['status'] = getReviewStatusText($reviewlog["status"]);
		$params['comment'] = $reviewlog['comment'];
		$params['username'] = $user->getFullName();
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;
		$this->toList($user, $nl["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($nl["groups"] as $grp) {
			$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}
//			$notifier->toIndividual($user, $content->getUser(), $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);
	} /* }}} */

	public function sendSubmittedApprovalMail($content, $user, $approvelog) { /* {{{ */
		$document = $content->getDocument();
		$nl=$document->getNotifyList();
		$folder = $document->getFolder();
		$subject = "approval_submit_email_subject";
		$message = "approval_submit_email_body";
		$params = array();
		$params['name'] = $document->getName();
		$params['version'] = $content->getVersion();
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['status'] = getApprovalStatusText($approvelog["status"]);
		$params['comment'] = $approvelog['comment'];
		$params['username'] = $user->getFullName();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID()."&currenttab=revapp";

		$this->toList($user, $nl["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($nl["groups"] as $grp)
			$notifier->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
//		$this->toIndividual($user, $content->getUser(), $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);

	} /* }}} */

	public function sendChangedDocumentOwnerMail($document, $user, $oldowner) { /* {{{ */
		if($oldowner->getID() != $document->getOwner()->getID()) {
			$notifyList = $document->getNotifyList();
			$folder = $document->getFolder();
			$subject = "ownership_changed_email_subject";
			$message = "ownership_changed_email_body";
			$params = array();
			$params['name'] = $document->getName();
			$params['folder_path'] = $folder->getFolderPathPlain();
			$params['username'] = $user->getFullName();
			$params['old_owner'] = $oldowner->getFullName();
			$params['new_owner'] = $document->getOwner()->getFullName();
			$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
			$params['sitename'] = $this->settings->_siteName;
			$params['http_root'] = $this->settings->_httpRoot;
			$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			foreach ($notifyList["groups"] as $grp) {
				$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			}
//			$notifier->toIndividual($user, $oldowner, $subject, $message, $params, SeedDMS_NotificationService::RECV_OWNER);
		}
	} /* }}} */

	public function sendChangedFolderOwnerMail($folder, $user, $oldowner) { /* {{{ */
		if($oldowner->getID() != $folder->getOwner()->getID()) {
			$notifyList = $folder->getNotifyList();
			$subject = "ownership_changed_email_subject";
			$message = "ownership_changed_email_body";
			$params = array();
			$params['name'] = $folder->getName();
			if($folder->getParent())
				$params['folder_path'] = $folder->getParent()->getFolderPathPlain();
			else
				$params['folder_path'] = $folder->getFolderPathPlain();
			$params['username'] = $user->getFullName();
			$params['old_owner'] = $oldowner->getFullName();
			$params['new_owner'] = $folder->getOwner()->getFullName();
			$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewFolder.php?folderid=".$folder->getID();
			$params['sitename'] = $this->settings->_siteName;
			$params['http_root'] = $this->settings->_httpRoot;
			$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			foreach ($notifyList["groups"] as $grp) {
				$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
			}
		}
	} /* }}} */

	public function sendChangedDocumentAccessMail($document, $user) { /* {{{ */
		$notifyList = $document->getNotifyList();
		$folder = $document->getFolder();
		$subject = "access_permission_changed_email_subject";
		$message = "access_permission_changed_email_body";
		$params = array();
		$params['name'] = $document->getName();
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;
		$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($notifyList["groups"] as $grp) {
			$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}
	} /* }}} */

	public function sendChangedFolderAccessMail($folder, $user) { /* {{{ */
		$notifyList = $folder->getNotifyList();
		$subject = "access_permission_changed_email_subject";
		$message = "access_permission_changed_email_body";
		$params = array();
		$params['name'] = $folder->getName();
		if($folder->getParent())
			$params['folder_path'] = $folder->getParent()->getFolderPathPlain();
		else
			$params['folder_path'] = $folder->getFolderPathPlain();
		$params['username'] = $user->getFullName();
		$params['url'] = getBaseUrl().$this->settings->_httpRoot."out/out.ViewFolder.php?folderid=".$folder->getID();
		$params['sitename'] = $this->settings->_siteName;
		$params['http_root'] = $this->settings->_httpRoot;
		$this->toList($user, $notifyList["users"], $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		foreach ($notifyList["groups"] as $grp) {
			$this->toGroup($user, $grp, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
		}
	} /* }}} */

}

