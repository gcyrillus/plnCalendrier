<?php
	if(!defined('PLX_ROOT')) exit;

	$plxMotor  = plxMotor::getInstance();
	$plxPlugin = $plxMotor->plxPlugins->aPlugins["plnCalendrier"];

	// On récupère le nombre de mois qui doivent constituer le calendrier
	// 2, 3, 4 ou 6
	$nbMonths	= $plxPlugin->getParam('nbMonths') == '' ? 6 : (int)$plxPlugin->getParam('nbMonths');

	// On récupère la date si elle est demandée, sinon on prend la date du jour
	$Date = isset($_GET["date"]) ? plxUtils::strCheck($_GET["date"]) : date("Y-m");;

	$Month 	= (int)substr($Date,5,2);
	$Year 	= substr($Date,0,4);

	// Suivant le nombre de mois consécutifs qui doivent s'afficher sur le calendrier,
	// on définit dans quel intervalle nous sommes.

	//Pour connaitre le mois de départ :
	$Month = 1+($nbMonths*(int)(($Month-1)/$nbMonths));

	# On affiche le titre et les boutons de déplacement
	if($Month == 1) 
	{
		$DatePrecedente = sprintf("%04d",$Year-1)."-".sprintf("%02d",13-$nbMonths);
		$DateSuivante 	= $Year."-".sprintf("%02d",1+$nbMonths);
	}
	elseif($Month + $nbMonths > 12)
	{
		$DatePrecedente = $Year."-".sprintf("%02d",13-2*$nbMonths);
		$DateSuivante 	= sprintf("%04d",$Year+1)."-01";
	}
	else
	{
		$DatePrecedente = $Year."-".sprintf("%02d",$Month-$nbMonths);
		$DateSuivante 	= $Year."-".sprintf("%02d",$Month+$nbMonths);
	}

	// Sélection du style
	$skin = $plxPlugin->getParam('style') == '' ? 'azur' : $plxPlugin->getParam('style');

?>
	<table class="plnCalendrierNav <?php echo $skin; ?>">
		<tr>
			<td colspan="2">
				<a href="?plnCalendrier&date=<?php echo $DatePrecedente ?>" title="<?php $plxPlugin->lang("Intervalle précédent");?>">&nbsp;</a>
			</td>
			<td colspan="2">
				<a href="?plnCalendrier" title="<?php $plxPlugin->lang("Intervalle en cours");?>">&nbsp;</a>
				<?php echo $Year; ?>
				</td>
			<td colspan="2">
				<a href="?plnCalendrier&date=<?php echo $DateSuivante ?>" title="<?php $plxPlugin->lang("Intervalle suivant");?>">&nbsp;</a>
			</td>
		</tr>
	</table>
	<div class="plnCalendrierAide"><?php $plxPlugin->lang("CALENDAR_HELP");?></div>
<?php
# On affiche les mois demandés
$DateTemp = new DateTime();
for($i=0;$i<$nbMonths;$i++)
{
	$CalendrierMois = new plnCalendrierMois($Year."-".sprintf("%02d",$Month),$plxPlugin->Calendrier);
?>
	<table class="plnCalendrierMonth plnCalendrierSize<?php echo $nbMonths?> <?php echo $skin ?>">
		<tr>
			<th colspan="3"><?php echo ucfirst(plxDate::getCalendar("month",sprintf("%02d",$Month)));?></th>
		</tr>
<?php
			foreach ($CalendrierMois->getDays() as $Day) 
			{
				$LienAvant  = "";
				$LienApres  = "";
				$Class		= "";
				$Title		= "";
				$LibelleEvenement = "";
				$LibelleJour = strtoupper(substr($Day->Libelle,0,1));
				if($LibelleJour == "S" or $LibelleJour == "D")
					$Class = ' class="weekend"';

				echo '<tr'.$Class.'><td>'.$Day->getNumero()."</td><td>".$LibelleJour."</td>";

				$Contents = "";
				if(isset($plxPlugin->Calendrier[$Day->Date]))
				{
					$Jour = $plxPlugin->Calendrier[$Day->Date];
					foreach($Jour as $Event)
					{
						$Title 	= ' title="'.$Event["Texte"].'"';
						if($Event["Article"] != "")
						{
							$LienAvant 	= '<a class="event '.$Event["Style"].'" href="'.$plxMotor->urlRewrite('?article'.$Event["Article"]).'/"'.$Title.'>';
							$LienApres 	= '</a>';
						}
						else
						{
							$LienAvant 	= '<span class="event '.$Event["Style"].'"'.$Title.'>';
							$LienApres 	= '</span>';
						}
						$LibelleEvenement = $Event["Libelle"];
						$Contents .= $LienAvant.$LibelleEvenement.$LienApres;
					}
				}

				echo '<td>'.$Contents.'</td></tr>'."\n";
			}
			echo "</table>\n";
			$Month++;
		}

		// On affiche la légende, si elle existe
		$isLegende=false;
		foreach($plxPlugin->Styles as $Style)
			if($Style["Legende"] != "")
			{
				echo '<ul class="event">'."\n";
				foreach($plxPlugin->Styles as $Nom => $Style)
					if($Style["Legende"] != "")
						echo "\t".'<li><div class="'.$Nom.'"></div>'.$Style["Legende"]."</li>\n";
				echo "</ul>\n";
				break;				
			}

