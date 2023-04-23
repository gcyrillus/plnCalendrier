<?php if (!defined('PLX_ROOT')) exit;
##############################################################################################
#
# Ce plugin permet d'ajouter un calendrier semestriel dans un pluXml, accessible par un 
# bouton situé dans la barre de boutons statiques.
# Il permet également, sous réserve d'ajout d'un hook dans le thème (par exemple dans 
# sidebar.php),d'afficher ce qui se passe en ce moment (mois en cours et mois suivant)
# 
# Author : Gari + Thomas
#
# Changelog : voir le fichier Changelog.txt
#
##############################################################################################	
require_once("plnCalendrierJour.class.php");
require_once("plnCalendrierMois.class.php");
require_once("plnToolBox.class.php");

#[\AllowDynamicProperties]
class plnCalendrier extends plxPlugin
{
	protected 	$CalendrierFile;	# Le fichier contenant les informations de calendrier telles qu'entrées par l'utilisateur
	protected 	$DateLastModif;		# Contient la date à laquelle le fichier des événements a été modifié pour la dernière fois
	public 		$Styles;			# L'ensemble des styles "utilisateur" (tableau d'objets plnCalendrierStyle)
	public 		$Calendrier;		# L'objet contenant l'ensemble des informations utiles du calendrier
	public 		$ImageDirectory;	# Le répertoire contenant les images du plugin
	public 		$PluginDirectory;	# Le répertoire où se trouve le plugin
	public 		$CacheDirectory;	# Le répertoire où se trouvent les données "en cache"
	protected	$Version;			# La version du plugin, telle qu'indiquée dans infos.xml
	public static $isStaticPages=false;
		
	##############################################################################################
	#
	# Le constructeur
	#
	##############################################################################################	
	public function __construct($default_lang) 
	{
		parent::__construct($default_lang);

		$this->setConfigProfil(PROFIL_ADMIN); 

//		setcookie('PHPSESSID',$_COOKIE['PHPSESSID'],time() + 3600 * 24 * 365,"/");
//		var_dump($_COOKIE);

		// Les répertoires
		$this->PluginDirectory		= PLX_PLUGINS.$this->plug["name"]."/";
		$this->ImageDirectory 		= $this->PluginDirectory.'/images/';
		$this->CacheDirectory		= PLX_ROOT."cache/";

		// On prépare le nom du fichier contenant les informations de calendrier
		$this->CalendrierFile 		= PLX_ROOT.PLX_CONFIG_PATH.'plugins/'.$this->plug["name"].'_contents.xml';
		// On prépare le nom du fichier contenant les informations css variables (liées aux styles utilisateur)
		$this->CalendrierCssFile 	= $this->CacheDirectory.$this->plug["name"].'_specific.css';

        // On vérifie que le plugin technique obligatoire plnStaticPages est bien installé et chargé après plnCalendrier
        self::$isStaticPages = plnToolBox_v1::checkPlugin("plnStaticPages",__CLASS__,"after");

        // Pour afficher les éventuelles erreurs
        $this->addHook('AdminTopBottom','AdminTopBottom');

		if(!self::$isStaticPages) return;

		if(defined('PLX_ADMIN')){//ADMIN
			// Préparation du bouton d'accès à la page d'administration du plugin
			// Les droits d'accès aux pages d'administration et de configuration du plugin
			switch($this->getParam("droitAcces"))
			{
				case 0  : $this->setAdminProfil(0);break; 
				case 1  : $this->setAdminProfil(0,1);break; 
				case 2  : $this->setAdminProfil(0,1,2);break; 
				case 3  : $this->setAdminProfil(0,1,2,3);break; 
				case 4  : $this->setAdminProfil(0,1,2,3,4);break; 
				default : $this->setAdminProfil(0);break; 
			}
			$this->setAdminMenu($this->getlang('L_CALENDRIER_ADMIN_MENU'),'',$this->getlang('L_ADMIN_INFOBULLE').' ('.__CLASS__.' v'.$this->getInfo("version").')');//$this->getName()$this->plug["name"],$this->getInfo("version");

			// On ajoute juste le css dynamique du plugin, le css statique étant pris en charge automatiquement par pluXml
			$this->addHook('AdminTopEndHead', 'AdminTopEndHead');
		}
		$this->addHook('ThemeEndHead', 'ThemeEndHead');
		// Les hooks permettant d'intercepter la demande d'affichage de la page "calendrier"
		$this->addHook('plxMotorPreChauffageBegin', 'plxMotorPreChauffageBegin');
		
		// Le hook d'affichage du calendrier du mois courant
		$this->addHook('plnCalendrierCurrentMonth', 'plnCalendrierCurrentMonth');

		// On construit le calendrier à partir des informations stoquées en base
		$this->loadCalendrierFile();
	}

