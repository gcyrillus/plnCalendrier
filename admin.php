<?php

if (!defined('PLX_ROOT')) exit;

plxToken::validateFormToken($_POST);



	# On construit le tableau d'association d'articles
	$arts = $plxAdmin->plxGlob_arts->aFiles;
	$artList='';
	foreach($arts as $k => $v) {
	//echo $v;
	 $info = $plxAdmin->artInfoFromFilename($v);
	 //echo (int)$info ["artId"] .'/'. $info["artUrl"].'<br>';
	 $artList .= '<option value="'.(int)$info ["artId"] .'/'. $info["artUrl"].'">'.(int)$info ["artId"] .'/'. $info["artUrl"].'</option>'.PHP_EOL;
	}


function plnCalendrier_format_date($Date)
{
	if($Date == "")
		return "";

	// On lance la fonction deux fois, parce que sinon il peut louper un truc...
	$Date = preg_replace("/(^|[^0-9])([0-9])([^0-9]|$)/","\${1}0$2$3",$Date);
	$Date = preg_replace("/(^|[^0-9])([0-9])([^0-9]|$)/","\${1}0$2$3",$Date);

	if(preg_match("/^[0-9]{2}[^0-9]/",$Date))
	{
		$Jour		= substr($Date,0,2);
		$Mois 		= substr($Date,3,2);
		$Annee		= substr($Date,6,4);
	}
	else
	{
		$Annee		= substr($Date,0,4);
		$Mois 		= substr($Date,5,2);
		$Jour		= substr($Date,8,2);
	}
	return $Annee."-".$Mois."-".$Jour;
}

