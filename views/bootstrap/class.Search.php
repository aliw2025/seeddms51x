<?php
/**
 * Implementation of Search result view
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
 * Include class to preview documents
 */
require_once("SeedDMS/Preview.php");

/**
 * Class which outputs the html page for Search result view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Search extends SeedDMS_Theme_Style {

	/**
	 * Mark search query sting in a given string
	 *
	 * @param string $str mark this text
	 * @param string $tag wrap the marked text with this html tag
	 * @return string marked text
	 */
	function markQuery($str, $tag = "b") { /* {{{ */
		$querywords = preg_split("/ /", $this->query);
		
		foreach ($querywords as $queryword)
			$str = str_ireplace("($queryword)", "<" . $tag . ">\\1</" . $tag . ">", $str);
		
		return $str;
	} /* }}} */

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');

		parent::jsTranslations(array('cancel', 'splash_move_document', 'confirm_move_document', 'move_document', 'confirm_transfer_link_document', 'transfer_content', 'link_document', 'splash_move_folder', 'confirm_move_folder', 'move_folder'));

//		$this->printFolderChooserJs("form1");
		$this->printDeleteFolderButtonJs();
		$this->printDeleteDocumentButtonJs();
		/* Add js for catching click on document in one page mode */
		$this->printClickDocumentJs();
		$this->printClickFolderJs();
?>
$(document).ready(function() {
	$('body').on('submit', '#form1', function(ev){
	});
});
<?php
	} /* }}} */

		function opensearchsuggestion() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$query = $this->params['query'];
		$entries = $this->params['searchhits'];
		$recs = array();
		$content = "<?xml version=\"1.0\"?>\n";
		$content .= "<SearchSuggestion version=\"2.0\" xmlns=\"http://opensearch.org/searchsuggest2\">\n";
		$content .= "<Query xml:space=\"preserve\">".$query."</Query>";
		if($entries) {
			$content .= "<Section>\n";
			foreach ($entries as $entry) {
				$content .= "<Item>\n";
				if($entry->isType('document')) {
					$content .= "<Text xml:space=\"preserve\">".$entry->getName()."</Text>\n";
					$content .= "<Url xml:space=\"preserve\">http:".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$entry->getId()."</Url>\n";
				} elseif($entry->isType('folder')) {
					$content .= "<Text xml:space=\"preserve\">".$entry->getName()."</Text>\n";
					$content .= "<Url xml:space=\"preserve\">http:".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewFolder.php?folderid=".$entry->getId()."</Url>\n";
				}
				$content .= "</Item>\n";
			}
			$content .= "</Section>\n";
		}
		$content .= "</SearchSuggestion>";
		header("Content-Disposition: attachment; filename=\"search.xml\"; filename*=UTF-8''search.xml");
		header('Content-Type: application/x-suggestions+xml');
		echo $content;
	} /* }}} */