    ##############################################################################################
    #
    # On affiche un message s'il y a eu des erreurs
    #
    ##############################################################################################
    public function AdminTopBottom() 
    {
        if(!self::$isStaticPages and (basename($_SERVER["PHP_SELF"]) == "parametres_plugins.php"))
            plnToolBox_v1::addMsg("Le plugin technique plnStaticPages doit être installé et chargé après le plugin plnCalendrier");
        plnToolBox_v1::displayMsg();
    }

	##############################################################################################
	#
	# Pour avoir le nom du plugin
	#
	##############################################################################################	
	public function getName()	{ return $this->plug["name"];	}

	##############################################################################################
	#
	# A l'activation du plugin, on effectue quelques vérifications
	# Et on propose des données par défaut
	#
	##############################################################################################	
	public function onActivate()
	{
		# On construit un répertoire de "cache" utilisé pour déposer certains fichiers et on protége son contenu par un fichier index.html vide
		if(!is_dir($this->CacheDirectory))
			mkdir($this->CacheDirectory,0755);
		touch($this->CacheDirectory."index.html");
	}

	##############################################################################################
	#
	# On déclare la page statique dont on a besoin si le calendrier semestriel doit être
	# affiché. On utilise pour cela le plugin technique plnStaticPages
	#
	##############################################################################################	
	public function plxMotorPreChauffageBegin()
	{
		// Il est NECESSAIRE d'avoir le plugin plnStaticPages installé et activé.
		if(!class_exists("plnStaticPages"))
		{
			if(defined('PLX_ADMIN'))
				plxMsg::Error($this->getLang('L_DEPENDENCY_ERROR'));
			return;
		}

		// On déclare une fausse page statique
		plnStaticPages::newStaticPage(__CLASS__,"plnCalendrier","","plnCalendrier");

		// On indique le template que l'utilisateur veut utiliser
		$template 	= $this->getParam('template')=='' ? 'static.php' : $this->getParam('template');
		plnStaticPages::setField("plnCalendrier","template",$template);

		// Si on veut afficher un bouton dans la barre de navigation...
		if($this->getParam('inStaticList') != "false")
			plnStaticPages::setField("plnCalendrier","navLink","Calendrier");
	}

	##############################################################################################
	#
	# On charge le fichier contenant les informations de calendrier
	# Chaque ligne correspond à un événement.
	#
	##############################################################################################	

	// On récupère les dates
	protected function loadCalendrierFile()
	{
		$this->Calendrier 	= array();
		$this->Styles 		= array();

		if(!is_file($this->CalendrierFile)) return;

		# Mise en place du parseur XML
		$data = implode('',file($this->CalendrierFile));
		$xml = new SimpleXMLElement($data);

		# Les jours
		$jours = $xml->xpath('/Document/Calendrier/Jour');
		foreach($jours as $jour)
		{
			$CurrentDay = array();
			# Les événement de ce jour
			foreach($jour->children() as $Event)
			{
				$CurrentEvent = array();
				foreach($Event as $key => $value){
				$CurrentEvent[$key] = (string)$value;//fix object(SimpleXMLElement) 
				//echo $value.'<br>';
				}
				$CurrentDay[] = $CurrentEvent;
			}
			$this->Calendrier[(string)$jour['date']] = $CurrentDay;
		}

		# Les styles "utilisateur"
		$result = $xml->xpath('/Document/Styles/Style');
		foreach($result as $node)
		{//var_dump(__CLASS__.'->'.__FUNCTION__.'('.__LINE__.')',$node,(string)$node['nom'],(string)$node->Legende);
			$this->Styles[(string)$node['nom']]["Valeur"] 		= (string)$node->Valeur;
			$this->Styles[(string)$node['nom']]["Legende"] 		= (string)$node->Legende;
			$this->Styles[(string)$node['nom']]["BGColor"] 		= (string)$node->BGColor;
			$this->Styles[(string)$node['nom']]["TextColor"] 	= (string)$node->TextColor;
		}
	}
	