# Si l'utilisateur vient de valider une saisie, on la traite
if(!empty($_POST)) 
{
	$plxMotor=plxAdmin::getInstance();
	// On enregistre les changements sur les dates existantes
	foreach($_POST as $Type => $ValeurDummy)
		if($Type != "Date_Nouveau" and substr($Type,0,5) == "Date_")
		{
			$Id    = substr($Type,5);
			$Date  = substr($Type,5,10);
			$Event = substr($Type,16);

			if(isset($_POST["Destroy_".$Id]))
				$plxPlugin->DestroyDate($Date,$Event);
			else
			{
				$NewDate 	= plnCalendrier_format_date(trim(plxUtils::strCheck($_POST["Date_".$Id])));
				$Libelle 	= trim(plxUtils::strCheck($_POST["Libelle_".$Id]));
				$Texte	 	= trim(plxUtils::strCheck($_POST["Texte_".$Id]));
				$Style	 	= trim(plxUtils::strCheck($_POST["Style_".$Id]));
				$Article 	= trim(plxUtils::strCheck($_POST["Article_".$Id]));

				// On essaie d'etre souple sur la date fournie par l'utilisateur. 
				// On accepte : AAAA-MM-JJ et JJ-MM-AAAA, le tiret pouvant etre n'importe quoi en fait
				// L'article demandé par l'utilisateur fait-il partie des articles actifs du site ?
				if($Article != "")
				{
					$aArticlesActifs = array_keys($plxMotor->activeArts);
					foreach($aArticlesActifs as $cle => $valeur)
						$aArticlesActifs[$cle] = (int)$valeur;

					if(!in_array((int)preg_replace("#/.*#","",$Article),$aArticlesActifs))
						plxMsg::Error(preg_replace("/<ARTICLE>/",$Article,$plxPlugin->getlang('L_ERREUR_ARTICLE')));
				}


				if($plxPlugin->ChangeDate($Date,$Event,$NewDate,$Libelle,$Texte,$Style,$Article)==false)
					plxMsg::Error(preg_replace("/<DATE>/",$_POST["Date_".$Date],$plxPlugin->getlang('L_ERREUR_DATE')));
			}
		}
	
	$NewDate 	= plnCalendrier_format_date(trim(plxUtils::strCheck($_POST["Date_Nouveau"])));
	$Libelle	= trim(plxUtils::strCheck($_POST["Libelle_Nouveau"]));
	$Texte	 	= trim(plxUtils::strCheck($_POST["Texte_Nouveau"]));
	$Style	 	= trim(plxUtils::strCheck($_POST["Style_Nouveau"]));
	$Article 	= trim(plxUtils::strCheck($_POST["Article_Nouveau"]));
	$res 		= $plxPlugin->NewDate($NewDate,$Libelle,$Texte,$Style,$Article);
	if($res == 1)
		plxMsg::Error(preg_replace("/<DATE>/",$NewDate,$plxPlugin->getlang('L_ERREUR_DATE')));

	// Maintenant qu'on a pris en compte les modifications sur les événements, on
	// s'intéresse aux styles.
	// On enregistre les changements sur les styles.
	// Si le nom d'un style change ou est détruit, on répercute ce changement sur les événements qui
	// utilisaient ce style.
	foreach($_POST as $Type => $ValeurDummy)
		if(preg_match("/StyleNom_.*/",$Type))
		{
			$Nom = substr($Type,9);
			if(isset($_POST["StyleDestroy_".$Nom]))
			{
				$plxPlugin->DestroyStyle(trim(plxUtils::strCheck($Nom)));
			}
			else
			{
				$NewNom 	= plxUtils::title2url(trim(plxUtils::strCheck($_POST["StyleNom_".$Nom])));
				$Valeur 	= trim(plxUtils::strCheck($_POST["StyleValeur_".$Nom]));
				$BGColor 	= trim(plxUtils::strCheck($_POST["StyleBGColor_".$Nom]));
				$TextColor 	= trim(plxUtils::strCheck($_POST["StyleTextColor_".$Nom]));
				$Legende 	= trim(plxUtils::strCheck($_POST["StyleLegende_".$Nom]));
				$plxPlugin->ChangeStyle($Nom,$NewNom,$BGColor,$TextColor,$Valeur,$Legende);
			}
		}
	
	$NewNom 	= plxUtils::title2url(trim(plxUtils::strCheck($_POST["StyleNom_Nouveau"])));
	$Valeur		= trim(plxUtils::strCheck($_POST["StyleValeur_Nouveau"]));
	$BGColor 	= trim(plxUtils::strCheck($_POST["StyleBGColor_Nouveau"]));
	$TextColor 	= trim(plxUtils::strCheck($_POST["StyleTextColor_Nouveau"]));
	$Legende 	= trim(plxUtils::strCheck($_POST["StyleLegende_Nouveau"]));
	$plxPlugin->NewStyle($NewNom,$BGColor,$TextColor,$Valeur,$Legende);

	$plxPlugin->saveCalendrierFile();

	header('Location: plugin.php?p='.$plxPlugin->getName());
	exit;
}

// Permet d'afficher une petite icone "détruire" accompagnée de son titre
function displayDetruire() {	echo '<a class="plnCalendrierDetruire" title="Détruire"/>'; }
?>

<div class="plnCalendrierAdmin">
<h2 id="title_config" class="hide"><?php echo $plxPlugin->getlang('L_ADMIN_TITLE')?> <sup>(v<?php echo $plxPlugin->getInfo("version");?>)</sup></h2>
<script type="text/javascript">//surcharge du titre dans l'admin
try{//pluxml 5.4+
 var title = document.getElementById('title_config').innerHTML;
 document.getElementsByClassName('inline-form')[0].firstChild.nextSibling.innerHTML = title;
}catch(e){console.log(e);}
</script>
<form action="plugin.php?p=<?php echo $plxPlugin->getName(); ?>" method="post">
<?php echo plxToken::getTokenPostMethod() ?>
    <ul id="tabs">
      <li><a href="#events"><?php $plxPlugin->lang('L_EVENEMENTS')?></a></li>
      <li><a href="#style"><?php $plxPlugin->lang('L_STYLES')?></a></li>
    </ul>
