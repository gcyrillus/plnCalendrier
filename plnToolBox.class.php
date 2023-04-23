<?php

/*
Ce fichier contient un ensemble d'outils transverses à divers plugins.
Ce n'est pas un plugin par lui-même et n'a donc aucune capacité à agir (par exemple) sur les hooks.
Pour simplifier, il s'agit d'un équivalent "maison" de plxUtils

Ces outils ayant vocation à être utilisés par plusieurs plugins, peuvent se poser des problématiques
de compatibilité entre plugins dès que plnToolBox évolue.
Afin d'éviter ces problèmes on respecte les règles suivantes :
- dès lors qu'une classe plnToolBox est utilisée, on ne rajoute ni ne retire de fonctionnalité. 
	On ne corrige même pas les bugs.
- dès qu'on veut faire une modification (ajout ou modification de fonctionnalité) on étend la classe
	mère (ou la plus récente classe fille) et on effectue les modifications dans la nouvelle classe fille. 
- toute classe plnToolBox (mère, filles, etc.) doit être "protégée" par un "defined" qui permet de 
	ne définir cette classe que si elle n'a pas déjà été définie.
- lorsqu'on veut utiliser les nouvelles fonctionnalités, on utilise la nouvelle classe fille.
- le plnToolBox.class.php est déposé à la racine des répertoires de tous les plugins. En développement, 
	on créé des liens (ln) de manière à ce que modifier 1 fichier modifie tous les fichiers à la fois
*/