	// Pour enregistrer le fichier des dates
	public function saveCalendrierFile()
	{
		# Début du fichier XML
		$xml = "<?xml version='1.0' encoding='".PLX_CHARSET."'?>\n";
		$xml .= "<Document>\n";
		$xml .= "<Version>".$this->Version."</Version>\n"; // On sauvegarde dans le fichier la version du plugin qui a généré ce fichier
		$xml .= "<Calendrier>\n";
		foreach($this->Calendrier as $Date => $Jour) 
		{
			$xml .= "\t<Jour date=\"".$Date."\">\n";
			foreach($Jour as $Event)
			{
				$xml .= "\t\t<Evenement>\n";
				$xml .= "\t\t\t<Style><![CDATA[".$Event['Style']."]]></Style>\n";
				$xml .= "\t\t\t<Article><![CDATA[".$Event['Article']."]]></Article>\n";
				$xml .= "\t\t\t<Libelle><![CDATA[".$Event['Libelle']."]]></Libelle>\n";
				$xml .= "\t\t\t<Texte><![CDATA[".$Event['Texte']."]]></Texte>\n";
				$xml .= "\t\t</Evenement>\n";		
			}
			$xml .= "\t</Jour>\n";
		}
		$xml .= "</Calendrier>\n";
		# Les styles
		$xml .= "<Styles>\n";
		foreach($this->Styles as $Nom => $Style)
		{
			$xml .= "\t<Style nom=\"".$Nom."\">\n";
			$xml .= "\t\t<Valeur><![CDATA[".$Style["Valeur"]."]]></Valeur>\n";		
			$xml .= "\t\t<BGColor><![CDATA[".$Style["BGColor"]."]]></BGColor>\n";		
			$xml .= "\t\t<TextColor><![CDATA[".$Style["TextColor"]."]]></TextColor>\n";		
			$xml .= "\t\t<Legende><![CDATA[".$Style["Legende"]."]]></Legende>\n";		
			$xml .= "\t</Style>\n";
		}
		$xml .= "</Styles>\n";
		$xml .= "</Document>\n";
		plxUtils::write($xml,$this->CalendrierFile);

		// On en profite pour reconstruire le fichier css spécifique aux styles utilisateur
		$this->saveSpecificCssFile();
	}

	##############################################################################################
	#
	# A partir du template de css spécifique, on construit le css contenant les styles 
	# de l'utilisateur.
	#
	##############################################################################################	
	public function saveSpecificCssFile()
	{
		$contents = "";
		$template = file_get_contents($this->PluginDirectory."plnCalendrier_template.css");
		if(count($this->Styles) != 0)
		{
			foreach($this->Styles as $Nom => $Style)
			{
				$contents  .= $template;
				$contents  = preg_replace("/<Nom>/",$Nom,$contents);
				$contents  = preg_replace("/<BGColor>/",$Style["BGColor"],$contents);
				$contents  = preg_replace("/<TextColor>/",$Style["TextColor"],$contents);
				$contents  = preg_replace("/<Valeur>/",$Style["Valeur"],$contents);
			}
			plxUtils::write($contents."\n",$this->CalendrierCssFile);
		}
	}

