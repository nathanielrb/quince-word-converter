<?php
new littre();
class littre {
  static $mot;
  function __construct() {
    $pathinfo=Web::pathinfo();
    list($littre,self::$mot)=explode ( "/" , $pathinfo.'//' );
    if (!self::$mot) self::$mot="dictionnaire";
  }
  static function head() {
    echo '
    <style type="text/css">
form small { display:block;}
input#q { font-size:16px; padding:0 0.5ex;}
div.entry, div.entryFree { margin-top:2em; max-width:80ex; border-bottom:1px #CCCCCC solid; padding-bottom:2em; }
.entry ul { list-style:none; margin:0 0 0 0; padding:0; }
.entry ul ul { margin-left:1em; }
.sense1{ margin:1.5em 0 1ex 0; padding:0; }
.sense2{ margin:0; padding:0; }
.entry p { margin:1em 0 1em 0; text-indent:0; text-align:left; }
.entry p.dictScrap { font-size:14px; margin:0; text-indent:0; text-align:left; line-height:140%; }
.entry blockquote { border-left: 1px dotted #888888; margin:0 0 0 1.25em; line-height:100%; padding:0; }
.quote { padding: 0.3ex 0 0.5ex 0.8ex; color:#4C4C4C; font-family:verdana; font-size:13px; text-align:justify; }
p.credits { font-size:12px; color:#4C4C4C; margin-top:2em; text-align:right; }
    </style>
    <script type="text/javascript">
function go() {
  var forme;
  if (window.getSelection) forme=window.getSelection();
  else if (document.getSelection) forme=document.getSelection();
  else if (document.selection) forme=document.selection.createRange().text;
  var href=location.href.replace
  alert(\'TODO\');
}
    </script>
    ';
  }
  static function body() {
    echo '<h1><i>Littré</i> : “',self::$mot,'”</h1>';
    echo '<form name="search" action="" method="get" onsubmit="this.action=this.q.value">
      <p>
        Littré, consulter un mot<br/>
        <input id="q" name="q" size="44" value=""/>
        <small>ou double-cliquer un mot dans le texte</small>
      </p>
    </form>
';
    echo '<div ondblclick="go();">';
    readfile('http://dev.algone.net:8080/littre/?body=1&q='.self::$mot);
    echo '</div>';
  }
}
?>
