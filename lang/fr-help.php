<style>
    code 	{	margin-bottom:10px;border:1px solid black;background-color:#777;color:white;padding:5px;display:inline-block;}
    code strong { color:#DDAA00;}
	p 		{	text-align:justify;margin-bottom:5px;}
	h3		{	font-size: 1.2em;margin:10px 0 5px 0;color:#AA3388;}
	h4		{	margin:10px 0 5px 0;text-decoration:underline;}
	ul 		{	margin-left:15px;margin-bottom:5px;}
</style>

<div id="plnCalendrierAide">

<h2>Aide pour plnCalendrier</h2>

<p><strong>plnCalendrier</strong> permet de disposer d'un calendrier d'événements.</p>

<p>Pour fonctionner il requiert le plugin technique <strong>plnStaticPages</strong>, qui doit être installé et chargé en dernier
	dans l'ordre de chargement.</p>

<p>La page de configuration du plugin <strong>plnCalendrier</strong> vous permettra de paramétrer certaines fonctionnalités : 
	quel profil a le droit de gérer le calendrier, quel template utiliser ou s'il faut faire apparaitre un bouton "calendrier" 
	dans la barre de navigation	du site.</p>

<p>Pour disposer du double calendrier mensuel "en ce moment", que nous conseillons de placer dans la sidebar, 
il faut ajouter le code suivant dans le thème (par exemple dans sidebar.php) :</p>

<code>&lt;?php eval($plxShow-&gt;callHook('plnCalendrierCurrentMonth')); ?&gt;</code>

</div>