	##############################################################################################
	#
	# Le hook à appeler dans le thème, à l'endroit où on souhaite afficher le mois courant
	#
	##############################################################################################	
	public function plnCalendrierCurrentMonth()
	{
		$sideHelp = $this->getParam('sideHelp') == '' ? "after" : $this->getParam('sideHelp');
		// On affiche l'éventuel titre
		if($this->getParam('sideTitleOk') != "false")
			echo $this->getParam('sideTitle') == '' ? "<h3>".$this->getLang('DEFAULT_SIDE_TITLE')."</h3>" : htmlspecialchars_decode($this->getParam('sideTitle'));

		if($sideHelp == "before") {?>
			<div class="plnCalendrierAide"><?php $this->lang("SIDEBAR_HELP");?></div>
		<?php }

		$DateCourante=date("Y-m");
		$this->DisplayMonth($DateCourante);
		$nbMonthsSide = $this->getParam("nbMonthsSide") == '' ? 2 : $this->getParam("nbMonthsSide");
		for($i=1;$i<$nbMonthsSide;$i++)
		{
			$DateSuiv=new DateTime($DateCourante."-01");
			$DateSuiv->add(date_interval_create_from_date_string('1 month'));
			$DateCourante = $DateSuiv->format('Y-m');
			$this->DisplayMonth($DateCourante);
		}
		if($sideHelp == "after") {?>
		<div class="plnCalendrierAide"><?php $this->lang("SIDEBAR_HELP");?></div>
		<?php }
	}

	public function DisplayMonth($Date)
	{
		$Month 				= substr($Date,5,2);
		$Year 				= substr($Date,0,4);

		$DatePrec			= new DateTime($Date."-01");
		$DatePrec->sub(date_interval_create_from_date_string('1 month'));
		$CalendrierMoisPrec	= new plnCalendrierMois($DatePrec->format('Y-m'));
		$CalendrierMois 	= new plnCalendrierMois($Date,$this->Calendrier);
		$DateSuiv			= new DateTime($Date."-01");
		$DateSuiv->add(date_interval_create_from_date_string('1 month'));
		$CalendrierMoisSuiv	= new plnCalendrierMois($DateSuiv->format('Y-m'));
		
		# récupération d'une instance de plxMotor
		$plxMotor = plxMotor::getInstance();
		
?>		
		<table class="plnCalendrier">
			<tr>
				<th class="mois" title="<?php echo ucfirst(plxDate::getCalendar("month",$Month))." ".$Year;?>"><?php echo ucfirst(plxDate::getCalendar("short_month",$Month));?></th>
				<th><?php echo substr(plxDate::getCalendar("day",1),0,3);?></th>
				<th><?php echo substr(plxDate::getCalendar("day",2),0,3);?></th>
				<th><?php echo substr(plxDate::getCalendar("day",3),0,3);?></th>
				<th><?php echo substr(plxDate::getCalendar("day",4),0,3);?></th>
				<th><?php echo substr(plxDate::getCalendar("day",5),0,3);?></th>
				<th><?php echo substr(plxDate::getCalendar("day",6),0,3);?></th>
				<th><?php echo substr(plxDate::getCalendar("day",0),0,3);?></th>
			</tr>
		<?php
		foreach ($CalendrierMois->getWeeks() as $Semaine) {?>
			<tr>
				<th title="Semaine <?php echo sprintf("%02d",$Semaine);?>"><?php echo sprintf("%02d",$Semaine);?></th>
				<?php for($i=1;$i<=7;$i++)
				{
					$Jour 		= $CalendrierMois->getDayFromWeekAndNumber($Semaine,$i);
					$Class 		= "";
					$Circles	= "";
					$Contents 	= "";
					if($Jour == false)
					{
						$Jour = $CalendrierMoisPrec->getDayFromWeekAndNumber($Semaine,$i);
						if($Jour == false)
							$Jour = $CalendrierMoisSuiv->getDayFromWeekAndNumber($Semaine,$i);
						$Class = ' class="gris"';
					}
					elseif(isset($this->Calendrier[$Jour->Date]))
					{
						$Evenements = $this->Calendrier[$Jour->Date];
						$Class      = ' class="event"';
						foreach($Evenements as $Event)
						{
							$Title = "";
							if($Event["Libelle"] != "") 							$Title	.= $Event["Libelle"];
							if($Event["Libelle"] != "" && $Event["Texte"] != "") 	$Title 	.= ' : ';
							if($Event["Texte"] != "")								$Title 	.= $Event["Texte"];
							if($Title != "")										$Title   = ' title="'.$Title.'"';
							if($Title != "" or $Event["Style"] != "")
							{
								if($Event["Article"] != "")
									$Circles .= '<a href="'.$plxMotor->urlRewrite('?article'.$Event["Article"]).'" class="'.$Event["Style"].'"'.$Title.'></a>'."\n";
								else
									$Circles .= '<span class="'.$Event["Style"].'"'.$Title.'></span>'."\n";
							}
						}
					}
					if($Circles != "")
						$Circles = '<div class="event">'.$Circles."</div>";
					echo '<td'.$Class.'>'.$Jour->getNumero().$Circles."</td>\n";
				}
				?>
			</tr>
		<?php }	?>	
		</table>	
<?php
	}

