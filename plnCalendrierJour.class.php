<?php

if (!defined('PLX_ROOT')) exit;

class plnCalendrierJour 
{
	public $Date;				# Date au format YYYY-MM-DD
	public $Semaine;			# A quel numéro de semaine (1 à 52) appartient ce jour ?
	public $Libelle;			# Le nom du jour : lun, mar, mer, jeu, ven, sam, dim
	public $TimeStamp;			# Le timestamp UNIX de la date
	public $NumeroDansSemaine;	# Quel est le numéro d'apparition de ce jour dans la semaine, 0 pour lundi et 7 pour dimanche
	
	public function __construct($Date="")
	{
		$Day 						= substr($Date,8,2);
		$Month 						= substr($Date,5,2);
		$Year 						= substr($Date,0,4);
		$this->Date					= $Date;
		$this->TimeStamp			= mktime(0,0,0,$Month,$Day,$Year);
		$this->NumeroDansSemaine 	= date("w",$this->TimeStamp);
		$this->Libelle 				= plxDate::getCalendar("day",$this->NumeroDansSemaine);
		$this->Semaine				= sprintf("%02d",date("W",$this->TimeStamp));
		// On modifie le numéro dans la semaine de manière à ce qu'il convienne au format "lundi à dimanche", ce qui donne 1 à 7
		if($this->NumeroDansSemaine == 0)
			$this->NumeroDansSemaine = 7;
	}
	
	public function getNumero()
	{
		return substr($this->Date,8,2);
	}
	
}