<div class="tabContent" id="style">
<noscript><h2><?php $plxPlugin->lang('L_STYLES')?></h2></noscript>
<div class="table-responsive">
<table id="plnCalendrierStyle">
<tr>
	<th><?php echo displayDetruire();?></th>
	<th></th>
	<th><?php $plxPlugin->lang('L_STYLE_NAME')?></th>
	<th><?php $plxPlugin->lang('L_BACKGROUNDCOLOR')?></th>
	<th><?php $plxPlugin->lang('L_TEXTCOLOR')?></th>
	<th><?php $plxPlugin->lang('L_CSS_COMMANDS')?></th>
	<th title="<?php $plxPlugin->lang('L_HELP_LEGEND')?>"><?php $plxPlugin->lang('L_LEGEND')?></th>
</tr>
<?php
	foreach($plxPlugin->Styles as $Nom => $Style)
	{
		$BGColor 	= $Style["BGColor"];
		$TextColor 	= $Style["TextColor"];
		$Valeur 	= $Style["Valeur"];
		$Legende 	= $Style["Legende"];
?>
<tr>
	<td><input type="checkbox" name="StyleDestroy_<?php echo $Nom;?>" value="True" /></td>
	<td>
		<div class="plnCalendrierExample <?php echo $Nom;?>"><?php $plxPlugin->lang('L_EXAMPLE')?></div>
	</td>
	<td><?php plxUtils::printInput("StyleNom_".$Nom,$Nom,'text','20-20'); ?></td>
	<td>
		<input 	id="id_StyleBGColor_<?php echo $Nom;?>" 
				name="StyleBGColor_<?php echo $Nom;?>" 
				type="color" 
				value="<?php echo $BGColor;?>"/>
	</td>
	<td>
		<input 	id="id_StyleTextColor_<?php echo $Nom;?>" 
				name="StyleTextColor_<?php echo $Nom;?>" 
				type="color" 
				value="<?php echo $TextColor;?>"/>
	</td>
	<td><?php plxUtils::printInput("StyleValeur_".$Nom,plxUtils::strRevCheck($Valeur),'text','40-555'); ?></td>
	<td><?php plxUtils::printInput("StyleLegende_".$Nom,plxUtils::strRevCheck($Legende),'text','30-100'); ?></td>
</tr>
<?php	
	}
?>
<tr>
	<td></td>
	<td></td>
	<td><?php plxUtils::printInput("StyleNom_Nouveau","",'text','20-20'); ?></td>
	<td><input id="id_StyleBGColor_Nouveau" name="StyleBGColor_Nouveau" type="color" value="#ffffff"/></td>
	<td><input id="id_StyleTextColor_Nouveau" name="StyleTextColor_Nouveau" type="color" value=""/></td>
	<td><?php plxUtils::printInput("StyleValeur_Nouveau","",'text','40-555'); ?></td>
	<td><?php plxUtils::printInput("StyleLegende_Nouveau","",'text','30-100'); ?></td>
</tr>
</table>
</div><!-- table-responsive -->
<p class="in-action-bar"><input type="submit" name="submit" value="<?php $plxPlugin->lang('L_SAVE') ?>" /></p>
</div><!-- #style -->
<div class="tabContent" id="events">
<noscript><h2><?php $plxPlugin->lang('L_EVENEMENTS')?></h2></noscript>
<div class="table-responsive">
<table id="plnCalendrierEvenements">
	

<tr>
	<th><?php echo displayDetruire();?></th>
	<th title="<?php $plxPlugin->lang('L_HELP_DATE')?>"><?php $plxPlugin->lang('L_DATE')?></th>
	<th><?php $plxPlugin->lang('L_LABEL')?></th>
	<th><?php $plxPlugin->lang('L_DESCRIPTION')?></th>
	<th title="<?php $plxPlugin->lang('L_HELP_STYLE')?>"><?php $plxPlugin->lang('L_STYLE')?></th>
	<th title="<?php $plxPlugin->lang('L_HELP_ARTICLE_ASSOCIE')?>"><?php $plxPlugin->lang('L_ASSOCIATED_ARTICLE')?></th>
</tr>
<?php
	// On construit l'array des styles
	$arrayStyle=array("" => "");
	foreach($plxPlugin->Styles as $Nom => $Style)
		$arrayStyle[$Nom] = $Nom;
?>