function typeahead() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$query = $this->params['query'];
		$entries = $this->params['searchhits'];
		$recs = array();
		if($entries) {
			foreach ($entries as $entry) {
				if($entry->isType('document')) {
//					$recs[] = 'D'.$entry->getName();
					$recs[] = array('type'=>'D', 'id'=>$entry->getId(), 'name'=>$entry->getName());
				} elseif($entry->isType('folder')) {
//					$recs[] = 'F'.$entry->getName();
					$recs[] = array('type'=>'F', 'id'=>$entry->getId(), 'name'=>$entry->getName());
				}
			}
		}
		array_unshift($recs, array('type'=>'S', 'name'=>$query));
		header('Content-Type: application/json');
		echo json_encode($recs);
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$fullsearch = $this->params['fullsearch'];
		$totaldocs = $this->params['totaldocs'];
		$totalfolders = $this->params['totalfolders'];
		$attrdefs = $this->params['attrdefs'];
		$allCats = $this->params['allcategories'];
		$allUsers = $this->params['allusers'];
		$mode = $this->params['mode'];
		$resultmode = $this->params['resultmode'];
		$workflowmode = $this->params['workflowmode'];
		$enablefullsearch = $this->params['enablefullsearch'];
		$enableclipboard = $this->params['enableclipboard'];
		$attributes = $this->params['attributes'];
		$categories = $this->params['categories'];
		$mimetype = $this->params['mimetype'];
		$owner = $this->params['owner'];
		$startfolder = $this->params['startfolder'];
		$createstartdate = $this->params['createstartdate'];
		$createenddate = $this->params['createenddate'];
		$expstartdate = $this->params['expstartdate'];
		$expenddate = $this->params['expenddate'];
		$statusstartdate = $this->params['statusstartdate'];
		$statusenddate = $this->params['statusenddate'];
		$creationdate = $this->params['creationdate'];
		$expirationdate = $this->params['expirationdate'];
		$statusdate = $this->params['statusdate'];
		$status = $this->params['status'];
		$this->query = $this->params['query'];
		$orderby = $this->params['orderby'];
		$entries = $this->params['searchhits'];
		$facets = $this->params['facets'];
		$totalpages = $this->params['totalpages'];
		$pageNumber = $this->params['pagenumber'];
		$searchTime = $this->params['searchtime'];
		$urlparams = $this->params['urlparams'];
		$searchin = $this->params['searchin'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$this->htmlStartPage(getMLText("search_results"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation("", "");

		$this->rowStart();
		$this->columnStart(4);
		$this->contentHeading("<button class=\"btn btn-primary\" id=\"searchform-toggle\" data-toggle=\"collapse\" href=\"#searchform\"><i class=\"fa fa-exchange\"></i></button> ".getMLText('search'), true);
		if($this->query) {
		echo "<div id=\"searchform\" class=\"collapse mb-sm-4\">";
		}
?>
  <ul class="nav nav-pills" id="searchtab">
	  <li class="nav-item <?php echo ($fullsearch == false) ? 'active' : ''; ?>"><a class="nav-link <?php echo ($fullsearch == false) ? 'active' : ''; ?>" data-target="#database" data-toggle="tab"><?php printMLText('databasesearch'); ?></a></li>
<?php
		if($enablefullsearch) {
?>
	  <li class="nav-item <?php echo ($fullsearch == true) ? 'active' : ''; ?>"><a class="nav-link <?php echo ($fullsearch == true) ? 'active' : ''; ?>" data-target="#fulltext" data-toggle="tab"><?php printMLText('fullsearch'); ?></a></li>
<?php
		}
?>
	</ul>
	<div class="tab-content">
	  <div class="tab-pane <?php echo ($fullsearch == false) ? 'active' : ''; ?>" id="database">
		<form class="form-horizontal" action="<?= $this->params['settings']->_httpRoot ?>out/out.Search.php" name="form1">
<input type="hidden" name="fullsearch" value="0" />
<?php
// Database search Form {{{
		$this->contentContainerStart();

		$this->formField(
			getMLText("search_query"),
			array(
				'element'=>'input',
				'type'=>'text',
				'name'=>'query',
				'value'=>htmlspecialchars($this->query)
			)
		);
		$options = array();
		$options[] = array('1', getMLText('search_mode_and'), $mode=='AND');
		$options[] = array('0', getMLText('search_mode_or'), $mode=='OR');
		$this->formField(
			getMLText("search_mode"),
			array(
				'element'=>'select',
				'name'=>'mode',
				'multiple'=>false,
				'options'=>$options
			)
		);
		$options = array();
		$options[] = array('1', getMLText('keywords').' ('.getMLText('documents_only').')', in_array('1', $searchin));
		$options[] = array('2', getMLText('name'), in_array('2', $searchin));
		$options[] = array('3', getMLText('comment'), in_array('3', $searchin));
		$options[] = array('4', getMLText('attributes'), in_array('4', $searchin));
		$options[] = array('5', getMLText('id'), in_array('5', $searchin));
		$this->formField(
			getMLText("search_in"),
			array(
				'element'=>'select',
				'name'=>'searchin[]',
				'class'=>'chzn-select',
				'multiple'=>true,
				'options'=>$options
			)
		);
		$options = array();
		foreach ($allUsers as $currUser) {
			if($user->isAdmin() || (!$currUser->isGuest() && (!$currUser->isHidden() || $currUser->getID() == $user->getID())))
				$options[] = array($currUser->getID(), htmlspecialchars($currUser->getLogin()), in_array($currUser->getID(), $owner), array(array('data-subtitle', htmlspecialchars($currUser->getFullName()))));
		}
		$this->formField(
			getMLText("owner"),
			array(
				'element'=>'select',
				'name'=>'owner[]',
				'class'=>'chzn-select',
				'multiple'=>true,
				'options'=>$options
			)
		);
		$options = array();
		$options[] = array('1', getMLText('search_mode_documents'), $resultmode==1);
		$options[] = array('2', getMLText('search_mode_folders'), $resultmode==2);
		$options[] = array('3', getMLText('search_resultmode_both'), $resultmode==3);
		$this->formField(
			getMLText("search_resultmode"),
			array(
				'element'=>'select',
				'name'=>'resultmode',
				'multiple'=>false,
				'options'=>$options
			)
		);
		$this->formField(getMLText("under_folder"), $this->getFolderChooserHtml("form1", M_READ, -1, $startfolder));
		$this->formField(
			getMLText("creation_date")." (".getMLText('from').")",
			$this->getDateChooser($createstartdate, "createstart", $this->params['session']->getLanguage())
		);
		$this->formField(
			getMLText("creation_date")." (".getMLText('to').")",
			$this->getDateChooser($createenddate, "createend", $this->params['session']->getLanguage())
		);
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_all) {
					if($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_date) {
						$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('from').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['from']) ? getReadableDate(makeTsFromDate($attributes[$attrdef->getID()]['from'])) : '', 'attributes', true, 'from'));
						$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('to').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['to']) ? getReadableDate(makeTsFromDate($attributes[$attrdef->getID()]['to'])) : '', 'attributes', true, 'to'));
					} else
						$this->formField(htmlspecialchars($attrdef->getName()), $this->getAttributeEditField($attrdef, isset($attributes[$attrdef->getID()]) ? $attributes[$attrdef->getID()] : '', 'attributes', true));
				}
			}
		}
		$this->formSubmit("<i class=\"fa fa-search\"></i> ".getMLText('search'));
		$this->contentContainerEnd();

		/* First check if any of the folder filters are set. If it is,
		 * open the accordion.
		 */
		$openfilterdlg = false;
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_document || $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_documentcontent) {
					if(!empty($attributes[$attrdef->getID()]))
						$openfilterdlg = true;
				}
			}
		}
		if($categories)
			$openfilterdlg = true;
		if($status)
			$openfilterdlg = true;
		if($expirationdate)
			$openfilterdlg = true;
		if($statusdate)
			$openfilterdlg = true;

		/* Start of fields only applicable to documents */
		ob_start();
		$tmpcatids = array();
		foreach($categories as $tmpcat)
			$tmpcatids[] = $tmpcat->getID();
		$options = array();
		$allcategories = $dms->getDocumentCategories();
		foreach($allcategories as $category) {
			$options[] = array($category->getID(), $category->getName(), in_array($category->getId(), $tmpcatids));
		}
		$this->formField(
			getMLText("categories"),
			array(
				'element'=>'select',
				'class'=>'chzn-select',
				'name'=>'category[]',
				'multiple'=>true,
				'attributes'=>array(array('data-placeholder', getMLText('select_category'), array('data-no_results_text', getMLText('unknown_document_category')))),
				'options'=>$options
			)
		);
		$options = array();
		if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
			if($workflowmode == 'traditional') { 
				$options[] = array(S_DRAFT_REV, getOverallStatusText(S_DRAFT_REV), in_array(S_DRAFT_REV, $status));
			}
		} elseif($workflowmode == 'advanced') {
			$options[] = array(S_IN_WORKFLOW, getOverallStatusText(S_IN_WORKFLOW), in_array(S_IN_WORKFLOW, $status));
		}
		$options[] = array(S_DRAFT_APP, getOverallStatusText(S_DRAFT_APP), in_array(S_DRAFT_APP, $status));
		$options[] = array(S_RELEASED, getOverallStatusText(S_RELEASED), in_array(S_RELEASED, $status));
		$options[] = array(S_REJECTED, getOverallStatusText(S_REJECTED), in_array(S_REJECTED, $status));
		$options[] = array(S_EXPIRED, getOverallStatusText(S_EXPIRED), in_array(S_EXPIRED, $status));
		$options[] = array(S_OBSOLETE, getOverallStatusText(S_OBSOLETE), in_array(S_OBSOLETE, $status));
		$this->formField(
			getMLText("status"),
			array(
				'element'=>'select',
				'class'=>'chzn-select',
				'name'=>'status[]',
				'multiple'=>true,
				'attributes'=>array(array('data-placeholder', getMLText('select_status')), array('data-no_results_text', getMLText('unknown_status'))),
				'options'=>$options
			)
		);
		$this->formField(
			getMLText("expires")." (".getMLText('from').")",
			$this->getDateChooser($expstartdate, "expirationstart", $this->params['session']->getLanguage())
		);
		$this->formField(
			getMLText("expires")." (".getMLText('to').")",
			$this->getDateChooser($expenddate, "expirationend", $this->params['session']->getLanguage())
		);
		$this->formField(
			getMLText("status_change")." (".getMLText('from').")",
			$this->getDateChooser($statusstartdate, "statusdatestart", $this->params['session']->getLanguage())
		);
		$this->formField(
			getMLText("status_change")." (".getMLText('to').")",
			$this->getDateChooser($statusenddate, "statusdateend", $this->params['session']->getLanguage())
		);
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_document || $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_documentcontent) {
					if($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_date) {
						$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('from').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['from']) ? getReadableDate(makeTsFromDate($attributes[$attrdef->getID()]['from'])) : '', 'attributes', true, 'from'));
						$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('to').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['to']) ? getReadableDate(makeTsFromDate($attributes[$attrdef->getID()]['to'])) : '', 'attributes', true, 'to'));
					} else
						$this->formField(htmlspecialchars($attrdef->getName()), $this->getAttributeEditField($attrdef, isset($attributes[$attrdef->getID()]) ? $attributes[$attrdef->getID()] : '', 'attributes', true));
				}
			}
		}
