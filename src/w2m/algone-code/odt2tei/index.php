<?php
// le code pilotant la transformation
include(dirname(__FILE__).'/Odt.php');

// Soumission en post
if (isset($_POST['post'])) {
  Odt::doPost();
  exit;
}
?><!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8"/>
    <title>odt2tei : Open Document Text &gt; XML/TEI</title>
    <link rel="stylesheet" href="http://svn.code.sf.net/p/algone/code/teipub/theme/html.css"/>
  </head>
  <body style="margin:1em;">
    <h1>ODT &gt; <a href="http://www.tei-c.org/release/doc/tei-p5-doc/fr/html/REF-ELEMENTS.html">TEI</a></h1>
    <h2>Convertissez vos documents bureautique en TEI</h2>
    <!--
    <ul>
      <li>Parcourir : chercher un fichier OpenOffice odt sur votre poste</li>
      <li>Voir : montrer le XML produit</li>
      <li>Télécharger : enregistrer le produit sur son poste</li>
    </ul>
    -->


    <?php
  if (isset($_REQUEST['format'])) $format=$_REQUEST['format'];
  else $format="tei";
  /*
        — <label title="OpenDocument Text xml"><input name="format" type="radio" value="ngml" <?php if($format == 'ngml') echo ' checked="checked"'; ?>/> NGML </label>

        — <label title="HTML (Diple)"><input name="format" type="radio" value="html" <?php if($format == 'html') echo ' checked="checked"'; ?>/> HTML </label>
  */
    ?>
    <form enctype="multipart/form-data" method="POST" name="odt" action="index.php">
      <input type="hidden" name="post" value="post"/>
      <div style="margin: 50px 0 20px;">
        <b>1. Fichier odt</b> :
        <input type="file" size="70" name="odt" accept="application/vnd.oasis.opendocument.text"/><!-- ne sort pas ds chrome -->
      </div>

      <div style="margin: 20px 0 20px;">
        <b>2. Format d'export</b> :
            <label title="TEI"><input name="format" type="radio" value="tei" <?php if($format == 'tei') echo ' checked="checked"'; ?>/> tei</label>
          — <label title="OpenDocument Text xml"><input name="format" type="radio" value="odtx" <?php if($format == 'odtx') echo ' checked="checked"'; ?>/> xml odt</label>
          — <label title="OpenDocument Text xml"><input name="format" type="radio" value="html" <?php if($format == 'html') echo ' checked="checked"'; ?>/> html</label>
          | <label title="Indiquer le mot clé d'un autre format">Autres formats <input name="local" size="10"/></label>
      </div>

      <div style="margin: 20px 0 40px;">
        <b>3. Résultat</b> :
        <input type="submit" name="view"  value="Voir"/> ou
          <input type="submit" name="download" onclick="this.form" value="Télécharger"/>
        <br/>
        Pour une démonstration sur ce <a href="odt2tei.odt">document de test</a>, cliquez sans choisir de fichier.
      </div>
    </form>
    <p><a onmouseover="this.href='mailto'+'\x3A'+'frederic.glorieux'+'\x40'+'algone.net'" href="#">Frédéric Glorieux</a>. N'hésitez pas à nous envoyer vos cas épineux.</p>
  </body>
</html>
