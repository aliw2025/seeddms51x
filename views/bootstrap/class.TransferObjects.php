<?php
/**
 * Implementation of TransferObjects view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2017 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
//require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for TransferObjects view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2017 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_TransferObjects extends SeedDMS_Theme_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$rmuser = $this->params['rmuser'];
		$allusers = $this->params['allusers'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentHeading(getMLText("transfer_objects"));

		$this->warningMsg(getMLText("confirm_transfer_objects", array ("username" => htmlspecialchars($rmuser->getFullName()))));
?>
<form class="form-horizontal" action="../op/op.UsrMgr.php" name="form1" method="post">
<input type="hidden" name="userid" value="<?php print $rmuser->getID();?>">
<input type="hidden" name="action" value="transferobjects">
<?php echo createHiddenFieldWithKey('transferobjects'); ?>
<?php
		$this->contentContainerStart();
		$options = array();
		foreach ($allusers as $currUser) {
			if ($currUser->isGuest() || ($currUser->getID() == $rmuser->getID()) )
				continue;

			if ($rmuser && $currUser->getID()==$rmuser->getID()) $selected=$count;
			$options[] = array($currUser->getID(), htmlspecialchars($currUser->getLogin()." - ".$currUser->getFullName()));
		}
		$this->formField(
			getMLText("transfer_objects_to_user"),
			array(
				'element'=>'select',
				'name'=>'assignTo',
				'class'=>'chzn-select',
				'options'=>$options
			)
		);
		$this->contentContainerEnd();
		$this->formSubmit("<i class=\"fa fa-share-alt\"></i> ".getMLText('transfer_objects'));
?>
</form>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