?>
<?php
		$content = ob_get_clean();
		$this->printAccordion(getMLText('filter_for_documents'), $content);
		/* First check if any of the folder filters are set. If it is,
		 * open the accordion.
		 */
		$openfilterdlg = false;
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_folder) {
					if(!empty($attributes[$attrdef->getID()]))
						$openfilterdlg = true;
				}
			}
		}
		ob_start();
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_folder) {
					if($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_date) {
						$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('from').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['from']) ? getReadableDate(makeTsFromDate($attributes[$attrdef->getID()]['from'])) : '', 'attributes', true, 'from'));
						$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('to').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['to']) ? getReadableDate(makeTsFromDate($attributes[$attrdef->getID()]['to'])) : '', 'attributes', true, 'to'));
					} else
						$this->formField(htmlspecialchars($attrdef->getName()), $this->getAttributeEditField($attrdef, isset($attributes[$attrdef->getID()]) ? $attributes[$attrdef->getID()] : '', 'attributes', true));
				}
			}
		}
		$content = ob_get_clean();
		$this->printAccordion(getMLText('filter_for_folders'), $content);
		// }}}
?>
</form>
		</div>
<?php
		if($enablefullsearch) {
	  	echo "<div class=\"tab-pane ".(($fullsearch == true) ? 'active' : '')."\" id=\"fulltext\">\n";
?>
<form action="<?= $this->params['settings']->_httpRoot ?>out/out.Search.php" name="form2" style="min-height: 330px;">
<input type="hidden" name="fullsearch" value="1" />
<?php
			$this->contentContainerStart();
			$this->formField(
				getMLText("search_query"),
				array(
					'element'=>'input',
					'type'=>'text',
					'name'=>'query',
					'value'=>htmlspecialchars($this->query)
				)
			);
			$this->formField(getMLText("under_folder"), $this->getFolderChooserHtml("form1", M_READ, -1, $startfolder, 'folderfullsearchid'));
			if(!isset($facets['owner'])) {
			$options = array();
			foreach ($allUsers as $currUser) {
				if($user->isAdmin() || (!$currUser->isGuest() && (!$currUser->isHidden() || $currUser->getID() == $user->getID())))
					$options[] = array($currUser->getID(), htmlspecialchars($currUser->getLogin()), in_array($currUser->getID(), $owner), array(array('data-subtitle', htmlspecialchars($currUser->getFullName()))));
			}
			$this->formField(
				getMLText("owner"),
				array(
					'element'=>'select',
					'name'=>'owner[]',
					'class'=>'chzn-select',
					'multiple'=>true,
					'options'=>$options
				)
			);
		}
		if(!isset($facets['category'])) {
			$tmpcatids = array();
			foreach($categories as $tmpcat)
				$tmpcatids[] = $tmpcat->getID();
			$options = array();
			$allcategories = $dms->getDocumentCategories();
			foreach($allcategories as $category) {
				$options[] = array($category->getID(), $category->getName(), in_array($category->getId(), $tmpcatids));
			}
			$this->formField(
				getMLText("category_filter"),
				array(
					'element'=>'select',
					'class'=>'chzn-select',
					'name'=>'category[]',
					'multiple'=>true,
					'attributes'=>array(array('data-placeholder', getMLText('select_category'), array('data-no_results_text', getMLText('unknown_document_category')))),
					'options'=>$options
				)
			);
		}
		$options = array();
		if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
			if($workflowmode == 'traditional') { 
				$options[] = array(S_DRAFT_REV, getOverallStatusText(S_DRAFT_REV), in_array(S_DRAFT_REV, $status));
			}
		} elseif($workflowmode == 'advanced') {
			$options[] = array(S_IN_WORKFLOW, getOverallStatusText(S_IN_WORKFLOW), in_array(S_IN_WORKFLOW, $status));
		}
		$options[] = array(S_DRAFT_APP, getOverallStatusText(S_DRAFT_APP), in_array(S_DRAFT_APP, $status));
		$options[] = array(S_RELEASED, getOverallStatusText(S_RELEASED), in_array(S_RELEASED, $status));
		$options[] = array(S_REJECTED, getOverallStatusText(S_REJECTED), in_array(S_REJECTED, $status));
		$options[] = array(S_EXPIRED, getOverallStatusText(S_EXPIRED), in_array(S_EXPIRED, $status));
		$options[] = array(S_OBSOLETE, getOverallStatusText(S_OBSOLETE), in_array(S_OBSOLETE, $status));
		$this->formField(
			getMLText("status"),
			array(
				'element'=>'select',
				'class'=>'chzn-select',
				'name'=>'status[]',
				'multiple'=>true,
				'attributes'=>array(array('data-placeholder', getMLText('select_status')), array('data-no_results_text', getMLText('unknown_status'))),
				'options'=>$options
			)
		);

		if($facets) {
foreach($facets as $facetname=>$values) {
	$options = array();
	foreach($values as $v=>$c) {
		$option = array($v, $v.' ('.$c.')');
		if(isset(${$facetname}) && in_array($v, ${$facetname}))
			$option[] = true;
		$options[] = $option;
	}
	$this->formField(
		getMLText($facetname),
		array(
			'element'=>'select',
			'id'=>$facetname,
			'name'=>$facetname."[]",
			'class'=>'chzn-select',
			'attributes'=>array(array('data-placeholder', getMLText('select_'.$facetname))),
			'options'=>$options,
			'multiple'=>true
		)
	);
}
		}
	$this->contentContainerEnd();
	$this->formSubmit("<i class=\"fa fa-search\"></i> ".getMLText('search'));