<tr>
	<td></td>
	<td><?php plxUtils::printInput("Date_Nouveau","",'text','10-10',false,'',$plxPlugin->getlang('L_YYYY-MM-DD')); ?></td>
	<td><?php plxUtils::printInput("Libelle_Nouveau","",'text','15-25'); ?></td>
	<td><?php plxUtils::printInput("Texte_Nouveau","",'text','40-255',false,'',$plxPlugin->getlang('L_INFOBULLE')); ?></td>
	<td><?php plxUtils::printSelect("Style_Nouveau",$arrayStyle,'',false,'select-style'); ?></td>
	<td>
		<select id="id_Article_Nouveau" name="Article_Nouveau" class="select-article">
		<?php echo $artList ?>
		</select>
	</td>
</tr>
<?php
	// On tri le tableau en ordre inverse
	$Keys = array_keys($plxPlugin->Calendrier);
	array_multisort($Keys,SORT_DESC,$plxPlugin->Calendrier);

	foreach($plxPlugin->Calendrier as $Date => $Jour)
		foreach($Jour as $NumEvent => $Event)
		{
			$Id = $Date."_".$NumEvent;
?>
<tr>
	<td><input type="checkbox" name="Destroy_<?php echo $Id;?>" value="True" /></td>
	<td><?php plxUtils::printInput("Date_".$Id,$Date,'text','10-10'); ?></td>
	<td><?php plxUtils::printInput("Libelle_".$Id,plxUtils::strRevCheck($Event['Libelle']),'text','15-25'); ?></td>
	<td><?php plxUtils::printInput("Texte_".$Id,plxUtils::strRevCheck($Event['Texte']),'text','40-255'); ?></td>
	<td><?php plxUtils::printSelect("Style_".$Id,$arrayStyle,plxUtils::strRevCheck($Event['Style']),false,'select-style'); ?></td>
	<td>
		<select id="id_Article_<?php echo $Id ?>" name="Article_<?php echo $Id ?>" class="select-article">
		<option value="<?php echo $Event['Article'] ?>" selected><?php echo $Event['Article'] ?></option>
		<?php echo $artList ?>
		</select>
	</td>
	<!--<td><?php plxUtils::printInput("Article_".$Id,plxUtils::strRevCheck($Event['Article']),'text','10-40'); ?></td>-->
</tr>
<?php	
		}
?>

</table>
</div><!-- table-responsive -->
<p class="in-action-bar"><input type="submit" name="submit" value="<?php $plxPlugin->lang('L_SAVE') ?>" /></p>
</div><!-- #events -->
<?php 

?>

</form>
</div>

<?php ############################ Le datePicker ############################ 

// On construit l'intervalle des années
$AnneeEnCours = new DateTime();
$Annee1 = $AnneeEnCours->format("Y") - 1;
$Annee2 = $Annee1 + 10;
?>
<script src="<?php echo PLX_PLUGINS ?>plnCalendrier/js/tabs.js"></script>
<script src="<?php echo PLX_PLUGINS ?>plnCalendrier/pikaday/moment.js"></script>
<script src="<?php echo PLX_PLUGINS ?>plnCalendrier/pikaday/pikaday.js"></script>
<script>
var a = document.getElementsByTagName("input");
for (var i=0;i<a.length;i++) {
	var id = a[i].id;
	if(id.substring(0,7)=='id_Date') {
		var picker = new Pikaday({ 
			field: document.getElementById(id),
			format: 'YYYY-MM-DD',
			yearRange: [<?php echo $Annee1 ?>,<?php echo $Annee2 ?>],
			i18n: {
				previousMonth : <?php echo $plxPlugin->getLang('PREVIOUS_MONTH') ?>,
				nextMonth     : <?php echo $plxPlugin->getLang('NEXT_MONTH') ?>,
				months        : [<?php echo $plxPlugin->getLang('MONTH_LIST') ?>],
				weekdays      : [<?php echo $plxPlugin->getLang('WEEKDAY_LIST') ?>],
				weekdaysShort : [<?php echo $plxPlugin->getLang('WEEKDAY_SHORT_LIST') ?>]
			}
		});
	}
}

</script>

