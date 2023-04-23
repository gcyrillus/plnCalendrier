<?php 
if(!defined('PLX_ROOT')) exit;

# Control du token du formulaire
plxToken::validateFormToken($_POST);

# Si on a des arguments POST, c'est que l'utilisateur a demandé à enregistrer les nouveaux paramètres.
# On enregistre donc.
if(!empty($_POST)) {
	$plxPlugin->setParam('style',	    	plxUtils::strCheck($_POST['style']), 	 	'string');
	$plxPlugin->setParam('droitAcces',    	plxUtils::strCheck($_POST['droitAcces']),  	'string');
	$plxPlugin->setParam('template', 		plxUtils::strCheck($_POST['template']), 	'string');
	$plxPlugin->setParam('inStaticList', 	plxUtils::strCheck($_POST['inStaticList']), 'string');
	$plxPlugin->setParam('nbMonths',	 	plxUtils::strCheck($_POST['nbMonths']), 	'numeric');
	$plxPlugin->setParam('nbMonthsSide', 	plxUtils::strCheck($_POST['nbMonthsSide']),	'numeric');
	$plxPlugin->setParam('sideTitle',	 	plxUtils::strCheck($_POST['sideTitle']),	'cdata');
	$plxPlugin->setParam('sideHelp',	 	plxUtils::strCheck($_POST['sideHelp']),		'string');
	if(isset($_POST["sideTitleOk"]))
		$plxPlugin->setParam('sideTitleOk',	"true",	'string');
	else
		$plxPlugin->setParam('sideTitleOk',	"false", 'string');

	$plxPlugin->saveParams();
	header('Location: parametres_plugin.php?p='.$plxPlugin->getName());
	exit;
}
if(isset($_GET) and isset($_GET["migration"]))
{
	$plxPlugin->migration();
	header('Location: parametres_plugin.php?p='.$plxPlugin->getName());
	exit;
}

# Quel template l'utilisateur utilise-t-il actuellement ?
$template 		= $plxPlugin->getParam('template') == '' ? 'static.php' : $plxPlugin->getParam('template');
# L'utilisateur veut-il que le lien pointant vers le calendrier semestriel soit dans la liste des pages statiques ?
$inStaticList 	= $plxPlugin->getParam('inStaticList') == '' ? 'true' : $plxPlugin->getParam('inStaticList');
# le nombre de mois qu'on veut voir apparaitre sur le calendrier principal
$nbMonths 		= $plxPlugin->getParam('nbMonths') == '' ? 6 : $plxPlugin->getParam('nbMonths');
# le nombre de mois qu'on veut voir apparaitre sur le calendrier de la sidebar
$nbMonthsSide	= $plxPlugin->getParam('nbMonthsSide') == '' ? 2 : $plxPlugin->getParam('nbMonthsSide');
# Le titre du calendrier secondaire
$sideTitleOk	= $plxPlugin->getParam('sideTitleOk') 	== '' ? "true" : $plxPlugin->getParam('sideTitleOk');
$sideTitle		= $plxPlugin->getParam('sideTitle') 	== '' ? "<h3>".$plxPlugin->getLang('DEFAULT_SIDE_TITLE')."</h3>" : $plxPlugin->getParam('sideTitle');
$sideHelp		= $plxPlugin->getParam('sideHelp') 		== '' ? "after" : $plxPlugin->getParam('sideHelp');
# le style d'affichage choisi
$style			= $plxPlugin->getParam('style') == '' ? 'azur' : $plxPlugin->getParam('style');

# On prépare la liste des profils utilisateurs possibles
$adroitAcces = array(	PROFIL_ADMIN 		=> 'Administrateur',
						PROFIL_MANAGER 		=> 'Gestionnaire',
						PROFIL_MODERATOR 	=> 'Modérateur',
						PROFIL_EDITOR 		=> 'Éditeur',
						PROFIL_WRITER 		=> 'Rédacteur',
					);

# L'array des possibilités pour l'aide de la sidebar
$aSideHelp = array(	"deactivated" 	=> $plxPlugin->getlang("deactivated"), 
					"after"			=> $plxPlugin->getlang("after"), 
					"before"		=> $plxPlugin->getlang("before"));

# On récupère l'ensemble des templates des pages statiques
$files = plxGlob::getInstance(PLX_ROOT.$plxAdmin->aConf['racine_themes'].$plxAdmin->aConf['style']);
if ($array = $files->query('/^static(-[a-z0-9-_]+)?.php$/')) {
	foreach($array as $k=>$v)
		$aTemplates[$v] = $v;
}

# On construit un array de "oui"/"non"
$aYesNo				= array();
$aYesNo["true"] 	= $plxPlugin->getlang('oui');
$aYesNo["false"] 	= $plxPlugin->getlang('non');