?>
</form>
<?php
			echo "</div>\n";
		}
?>
	</div>
<?php
		if($this->query) {
			echo "</div>\n";
		}
		$this->columnEnd();
		$this->columnStart(8);
		$this->contentHeading(getMLText('search_results'));
// Search Result {{{
		$foldercount = $doccount = 0;
		if($entries) {
			/*
			foreach ($entries as $entry) {
				if($entry->isType('document')) {
					$doccount++;
				} elseif($entry->isType('document')) {
					$foldercount++;
				}
			}
			 */
			echo $this->infoMsg(getMLText("search_report", array("doccount" => $totaldocs, "foldercount" => $totalfolders, 'searchtime'=>$searchTime)));
			$this->pageList($pageNumber, $totalpages, "../out/out.Search.php", $urlparams);
//			$this->contentContainerStart();

			$txt = $this->callHook('searchListHeader', $orderby, 'asc');
			if(is_string($txt))
				echo $txt;
			else {
				parse_str($_SERVER['QUERY_STRING'], $tmp);
				$tmp['orderby'] = $orderby=="n"||$orderby=="na)"?"nd":"n";
				print "<table class=\"table table-condensed table-sm table-hover\">";
				print "<thead>\n<tr>\n";
				print "<th></th>\n";
				print "<th>".getMLText("name");
				if(!$fullsearch) {
					print " <a href=\"../out/out.Search.php?".http_build_query($tmp)."\" title=\"".getMLText("sort_by_name")."\">".($orderby=="n"||$orderby=="na"?' <i class="fa fa-sort-alpha-asc selected"></i>':($orderby=="nd"?' <i class="fa fa-sort-alpha-desc selected"></i>':' <i class="fa fa-sort-alpha-asc"></i>'))."</a>";
					$tmp['orderby'] = $orderby=="d"||$orderby=="da)"?"dd":"d";
					print " <a href=\"../out/out.Search.php?".http_build_query($tmp)."\" title=\"".getMLText("sort_by_date")."\">".($orderby=="d"||$orderby=="da"?' <i class="fa fa-sort-amount-asc selected"></i>':($orderby=="dd"?' <i class="fa fa-sort-amount-desc selected"></i>':' <i class="fa fa-sort-amount-asc"></i>'))."</a>";
				}
				print "</th>\n";
				//print "<th>".getMLText("attributes")."</th>\n";
				print "<th>".getMLText("status")."</th>\n";
				print "<th>".getMLText("action")."</th>\n";
				print "</tr>\n</thead>\n<tbody>\n";
			}

			$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
			foreach ($entries as $entry) {
				if($entry->isType('document')) {
					$txt = $this->callHook('documentListItem', $entry, $previewer, false, 'search');
					if(is_string($txt))
						echo $txt;
					else {
						$document = $entry;
						$owner = $document->getOwner();
						if($lc = $document->getLatestContent())
							$previewer->createPreview($lc);

						if (in_array(3, $searchin))
							$comment = $this->markQuery(htmlspecialchars($document->getComment()));
						else
							$comment = htmlspecialchars($document->getComment());
						if (strlen($comment) > 150) $comment = substr($comment, 0, 147) . "...";

						$lcattributes = $lc ? $lc->getAttributes() : null;
						$attrstr = '';
						if($lcattributes) {
							$attrstr .= "<table class=\"table table-condensed table-sm\">\n";
							$attrstr .= "<tr><th>".getMLText('name')."</th><th>".getMLText('attribute_value')."</th></tr>";
							foreach($lcattributes as $lcattribute) {
								$arr = $this->callHook('showDocumentContentAttribute', $lc, $lcattribute);
								if(is_array($arr)) {
									$attrstr .= "<tr>";
									$attrstr .= "<td>".$arr[0].":</td>";
									$attrstr .= "<td>".$arr[1]."</td>";
									$attrstr .= "</tr>";
								} elseif(is_string($arr)) {
									$attrstr .= $arr;
								} else {
									$attrdef = $lcattribute->getAttributeDefinition();
									$attrstr .= "<tr><td>".htmlspecialchars($attrdef->getName())."</td><td>".htmlspecialchars(implode(', ', $lcattribute->getValueAsArray()))."</td></tr>\n";
									// TODO: better use printAttribute()
									// $this->printAttribute($lcattribute);
								}
							}
							$attrstr .= "</table>\n";
						}
						$docattributes = $document->getAttributes();
						if($docattributes) {
							$attrstr .= "<table class=\"table table-condensed table-sm\">\n";
							$attrstr .= "<tr><th>".getMLText('name')."</th><th>".getMLText('attribute_value')."</th></tr>";
							foreach($docattributes as $docattribute) {
								$arr = $this->callHook('showDocumentAttribute', $document, $docattribute);
								if(is_array($arr)) {
									$attrstr .= "<tr>";
									$attrstr .= "<td>".$arr[0].":</td>";
									$attrstr .= "<td>".$arr[1]."</td>";
									$attrstr .= "</tr>";
								} elseif(is_string($arr)) {
									$attrstr .= $arr;
								} else {
									$attrdef = $docattribute->getAttributeDefinition();
									$attrstr .= "<tr><td>".htmlspecialchars($attrdef->getName())."</td><td>".htmlspecialchars(implode(', ', $docattribute->getValueAsArray()))."</td></tr>\n";
								}
							}
							$attrstr .= "</table>\n";
						}
						$extracontent = array();
						$extracontent['below_title'] = $this->getListRowPath($document);
						if($attrstr)
							$extracontent['bottom_title'] = '<br />'.$this->printPopupBox('<span class="btn btn-mini btn-sm btn-secondary">'.getMLText('attributes').'</span>', $attrstr, true);
						print $this->documentListRow($document, $previewer, false, 0, $extracontent);
					}
				} elseif($entry->isType('folder')) {
					$txt = $this->callHook('folderListItem', $entry, false, 'search');
					if(is_string($txt))
						echo $txt;
					else {
					$folder = $entry;
					$owner = $folder->getOwner();
					if (in_array(2, $searchin)) {
						$folderName = $this->markQuery(htmlspecialchars($folder->getName()), "i");
					} else {
						$folderName = htmlspecialchars($folder->getName());
					}

					$attrstr = '';
					$folderattributes = $folder->getAttributes();
					if($folderattributes) {
						$attrstr .= "<table class=\"table table-condensed table-sm\">\n";
						$attrstr .= "<tr><th>".getMLText('name')."</th><th>".getMLText('attribute_value')."</th></tr>";
						foreach($folderattributes as $folderattribute) {
							$attrdef = $folderattribute->getAttributeDefinition();
							$attrstr .= "<tr><td>".htmlspecialchars($attrdef->getName())."</td><td>".htmlspecialchars(implode(', ', $folderattribute->getValueAsArray()))."</td></tr>\n";
						}
						$attrstr .= "</table>";
					}
					$extracontent = array();
					$extracontent['below_title'] = $this->getListRowPath($folder);
					if($attrstr)
						$extracontent['bottom_title'] = '<br />'.$this->printPopupBox('<span class="btn btn-mini btn-sm btn-secondary">'.getMLText('attributes').'</span>', $attrstr, true);
					print $this->folderListRow($folder, false, $extracontent);
					}
				}
			}
			print "</tbody></table>\n";
//			$this->contentContainerEnd();
			$this->pageList($pageNumber, $totalpages, "../out/out.Search.php", $_GET);
		} else {
			$numResults = $totaldocs + $totalfolders;
			if ($numResults == 0) {
				echo $this->warningMsg(getMLText("search_no_results"));
			}
		}
// }}}
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
