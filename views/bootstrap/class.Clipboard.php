<?php
/**
 * Implementation of Clipboard view
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
 * Class which outputs the html page for clipboard view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Clipboard extends SeedDMS_Theme_Style {
	/**
	 * Returns the html needed for the clipboard list in the menu
	 *
	 * This function renders the clipboard in a way suitable to be
	 * used as a menu
	 *
	 * @param array $clipboard clipboard containing two arrays for both
	 *        documents and folders.
	 * @return string html code
	 */
	public function menuClipboard() { /* {{{ */
		$clipboard = $this->params['session']->getClipboard();
		if ($this->params['user']->isGuest() || (count($clipboard['docs']) + count($clipboard['folders'])) == 0) {
			return '';
		}

		$menuitems = [];

		$content = '';
		$content .= "   <ul id=\"main-menu-clipboard\" class=\"nav pull-right\">\n";
		$content .= "    <li class=\"dropdown add-clipboard-area\">\n";
		$content .= "     <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\" class=\"add-clipboard-area\">".getMLText('clipboard')." (".count($clipboard['folders'])."/".count($clipboard['docs']).") <i class=\"fa fa-caret-down\"></i></a>\n";
		$content .= "     <ul class=\"dropdown-menu\" role=\"menu\">\n";
		$subitems = [];
		foreach($clipboard['folders'] as $folderid) {
			if($folder = $this->params['dms']->getFolder($folderid)) {
				$content .= "    <li><a href=\"".$this->params['settings']->_httpRoot."out/out.ViewFolder.php?folderid=".$folder->getID()."\" class=\"table-row-folder droptarget\" data-droptarget=\"folder_".$folder->getID()."\" rel=\"folder_".$folder->getID()."\" data-name=\"".htmlspecialchars($folder->getName(), ENT_QUOTES)."\" data-uploadformtoken=\"".createFormKey('')."\" formtoken=\"".createFormKey('')."\"><i class=\"fa fa-folder-o\"></i> ".htmlspecialchars($folder->getName())."</a></li>\n";
				$subitems[] = array('label'=>'<i class="fa fa-folder-o"></i> '.$folder->getName(), 'link'=>$this->params['settings']->_httpRoot."out/out.ViewFolder.php?folderid=".$folder->getID(), 'class'=>"table-row-folder droptarget", 'rel'=>"folder_".$folder->getID(), 'attributes'=>array(array('data-droptarget', "folder_".$folder->getID()), array('data-name', htmlspecialchars($folder->getName(), ENT_QUOTES))));
			}
		}
		foreach($clipboard['docs'] as $docid) {
			if($document = $this->params['dms']->getDocument($docid))
				$content .= "    <li><a href=\"".$this->params['settings']->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID()."\" class=\"table-row-document droptarget\" data-droptarget=\"document_".$document->getID()."\" rel=\"document_".$document->getID()."\" data-name=\"".htmlspecialchars($document->getName(), ENT_QUOTES)."\" formtoken=\"".createFormKey('')."\"><i class=\"fa fa-file\"></i> ".htmlspecialchars($document->getName())."</a></li>\n";
				$subitems[] = array('label'=>'<i class="fa fa-file"></i> '.$document->getName(), 'link'=>$this->params['settings']->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID(), 'class'=>"table-row-document droptarget", 'rel'=>"document_".$document->getID(), 'attributes'=>array(array('data-droptarget', "document_".$document->getID()), array('data-name', htmlspecialchars($document->getName(), ENT_QUOTES))));
		}
		$content .= "    <li class=\"divider\"></li>\n";
		$subitems[] = array('divider'=>true);
		if(isset($this->params['folder']) && $this->params['folder']->getAccessMode($this->params['user']) >= M_READWRITE) {
			$content .= "    <li><a href=\"".$this->params['settings']->_httpRoot."op/op.MoveClipboard.php?targetid=".$this->params['folder']->getID()."&refferer=".urlencode($this->params['settings']->_httpRoot.'out/out.ViewFolder.php?folderid='.$this->params['folder']->getID())."\">".getMLText("move_clipboard")."</a></li>\n";
			$subitems[] = array('label'=>getMLText("move_clipboard"), 'link'=>$this->params['settings']->_httpRoot."op/op.MoveClipboard.php?targetid=".$this->params['folder']->getID()."&refferer=".urlencode($this->params['settings']->_httpRoot.'out/out.ViewFolder.php?folderid='.$this->params['folder']->getID()));
		}
//		$content .= "    <li><a href=\"../op/op.ClearClipboard.php?refferer=".urlencode($this->params['refferer'])."\">".getMLText("clear_clipboard")."</a><a class=\"ajax-click\" data-href=\"../op/op.Ajax.php\" data-param1=\"command=clearclipboard\">kkk</a> </li>\n";
//		$content .= "    <li><a class=\"ajax-click\" data-href=\"../op/op.Ajax.php\" data-param1=\"command=clearclipboard\">".getMLText("clear_clipboard")."</a></li>\n";
		$subitems[] = array('label'=>getMLText('clear_clipboard'), 'attributes'=>array(array('class', 'ajax-click'), array('data-href', $this->params['settings']->_httpRoot.'op/op.Ajax.php'), array('data-param1', 'command=clearclipboard')));
		if($this->hasHook('clipboardMenuItems'))
			$subitems = $this->callHook('clipboardMenuItems', $clipboard, $subitems);
		/*
		foreach($menuitems as $menuitem) {
			$content .= "<li>";
			$content .= "<a";
			if(!empty($menuitem['link']))
				$content .= ' href="'.$menuitem['link'].'"';
			if(!empty($menuitem['attributes']))
				foreach($menuitem['attributes'] as $attr)
					$content .= ' '.$attr[0].'="'.$attr[1].'"';
			$content .= ">";
			$content .= getMLText($menuitem['label']);
			$content .= "</a></li>";
		}
		 */
		$content .= "     </ul>\n";
		$content .= "    </li>\n";
		$content .= "   </ul>\n";
		$menuitems['clipboard'] = array('label'=>getMLText('clipboard')." (".count($clipboard['folders'])."/". count($clipboard['docs']).")", 'children'=>$subitems);
		self::showNavigationBar($menuitems, array('right'=>true));
//		echo $content;
	} /* }}} */

	/**
	 * Return row of clipboard for a folder
	 *
	 * @param object $folder
	 * @return string rendered html content
	 */
	public function folderClipboardRow($folder) { /* {{{ */
		$dms = $this->params['dms'];

		$content = '';
		$comment = $folder->getComment();
		if (strlen($comment) > 150) $comment = substr($comment, 0, 147) . "...";
//		$content .= "<tr draggable=\"true\" rel=\"folder_".$folder->getID()."\" class=\"folder table-row-folder\" formtoken=\"".createFormKey('movefolder')."\">";
		$content .= $this->folderListRowStart($folder);
		$content .= "<td><a draggable=\"false\" href=\"out.ViewFolder.php?folderid=".$folder->getID()."&showtree=".showtree()."\"><img draggable=\"false\" src=\"".$this->getMimeIcon(".folder")."\" width=\"24\" height=\"24\" border=0></a></td>\n";
		$content .= "<td><a draggable=\"false\" href=\"out.ViewFolder.php?folderid=".$folder->getID()."&showtree=".showtree()."\">" . htmlspecialchars($folder->getName()) . "</a>";
		/*
		if($comment) {
			$content .= "<br /><span style=\"font-size: 85%;\">".htmlspecialchars($comment)."</span>";
		}
		 */
		$content .= $this->getListRowPath($folder);
		$content .= "</td>\n";
		$content .= "<td>\n";
		$content .= "<div class=\"list-action\"><a class=\"removefromclipboard\" rel=\"F".$folder->getID()."\" msg=\"".getMLText('splash_removed_from_clipboard')."\" title=\"".getMLText('rm_from_clipboard')."\"><i class=\"fa fa-remove\"></i></a></div>";
		$content .= "</td>\n";
		$content .= "</tr>\n";

		return $content;
	} /* }}} */

	/**
	 * Return row of clipboard for a document
	 *
	 * @param object $document
	 * @return string rendered html content
	 */
	public function documentClipboardRow($document, $previewer) { /* {{{ */
		$dms = $this->params['dms'];

		$content = '';
		$comment = $document->getComment();
		if (strlen($comment) > 150) $comment = substr($comment, 0, 147) . "...";
		$latestContent = $this->callHook('documentLatestContent', $document);
		if($latestContent === null)
			$latestContent = $document->getLatestContent();
		if($latestContent) {
			$previewer->createPreview($latestContent);
			$version = $latestContent->getVersion();
			$status = $latestContent->getStatus();
			
			$content .= $this->documentListRowStart($document);

			if (file_exists($dms->contentDir . $latestContent->getPath())) {
				$content .= "<td><a draggable=\"false\" href=\"".$this->params['settings']->_httpRoot."op/op.Download.php?documentid=".$document->getID()."&version=".$version."\">";
				if($previewer->hasPreview($latestContent)) {
					$content .= "<img draggable=\"false\" class=\"mimeicon\" width=\"40\"src=\"".$this->params['settings']->_httpRoot."op/op.Preview.php?documentid=".$document->getID()."&version=".$latestContent->getVersion()."&width=40\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
				} else {
					$content .= "<img draggable=\"false\" class=\"mimeicon\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
				}
				$content .= "</a></td>";
			} else
				$content .= "<td><img draggable=\"false\" class=\"mimeicon\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\"></td>";
			
			$content .= "<td><a draggable=\"false\" href=\"out.ViewDocument.php?documentid=".$document->getID()."&showtree=".showtree()."\">" . htmlspecialchars($document->getName()) . "</a>";
			/*
			if($comment) {
				$content .= "<br /><span style=\"font-size: 85%;\">".htmlspecialchars($comment)."</span>";
			}
			 */
			$content .= $this->getListRowPath($document);
			$content .= "</td>\n";
			$content .= "<td>\n";
			$content .= "<div class=\"list-action\"><a class=\"removefromclipboard\" rel=\"D".$document->getID()."\" msg=\"".getMLText('splash_removed_from_clipboard')."\" _href=\"".$this->params['settings']->_httpRoot."op/op.RemoveFromClipboard.php?folderid=".(isset($this->params['folder']) ? $this->params['folder']->getID() : '')."&id=".$document->getID()."&type=document\" title=\"".getMLText('rm_from_clipboard')."\"><i class=\"fa fa-remove\"></i></a></div>";
			$content .= "</td>\n";
			$content .= "</tr>";
		}
		return $content;
	} /* }}} */

	/**
	 * Return clipboard content rendered as html
	 *
	 * @param array clipboard
	 * @return string rendered html content
	 */
	public function mainClipboard() { /* {{{ */
		$dms = $this->params['dms'];
		$clipboard = $this->params['session']->getClipboard();
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		$content = '';
		$txt = $this->callHook('preClipboard', $clipboard);
		if(is_string($txt))
			$content .= $txt;
		$foldercount = $doccount = 0;
		if($clipboard['folders']) {
			foreach($clipboard['folders'] as $folderid) {
				/* FIXME: check for access rights, which could have changed after adding the folder to the clipboard */
				if($folder = $dms->getFolder($folderid)) {
					$txt = $this->callHook('folderClipboardItem', $folder, 'clipboard');
					if(is_string($txt))
						$content .= $txt;
					else {
						$content .= $this->folderClipboardRow($folder);
					}

					$foldercount++;
				}
			}
		}
		if($clipboard['docs']) {
			foreach($clipboard['docs'] as $docid) {
				/* FIXME: check for access rights, which could have changed after adding the document to the clipboard */
				if($document = $dms->getDocument($docid)) {
					$document->verifyLastestContentExpriry();
					$txt = $this->callHook('documentClipboardItem', $document, $previewer, 'clipboard');
					if(is_string($txt))
						$content .= $txt;
					else {
						$content .= $this->documentClipboardRow($document, $previewer);
					}

					$doccount++;
				}
			}
		}

		/* $foldercount or $doccount will only count objects which are
		 * actually available
		 */
		if($foldercount || $doccount) {
			$content = "<table class=\"table table-condensed table-sm\">".$content;
			$content .= "</table>";
		} else {
		}
		$content .= "<div class=\"alert alert-warning add-clipboard-area\">".getMLText("drag_icon_here")."</div>";
		$txt = $this->callHook('postClipboard', $clipboard);
		if(is_string($txt))
			$content .= $txt;
		echo $content;
	} /* }}} */

}
