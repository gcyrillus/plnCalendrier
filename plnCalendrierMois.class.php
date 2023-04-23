<?php

if (!defined('PLX_ROOT')) exit;

class plnCalendrierMois
{
	protected $Tableau;				# Tableau d'objets plnCalendrierJour trié en ordre chronologique croissant
	protected $Year;				# L'année à laquelle le mois appartient
	protected $Month;				# Le numéro du mois
	protected $SemaineEtNumero;		# Tableau de tableaux d'objets plnCalendrierJour triés par semaine puis numéro d'apparition dans la semaine
	
	// La date est fournie sous la forme "o-m" (YYYY-MM)
	public function __construct($Date)
	{
		$this->Tableau 			= array();
		$this->SemaineEtNumero 	= array();
		$this->Year 			= substr($Date,0,4);
		$this->Month			= substr($Date,5,2);
		$CurrentDay				= 1;
		
		while(checkdate($this->Month,$CurrentDay,$this->Year)) 
		{
			$this->addJour($Date."-".sprintf("%02d",$CurrentDay));
			$CurrentDay++;
		}
	}
	
	protected function addJour($Date)
	{
		$Jour 				= new plnCalendrierJour($Date);
		if($Jour->Semaine == "53") // La semaine 53 correspond en fait à la semaine 1 de l'année suivante
			$Jour->Semaine = "01";
		$this->Tableau[] 	= $Jour;
		if(!isset($this->SemaineEtNumero[$Jour->Semaine]))
			$this->SemaineEtNumero[$Jour->Semaine] = array();
		$this->SemaineEtNumero[$Jour->Semaine][$Jour->NumeroDansSemaine] = $Jour;
	}
	
	// A partir du numéro de semaine (par exemple 44) et du  numéro du jour (par exemple 2 pour mardi) on récupère le jour correspondant
	public function getDayFromWeekAndNumber($Semaine,$Numero)
	{
		$Semaine = sprintf("%02d",$Semaine);
		if(isset($this->SemaineEtNumero[$Semaine][$Numero]))
			return $this->SemaineEtNumero[$Semaine][$Numero];
		return false;
	}
	
	// On récupère un array contenant toutes les semaines du mois
	public function getWeeks()
	{
		return array_keys($this->SemaineEtNumero);
	}

	// On récupère un array contenant toutes les jours du mois
	public function getDays()	
	{
		return $this->Tableau;
	}

}