# Si la toolbox a déjà été instanciée, on quitte.
if(!defined('PLNTOOLBOX_v1') && !class_exists('plnToolBox_v1')) {//fix 4 plnPrivatePage
define('PLNTOOLBOX_v1','v1');
class plnToolBox_v1
{
	##############################################################################################
	#
	# Cette méthode permet de déterminer si :
	# - un plugin "$plugin" est présent, activé et si ses pré-requis sont bien remplis
	# - optionnellement, s'il est bien lancé avant ($where="before") ou après ($where="after") un autre plugin $pluginReference 
	# Elle renvoit un booléen true or false
	#
	##############################################################################################
	public static $pluginList; // Tableau des plugins triés dans l'ordre de chargement, sous la forme $pluginList[Nom]=Ordre
	static public function checkPlugin($plugin,$pluginReference=false,$where="after")
	{
		# Si le fichier n'existe pas, rien n'existe (mais c'est douteux)
        if(is_file(path('XMLFILE_PLUGINS'))) 
        {
        	# Est-ce que la variable $pluginList est renseignée ?
        	# Si non on la créé
        	if(!isset(self::$pluginList) or !is_array(self::$pluginList))
        	{
        		self::$pluginList = array();
	            # Mise en place du parseur XML
	            $xml = new SimpleXMLElement(implode('',file(path('XMLFILE_PLUGINS'))));
	            # On récupère les plugins
	            $xmlplugins = $xml->xpath('/document/plugin');
	            foreach($xmlplugins as $xmlplugin)
	            	self::$pluginList[] = (string)$xmlplugin["name"];
	            self::$pluginList = array_flip(self::$pluginList);
        	}

        	# A cet endroit $pluginList est créé et connnu.
           	// Si le plugin demandé n'existe pas, on dit false
        	if(!isset(self::$pluginList[$plugin]))
        		return false;

			// Si le plugin de référence est fourni mais n'existe pas, on dit false
        	if($pluginReference !== false)
        	{
            	if(!isset(self::$pluginList[$pluginReference]))
        			return false;
            	// Si on demandait un ordre de chargement non respecté, on dit false
				if($where == "after" and self::$pluginList[$plugin] < self::$pluginList[$pluginReference])
					return false;

				if($where == "before" and self::$pluginList[$plugin] > self::$pluginList[$pluginReference])
					return false;

				// Si ce plugin ne remplit lui-même pas ses pré-requis, on dit false
				//if(method_exists($plugin,'areMyPreRequisitesMet') and !$plugin::areMyPreRequisitesMet())
				//	return false;
        	} 
        	// Si on est arrivé là, c'est que tout va bien
        	return true;
        }
        return false;
	}

	##############################################################################################
	#
	# Gestion d'une pile de message
	#
	##############################################################################################
	public static function addMsg($Msg,$Type="error")
	{
		if(!isset($_SESSION[__CLASS__])) 			$_SESSION[__CLASS__] 		=  array();
		if(!isset($_SESSION[__CLASS__][$Type])) 	$_SESSION[__CLASS__][$Type] =  array();
        $_SESSION[__CLASS__][$Type][] = $Msg;
	}

	// $Type contient le type exact de message à afficher, "true" pour tout afficher
	public static function displayMsg($Type=true)
	{
		if(isset($_SESSION[__CLASS__]))
		{
			if($Type == true)
			{
				foreach($_SESSION[__CLASS__] as $Type => $MsgList)
					echo '<p class="warning '.$Type.'">'."\n".implode("\t<br/>\n",$MsgList)."\n</p>\n";
				// On reset les messages;
				$_SESSION[__CLASS__] = array();
			}
			else
			{
				if(isset($_SESSION[__CLASS__][$Type]))
				{
					echo '<p class="warning '.$Type.'">'."\n";
					foreach($_SESSION[__CLASS__][$Type] as $MsgList)
						echo $Msg."\t<br/>\n";
					echo "</p>\n";
					// On reset les messages;
					$_SESSION[__CLASS__][$Type] = array();
				}
			}
		}
	}

	##############################################################################################
	#
	# Renvoit de l'instance de moteur actuellement utilisée (plxAdmin ou plxMotor)
	#
	##############################################################################################
	static function getMotorInstance()
	{
		return class_exists('plxAdmin') ? plxAdmin::getInstance() : plxMotor::getInstance();
	}

	##############################################################################################
	#
	# Ajoute un hook provenant d'une classe quelconque dans le moteur de pluXml
	# Cette méthode peut $etre appelée dès lors qu'un plxMotor ou plxAdmin est instancié
	#
	##############################################################################################
	static function addHook($hookname,$class,$userfunction)
	{
		if(method_exists($class, $userfunction)) 
		{
			$plxMotor = self::getMotorInstance();
			$plxMotor->plxPlugins->aHook[$hookname][]=array(
					'class'		=> $class,
					'method'	=> $userfunction
				);
//			var_dump($plxMotor->plxPlugins->aHooks);
		}
	}

	##############################################################################################
	#
	# Renvoie un texte html permettant d'afficher les boutons de paginations classiques de type
	# << < ... 5 10 14 15 16 20 25 ... > >>
	# On considère que la page minimum est forcément la "1"
	# $MaxPage 		: La page max
	# $CurrentPage 	: La page en cours
	# $UrlPattern	: Le pattern permettant d'accéder aux différentes pages
	#				  Dans le pattern __PAGE__ est remplacé par le numéro de page
	#
	# De chaque côté de la page en cours on aura le numéro le plus proche + à 5 + à 10
	# Exemple
	#
	##############################################################################################
	static function printPageNavA($UrlPattern,$Page)
	{
		echo '<a href="'.preg_replace("/__PAGE__/",$Page,$UrlPattern).'">'.$Page.'</a>';

	}
	static function printPageNav($CurrentPage=1,$MaxPage=1,$UrlPattern="?p=__CLASS__&page=__PAGE__")
	{
		if($MaxPage == 1) return;
		?>
		<nav class="plnToolBoxPageNav">
		<a href="<?php echo preg_replace("/__PAGE__/",1,$UrlPattern); ?>">&lt;&lt;</a>
		<a href="<?php echo preg_replace("/__PAGE__/",$CurrentPage == 1 ? '1' : $CurrentPage - 1,$UrlPattern); ?>">&lt;</a>
		<?php 
			if($CurrentPage > 10) 	{ self::printPageNavA($UrlPattern,$CurrentPage - 10); 	echo "&nbsp;"; 	}
			if($CurrentPage > 5) 	{ self::printPageNavA($UrlPattern,$CurrentPage - 5);	echo "&nbsp;";	}
			if($CurrentPage > 3) 	{ ?><span>...</span><?php }
			if($CurrentPage > 2) 	{ self::printPageNavA($UrlPattern,$CurrentPage - 2);	echo "&nbsp;";}
			if($CurrentPage > 1) 	{ self::printPageNavA($UrlPattern,$CurrentPage - 1);	echo "&nbsp;";}
		?>
		<span><?php echo $CurrentPage; ?></span>
		<?php 
			if($CurrentPage <= $MaxPage - 1) 	{ self::printPageNavA($UrlPattern,$CurrentPage + 1); 	echo "&nbsp;";} 
			if($CurrentPage <= $MaxPage - 2) 	{ self::printPageNavA($UrlPattern,$CurrentPage + 2); 	echo "&nbsp;";} 
			if($CurrentPage <= $MaxPage - 3) 	{ ?><span>...</span><?php }
			if($CurrentPage <= $MaxPage - 5) 	{ self::printPageNavA($UrlPattern,$CurrentPage + 5); 	echo "&nbsp;";} 
			if($CurrentPage <= $MaxPage - 10) 	{ self::printPageNavA($UrlPattern,$CurrentPage + 10); 	echo "&nbsp;";} 
		?>
		<a href="<?php echo preg_replace("/__PAGE__/",$CurrentPage == $MaxPage ? $MaxPage : $CurrentPage + 1,$UrlPattern); ?>">&gt;</a>
		<a href="<?php echo preg_replace("/__PAGE__/",$MaxPage,$UrlPattern); ?>">&gt;&gt;</a>
		</nav>
<?php
	}
}

}