// On construit un array permettant de choisir le nombre de mois qu'on veut voir apparaitre sur le calendrier principal
$aNbMonths 			= array("2" => "2", "3" => "3", "4" => "4", "6" => "6");

// On construit un array permettant de choisir le nombre de mois qu'on veut voir apparaitre sur le calendrier sidebar
$aNbMonthsSide		= array("1" => "1", "2" => "2", "3" => "3", "4" => "4");

// Les styles disponibles 
$aStyle 			= array('azur' => 'azur', 'nature' => 'nature');
?>

<form id="form_plxcalendrier" action="parametres_plugin.php?p=<?php echo $plxPlugin->getName()?>" method="post">
	<fieldset>

		<div class="grid">
			<div class="col sml-12 med-5 label-centered">
				<label for="id_droitAcces"><?php $plxPlugin->lang('L_CONFIG_DROIT_ACCES') ?>&nbsp;:</label>
			</div>
			<div class="col sml-12 med-7">
				<?php plxUtils::printSelect('droitAcces', $adroitAcces, $plxPlugin->getParam('droitAcces')) ?>
				<a class="hint"><span><?php $plxPlugin->lang('L_HELP_DROIT_ACCES')?></span></a>
			</div>
		</div>

		<div class="grid">
			<div class="col sml-12 med-5 label-centered">
				<label for="id_inStaticList"><?php $plxPlugin->lang('L_INSTATICLIST') ?>&nbsp;:</label>
			</div>
			<div class="col sml-12 med-7">
				<?php plxUtils::printSelect('inStaticList', $aYesNo, $inStaticList) ?>
				<a class="hint"><span><?php $plxPlugin->lang('L_HELP_INSTATICLIST')?></span></a>
			</div>
		</div>

		<h3><?php $plxPlugin->lang('Calendrier principal') ?></h3>

		<div class="grid">
			<div class="col sml-12 med-5 label-centered">
				<label for="id_template"><?php $plxPlugin->lang('L_TEMPLATE') ?>&nbsp;:</label>
			</div>
			<div class="col sml-12 med-7">
				<?php plxUtils::printSelect('template', $aTemplates, $template) ?>
				<a class="hint"><span><?php $plxPlugin->lang('L_HELP_TEMPLATE')?></span></a>
			</div>
		</div>

		<div class="grid">
			<div class="col sml-12 med-5 label-centered">
				<label for="id_style"><?php $plxPlugin->lang('L_CONFIG_STYLE') ?>&nbsp;:</label>
			</div>
			<div class="col sml-12 med-7">
				<?php plxUtils::printSelect('style', $aStyle, $style) ?>
			</div>
		</div>

		<div class="grid">
			<div class="col sml-12 med-5 label-centered">
				<label for="id_nbMonths"><?php $plxPlugin->lang('L_NBMONTHS') ?>&nbsp;:</label>
			</div>
			<div class="col sml-12 med-7">
				<?php plxUtils::printSelect('nbMonths', $aNbMonths, $nbMonths) ?>
			</div>
		</div>

		<h3><?php $plxPlugin->lang('Calendrier secondaire') ?></h3>

		<div class="grid">
			<div class="col sml-12 med-5 label-centered">
				<label for="id_sideTitle"><?php $plxPlugin->lang('L_SIDE_TITLE') ?>&nbsp;:</label>
			</div>
			<div class="col sml-12 med-7">
				<input type="checkbox" name="sideTitleOk" value="1" <?php echo ($sideTitleOk == "true") ? "checked" : '' ?>>
				<?php plxUtils::printInput('sideTitle', $sideTitle) ?>
				<a class="hint"><span><?php $plxPlugin->lang('L_HELP_SIDE_TITLE')?></span></a>
			</div>
		</div>

		<div class="grid">
			<div class="col sml-12 med-5 label-centered">
				<label for="id_nbMonthsSide"><?php $plxPlugin->lang('L_NBMONTHSSIDE') ?>&nbsp;:</label>
			</div>
			<div class="col sml-12 med-7">
				<?php plxUtils::printSelect('nbMonthsSide', $aNbMonthsSide, $nbMonthsSide) ?>
			</div>
		</div>

		<div class="grid">
			<div class="col sml-12 med-5 label-centered">
				<label for="id_sideHelp"><?php $plxPlugin->lang('L_CONFIG_SIDE_HELP') ?>&nbsp;:</label>
			</div>
			<div class="col sml-12 med-7">
				<?php plxUtils::printSelect('sideHelp', $aSideHelp, $sideHelp) ?>
			</div>
		</div>

		<?php echo plxToken::getTokenPostMethod() ?>
		<p class="in-action-bar" style="z-index1"><input type="submit" name="submit" value="<?php $plxPlugin->lang('L_SAVE') ?>" /></p>
	</fieldset>
</form>

