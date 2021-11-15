<?php
/**
 * Implementation of RemoveWorkflowFromDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
//require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for Removeorkflow view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_RemoveWorkflowFromDocument extends SeedDMS_Theme_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];

		$latestContent = $document->getLatestContent();

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("rm_workflow"));

		$currentstate = $latestContent->getWorkflowState();
		$wkflog = $latestContent->getWorkflowLog();
		$workflow = $latestContent->getWorkflow();

		if($currentstate) {
			$msg = "The document is currently in state: ".$currentstate->getName()."<br />";
			if($wkflog) {
				foreach($wkflog as $entry) {
					if($entry->getTransition()->getNextState()->getID() == $currentstate->getID()) {
						$enterdate = $entry->getDate();
						$enterts = makeTsFromLongDate($enterdate);
					}
				}
				$msg .= "The state was entered at ".$enterdate." which was ";
				$msg .= getReadableDuration((time()-$enterts))." ago.<br />";
			}
			$msg .= "The document may stay in this state for ".$currentstate->getMaxTime()." sec.";
		} else {
			$msg = getMLText('workflow_in_unknown_state');
		}
		$this->infoMsg($msg);

		$this->contentContainerStart();
		// Display the Workflow form.
		$this->rowStart();
		$this->columnStart(4);
?>
	<p><?php printMLText("rm_workflow_warning"); ?></p>
	<form method="post" action="../op/op.RemoveWorkflowFromDocument.php" name="form1">
	<?php echo createHiddenFieldWithKey('removeworkflowfromdocument'); ?>
	<input type='hidden' name='documentid' value='<?php echo $document->getId(); ?>'/>
	<input type='hidden' name='version' value='<?php echo $latestContent->getVersion(); ?>'/>
	<button type='submit' class="btn btn-danger"><i class="fa fa-remove"></i> <?php printMLText("rm_workflow"); ?></button>
	</form>
<?php
		$this->columnEnd();
		$this->columnStart(8);
?>
	<div id="workflowgraph">
	<iframe src="out.WorkflowGraph.php?workflow=<?php echo $workflow->getID(); ?>" width="100%" height="500" style="border: 1px solid #AAA;"></iframe>
	</div>
<?php
		$this->columnEnd();
		$this->contentContainerEnd();

		if($wkflog) {
			$this->contentContainerStart();
			echo "<table class=\"table-condensed\">";
			echo "<tr><th>".getMLText('action')."</th><th>Start state</th><th>End state</th><th>".getMLText('date')."</th><th>".getMLText('user')."</th><th>".getMLText('comment')."</th></tr>";
			foreach($wkflog as $entry) {
				echo "<tr>";
				echo "<td>".getMLText('action_'.$entry->getTransition()->getAction()->getName())."</td>";
				echo "<td>".$entry->getTransition()->getState()->getName()."</td>";
				echo "<td>".$entry->getTransition()->getNextState()->getName()."</td>";
				echo "<td>".$entry->getDate()."</td>";
				echo "<td>".$entry->getUser()->getFullname()."</td>";
				echo "<td>".$entry->getComment()."</td>";
				echo "</tr>";
			}
			echo "</table>\n";
			$this->contentContainerEnd();
		}

		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
