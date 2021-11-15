<?php
/**
 * Implementation of Login view
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
 * Class which outputs the html page for Login view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Login extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		$dms = $this->params['dms'];
		$enableguestlogin = $this->params['enableguestlogin'];
		$guest = null;
		if($enableguestlogin) {
			$guestid = $this->params['guestid'];
			$guest = $dms->getUser((int) $guestid);
		}
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
?>
document.form1.login.focus();

$(document).ready( function() {
<?php
		if($guest) {
?>
	function guestLogin()
	{
		theme = $("#themeselector").val();
		lang = $("#languageselector").val();
		url = "../op/op.Login.php?login=<?= $guest->getLogin() ?>";
		if(theme)
			url += "&sesstheme=" + theme;
		if(lang)
			url += "&lang=" + lang;
		if (document.form1.referuri) {
			url += "&referuri=" + escape(document.form1.referuri.value);
		}
		document.location.href = url;
	}
	$('body').on('click', '#guestlogin', function(ev){
		ev.preventDefault();
		guestLogin();
	});
<?php
		}
?>
	$("#form").validate({
		messages: {
			login: "<?php printMLText("js_no_login");?>",
			pwd: "<?php printMLText("js_no_pwd");?>"
		},
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$enableguestlogin = $this->params['enableguestlogin'];
		$guestid = $this->params['guestid'];
		$enablepasswordforgotten = $this->params['enablepasswordforgotten'];
		$refer = $this->params['referrer'];
		$themes = $this->params['themes'];
		$msg = $this->params['msg'];
		$languages = $this->params['languages'];
		$enableLanguageSelector = $this->params['enablelanguageselector'];
		$enableThemeSelector = $this->params['enablethemeselector'];

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("sign_in"), "login");
		$this->globalBanner();
		$this->contentStart();
		echo "<div id=\"login_wrapper\">\n";
		$this->pageNavigation(getMLText("sign_in"));
		if($msg)
			$this->errorMsg(htmlspecialchars($msg));
?>
<form class="form-horizontal" action="../op/op.Login.php" method="post" name="form1" id="form">
<?php
		$this->contentContainerStart();
		if ($refer) {
			echo "<input type='hidden' name='referuri' value='".htmlspecialchars($refer)."'/>";
		}
		$this->formField(
			getMLText("user_login"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'login',
				'name'=>'login',
				'placeholder'=>getMLText('user_login'),
				'autocomplete'=>'on',
				'required'=>true
			)
		);
		$this->formField(
			getMLText("password"),
			array(
				'element'=>'input',
				'type'=>'password',
				'id'=>'pwd',
				'name'=>'pwd',
				'placeholder'=>getMLText('password'),
				'autocomplete'=>'off',
				'required'=>true
			)
		);
		if($enableLanguageSelector) {
			$html = "<select id=\"languageselector\" class=\"form-control\" name=\"lang\">";
			$html .= "<option value=\"\">-";
			foreach ($languages as $currLang) {
				$html .= "<option value=\"".$currLang."\">".getMLText($currLang)."</option>";
			}
			$html .= "</select>";
			$this->formField(
				getMLText("language"),
				$html
			);
		}
		if($enableThemeSelector) {
			$html = "<select id=\"themeselector\" class=\"form-control\" name=\"sesstheme\">";
			$html .= "<option value=\"\">-";
			foreach ($themes as $currTheme) {
				$html .= "<option value=\"".$currTheme."\">".$currTheme;
			}
			$html .= "</select>";
			$this->formField(
				getMLText("theme"),
				$html
			);
		}
		$this->formSubmit(getMLText('submit_login'));
		$this->contentContainerEnd();
?>
</form>
<?php
		$tmpfoot = array();
		if ($enableguestlogin && $guestid && $dms->getUser((int) $guestid))
			$tmpfoot[] = "<a href=\"\" id=\"guestlogin\">" . getMLText("guest_login") . "</a>\n";
		if ($enablepasswordforgotten)
			$tmpfoot[] = "<a href=\"../out/out.PasswordForgotten.php\">" . getMLText("password_forgotten") . "</a>\n";
		if($tmpfoot) {
			print "<p>";
			print implode(' | ', $tmpfoot);
			print "</p>\n";
		}
		echo "</div>\n";
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