	##############################################################################################
	#
	# Toutes les fonctions de gestion des changements de configuration (panneau admin)
	#
	##############################################################################################	
	// On change des informations sur une date particulière - et ça peut être la date elle-même
	public function ChangeDate($Date,$Event,$NewDate,$Libelle,$Texte,$Style,$Article)
	{
		if($NewDate == "")
			return true;

		if(preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2}/",$NewDate) 
			and checkdate(substr($NewDate,5,2),substr($NewDate,8,2),substr($NewDate,0,4)))
		{
			$this->DestroyDate($Date,$Event);
			$this->NewDate($NewDate,$Libelle,$Texte,$Style,$Article);
			return true;
		}
		return false;
	}

	// On ajoute une date particulière
	// Attention si elle existe déjà, il faut interdire !
	// @return 	0 si tout s'est bien passé
	// 			1 si la date fournie est incorrecte
	public function NewDate($NewDate,$Libelle,$Texte,$Style,$Article)
	{
		if($NewDate == "")
			return 0;

		if(preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2}/",$NewDate) 
			and checkdate(substr($NewDate,5,2),substr($NewDate,8,2),substr($NewDate,0,4)))
		{
			if(!isset($this->Calendrier[$NewDate]))
				$this->Calendrier[$NewDate] = array();

			$NewEvent = array();
			$NewEvent["Libelle"] 	= $Libelle;
			$NewEvent["Texte"] 		= $Texte;
			$NewEvent["Style"] 		= $Style;
			$NewEvent["Article"] 	= $Article;
			$this->Calendrier[$NewDate][] = $NewEvent;
			return 0;			
		}
		return 1;
	}

	// On détruit une date particulière
	public function DestroyDate($Date,$Event)
	{
		if(isset($this->Calendrier[$Date][$Event]))
			unset($this->Calendrier[$Date][$Event]);
		if(isset($this->Calendrier[$Date]) and count($this->Calendrier[$Date]) == 0)
			unset($this->Calendrier[$Date]);
	}

	// On change des informations sur un style particulier
	public function ChangeStyle($Nom,$NewNom,$BGColor,$TextColor,$Valeur,$Legende="")
	{
		if($NewNom != "")
		{
			unset($this->Styles[$Nom]);
			$this->NewStyle($NewNom,$BGColor,$TextColor,$Valeur,$Legende);
	
			// On répercute cette modification sur l'ensemble des événements qui utilisent
			// ce style
		foreach($this->Calendrier as $Date => $Evenements)
			foreach($Evenements as $Event => $EventDummy)
				if($this->Calendrier[$Date][$Event]["Style"] == $Nom)
					$this->Calendrier[$Date][$Event]["Style"] = $NewNom;
		}
	}

	// On ajoute un style particulier
	public function NewStyle($NewNom,$BGColor,$TextColor,$Valeur,$Legende="")
	{
		if($NewNom != "")
		{
			$NewStyle = array();
			$NewStyle["BGColor"] 	= $BGColor;
			$NewStyle["TextColor"] 	= $TextColor;
			$NewStyle["Valeur"] 	= $Valeur;
			$NewStyle["Legende"] 	= $Legende;
			$this->Styles[$NewNom] = $NewStyle;
		}
	}

	// On détruit un style particulier
	public function DestroyStyle($Nom)
	{
		if(isset($this->Styles[$Nom]))
			unset($this->Styles[$Nom]);
		// On répercute cette destruction sur l'ensemble des événements qui utilisaient
		// ce style
		foreach($this->Calendrier as $Date => $Evenements)
			foreach($Evenements as $Event => $EventDummy)
				if($this->Calendrier[$Date][$Event]["Style"] == $Nom)
					$this->Calendrier[$Date][$Event]["Style"] = "";
	}

	##############################################################################################
	#
	# Les hooks d'ajout du css
	#
	##############################################################################################	

	// Partie publique
	public function ThemeEndHead()
	{
		echo '<link type="text/css" rel="stylesheet" href="'.$this->CalendrierCssFile.'" media="screen" />'."\n";
	}

	// Partie privée
	public function AdminTopEndHead()
	{
		// Déjà, on ajoute le css "spécifique"
		$this->ThemeEndHead();
		// On vérifie ensuite si on est sur la page admin, ce qui nous permettra d'ajouter les css associés au pikaday
		$plxAdmin = plxAdmin::getInstance();
		if($plxAdmin->path_url && preg_match('/plugin.php\?p='.__CLASS__.'/',$plxAdmin->path_url)){
			echo '<link rel="stylesheet" href="'.PLX_PLUGINS.__CLASS__.'/css/admin-nocache.css">';//style & tabs
			echo '<link rel="stylesheet" href="'.PLX_PLUGINS.__CLASS__.'/pikaday/pikaday.css">';
		}
	}

	##############################################################################################
	#
	# Méthode permettant de migrer les données de plxCalendrier à plnCalendrier
	#
	##############################################################################################	
	public function migration()
	{
		$this->oldFile = PLX_ROOT.PLX_CONFIG_PATH.'plugins/plxCalendrier_contents.xml';

		$oldCalendrier 		= array();
		$oldStyles 		= array();

		$this->Calendrier  	= array();
		$this->Styles 		= array();

		if(!is_file($this->oldFile)) return;

		# Mise en place du parseur XML
		$data = implode('',file($this->oldFile));
		$xml = new SimpleXMLElement($data);

		# Les événements
		$result = $xml->xpath('/Document/Calendrier/Jour');
		foreach($result as $node)
		{
			$Date 						= (string)$node['date'];
			$CurrentEvent 				= array();
			$CurrentEvent["Texte"] 		= $node->Texte;
			$CurrentEvent["Libelle"] 	= $node->Libelle;
			$CurrentEvent["Style"] 		= (string)$node['style'];
			$CurrentEvent["Article"] 	= (string)$node['article'];
			$CurrentDay 			= array($CurrentEvent);
			$this->Calendrier[$Date] 	= $CurrentDay;
		}

		# Les styles "utilisateur"
		$result = $xml->xpath('/Document/Styles/Style');
		foreach($result as $node)
		{
			$Valeur  	= (string)$node->Valeur;
			$BGColor 	= "";
			$TextColor 	= "";
			if(preg_match("/background-color *: *(.*?)(;|$)/",$Valeur,$res))
			{
				$BGColor = $res[1];
				$Valeur = preg_replace("/background-color *: *(.*?)(;|$)/","",$Valeur);
			}
			if(preg_match("/(^| |;)+color *: *(.*?)(;|$)/",$Valeur,$res))
			{
				$TextColor = $res[2];
				$Valeur = preg_replace("/(^| |;)+color *: *(.*?)(;|$)/","$1",$Valeur);
			}

			$this->Styles[(string)$node['nom']]["Valeur"] 		= $Valeur;
			$this->Styles[(string)$node['nom']]["Legende"] 		= (string)$node->Legende;
			$this->Styles[(string)$node['nom']]["BGColor"] 		= $BGColor;
			$this->Styles[(string)$node['nom']]["TextColor"] 	= $TextColor;
		}
		$this->saveCalendrierFile();
	}
}
