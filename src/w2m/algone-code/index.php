<?php
include (dirname(__FILE__).'/teipot/Teipot.php');
$pathinfo=Web::pathinfo();
$baseHref=str_repeat("../", substr_count($pathinfo, '/'));
$html='
  <ul class="cat">
    <a href="'.$baseHref.'odt2tei/"><li><h2>ODT > TEI</h2><p>Convertissez vos documents bureautique en TEI</p></li></a>
    <a href="'.$baseHref.'teipub"><li><h2>TEI > HTML</h2><p>Convertissez vos documents TEI en HTML</p></li></a>
    <a href="'.$baseHref.'teibook/index.html"><li><h2>Teibook</h2><p>Un schéma TEI pour les livres</p></li></a>
    <a href="'.$baseHref.'ead/"><li><h2>EAD DOC</h2><p>Consulter la documentation EAD</p></li></a>
    <a href="'.$baseHref.'xmlstats/"><li><h2>XML STATS</h2><p>Analysez le contenu d’un document XML</p></li></a>
    <a href="'.$baseHref.'xrem/"><li><h2>XREM</h2><p>Visualisez vos schémas Relax-NG en HTML</p></li></a>
    <a href="'.$baseHref.'littre/dictionnaire"><li><h2>Littré</h2><p>Consultez le Littré lemmatisé</p></li></a>
  </ul>
</section>
';
list($branch1, $branch2)=explode ( "/" , $pathinfo.'//' );
$php=$classname=null;

if ( !$pathinfo);
else if ( file_exists($php=$branch1."/index.php")) $classname=$branch1;
else if ( file_exists($php=$pathinfo.".php")) $classname=$pathinfo;
else if ( file_exists($php=$pathinfo."/index.php")) $classname=rtrim($pathinfo, "/");
else if ( file_exists($php=$branch1.".php")) $classname=$branch1;
else $php='';
if ($php) {
  ob_start();
  include $php;
  $html = ob_get_contents();
  ob_end_clean();
}
if(!class_exists($classname))$classname=null;
?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="utf-8"/>
    <link rel="stylesheet" href="<?php echo $baseHref; ?>algone.css"/>
    <?php
if ($classname && method_exists($classname,'head')) call_user_func(array(__NAMESPACE__ .$classname, 'head'));
else echo '
    <title>Algone</title>
';
?>
  </head>
  <body class="fixed">
    <header id="top">
      <h1 id="logo"><a href="<?php echo $baseHref?>"><img src="<?php echo $baseHref?>algone.png" alt="algone"/></a></h1>
    </header>
    <article>
      <?php
if ($html) echo Chtimel::bodySub($html);
else if ($classname && method_exists($classname,'body')) call_user_func(array(__NAMESPACE__ .$classname, 'body'));
      ?>
	  </article>
    <footer id="bottom">
      <a href="http://algone.net">Algone</a> –
      <a href="http://algone.net">À propos</a>
    </footer>
  </body>
</html>
