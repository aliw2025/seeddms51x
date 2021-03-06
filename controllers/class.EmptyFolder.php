<?php
/**
 * Implementation of EmptyFolder controller
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class which does the busines logic for downloading a document
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_EmptyFolder extends SeedDMS_Controller_Common {

	public function run() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$folder = $this->params['folder'];
		$index = $this->params['index'];
		$indexconf = $this->params['indexconf'];

		/* Get the document id and name before removing the document */
		$foldername = $folder->getName();
		$folderid = $folder->getID();

		if(false === $this->callHook('preEmptyFolder')) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_preEmptyFolder_failed';
			return null;
		}

		$result = $this->callHook('emptyFolder', $folder);
		if($result === null) {
			/* Register a callback which removes each document from the fulltext index
			 * The callback must return null other the removal will be canceled.
			 */
			function removeFromIndex($arr, $document) {
				$index = $arr[0];
				$indexconf = $arr[1];
				$lucenesearch = new $indexconf['Search']($index);
				if($hit = $lucenesearch->getDocument($document->getID())) {
					$index->delete($hit->id);
					$index->commit();
				}
				return null;
			}
			if($index)
				$dms->setCallback('onPreEmptyDocument', 'removeFromIndex', array($index, $indexconf));

			if (!$folder->emptyFolder()) {
				$this->errormsg = 'error_occured';
				return false;
			}
		} elseif($result === false) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_emptyFolder_failed';
			return false;
		}

		if(false === $this->callHook('postEmptyFolder')) {
		}

		return true;
	}
}
