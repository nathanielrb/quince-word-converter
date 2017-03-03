<?php // encoding="UTF-8"
/**
<h1>OpenDocument text transform</h1>

© 2012, <a href="http://algone.net/">Algone</a>,
<a href="http://www.cecill.info/licences/Licence_CeCILL_V2-fr.html">licence CeCILL</a> (<a href="http://www.cecill.info/licences/Licence_CeCILL_V2-en.html">en</a>) / <a href="http://www.gnu.org/licenses/gpl.html">GPL</a>
© 2010, <a href="http://www.enc.sorbonne.fr/">École nationale des chartes</a>,
<a href="http://www.cecill.info/licences/Licence_CeCILL_V2-fr.html">licence CeCILL</a> (<a href="http://www.cecill.info/licences/Licence_CeCILL_V2-en.html">en</a>) / <a href="http://www.gnu.org/licenses/gpl.html">GPL</a>


<ul>
  <li>2012 [FG] <a href="#" onmouseover="this.href='mailto'+'\x3A'+'frederic.glorieux'+'\x40'+'algone.net'">Frédéric Glorieux</a></li>
  <li>2009–2010 [FG] <a  href="#" onmouseover="this.href='mailto'+'\x3A'+'frederic.glorieux'+'\x40'+'enc.sorbonne.fr'">Frédéric Glorieux</a></li>
</ul>


<p>
Pilot to work with OpenOffice Text files. Steps:
</p>

<ul>
  <li>unzip odt</li>
  <li><a href="odtNorm.xsl">normalisation of some odt oddities</a> (XSL)</li>
  <li><a href="odt2tei.xsl">structuration of visual formatting</a> (XSL)</li>
  <li><a href="tei.sed">typographical normalisation</a> (regex)</li>
  <li><a href="teiPost.xsl">some semantic interpretations (ex: index)</a> (XSL)</li>
</ul>



*/


set_time_limit(-1);
// included file, do nothing
if (isset($_SERVER['SCRIPT_FILENAME']) && basename($_SERVER['SCRIPT_FILENAME']) != basename(__FILE__));
else if (isset($_SERVER['ORIG_SCRIPT_FILENAME']) && realpath($_SERVER['ORIG_SCRIPT_FILENAME']) != realpath(__FILE__));
// direct command line call, work
else if (php_sapi_name() == "cli") Odt::doCli();
// direct http call, work
else Odt::doPost();


/**

OpenDocumentText vers TEI.
 */
class Odt {
  /** keep original odt FilePath for a file context */
  private $srcFile;
  /** FileName without extension for generated contents */
  private $destName;
  /** un log */
  private $log;
  /** A dom document to load an XSL */
  private $xsl;
  /** Current dom document on which work is done by methods */
  private $doc;
  /** Current  */
  private $proc;
  /** Array of messages */
  private $message;
  
  /**
   * Constructor, instanciations
   */
  function __construct($odtFile, $destName='') {
    $this->srcFile=$odtFile;
    $pathinfo=pathinfo($odtFile);
    if ($destName) $this->destName=$destName;
    else $this->destName=$pathinfo['filename'];
    

    $this->xsl = new DOMDocument("1.0", "UTF-8");
    // register functions ?
    $this->proc = new XSLTProcessor();
    // load odt as xml doc
    $this->odtx();
  }
  /**
   * Format loaded dom
   */
  public function format($format, $pars=array()) {
    if ($format=='odtx');
    else if ($format=='tei') {
      $this->tei();
    }
    else if ($format=='philo3') {
      $this->tei();
      $this->transform(dirname(__FILE__).'/tei_philo3.xsl');
    }
    else if ($format=='corr') {
      $this->tei();
      $this->transform(dirname(__FILE__).'/tei_corr.xsl');
    }
    else if ($format == 'html') {
      // format html from a clean TEI
      $this->tei();
      // find a transfo pack for tei to html
      $xsl=dirname(__FILE__).'/tei2html.xsl';
      if (!file_exists($xsl)) $xsl=dirname(dirname(__FILE__)).'/teipub/xsl/tei2html.xsl';
      if (!file_exists($xsl)) $xsl="http://svn.code.sf.net/p/algone/code/teipub/xsl/tei2html.xsl";
      $this->transform($xsl, $pars);
    }
    else {
      return;
      echo "Format $format not yet supported. Please create a ticket to ask for a new feature : <a href=\"http://sourceforge.net/p/algone/tickets/\">Algone SourceForge project</a> ";
      exit;
    }
    $this->doc->formatOutput=true;
    $this->doc->substituteEntities=true;
    // $this->doc->normalize(); // no, will not allow &gt;
  }
  
  /**
   * Save result to file, in desired format
   */
  public function save($format, $destFile, $pars=array()) {
    $pathinfo=pathinfo($destFile);
    if (file_exists($destFile) && !isset($pars['force'])) {
      echo "Destination file already exists: $destFile\n";
      return;
    }
    $this->destName=$pathinfo['filename'];
    if ($format=='odtx') {
      $this->pictures(dirname($destFile).'/Pictures');
    }
    else {
      $this->pictures(dirname($destFile).'/'.$this->destName.'-img/');
    }
    $this->format($format, $pars);
    $this->doc->save($destFile);
  }
  
  /**
   * Get xml in the desired format
   */
  public function saveXML($format, $pars=array()) {
    $this->format($format, $pars);
    return $this->doc->saveXML();
  }
  private function pictures($destDir) {
    $zip = new ZipArchive();
    $zip->open($this->srcFile);
    $entries=array();
    for($i = $zip->numFiles -1; $i >= 0 ; $i--) {
      if (strpos($zip->getNameIndex($i), 'Pictures/') !== 0 && strpos($zip->getNameIndex($i), 'media/') !== 0) continue;
      $entries[]=$zip->getNameIndex($i);
    }
    if (!count($entries)) return false;
    $destDir=rtrim($destDir, '/').'/';
    if (!is_dir($destDir)) mkdir($destDir, 0775, true);
    @chmod($dest, 0775);  // let @, if www-data is not owner but allowed to write
    foreach($entries as $entry) file_put_contents($destDir.basename($entry), $zip->getFromName($entry));
  }
  /**
   * instanciate a dom document from the zip
   */
  public function odtx() {
    if (!extension_loaded("zip")) {
      echo '<p class="error">PHP zip extension required</p>';
      return;
    }
    $zip = new ZipArchive();
    if (!($zip->open($this->srcFile)===TRUE)) {
      echo '<p class="error">'.$this->srcFile.' not found.</p>';
      return false;
    }
    // suppress xml prolog in extracted files
    $xml='<?xml version="1.0" encoding="UTF-8"?>
<office:document xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0">
';
    $content=$zip->getFromName('meta.xml');
    $xml .= substr($content, strpos($content, "\n"));
    $content = $zip->getFromName('styles.xml');
    $xml .= substr($content, strpos($content, "\n"));
    $content = $zip->getFromName('content.xml');
    $xml .= substr($content, strpos($content, "\n"));
    $xml .='
</office:document>';
    // load doc
    $this->loadXML($xml);
    
    $zip->close();
  }
  /**
   * Output TEI
   */
  private function tei() {
    $params=array();
    $params['filename']=$this->destName;
    // some normalisation of oddities
    $this->transform(dirname(__FILE__).'/odt-norm.xsl');
    // $this->doc->formatOutput=true;
    // odt > tei
    $this->transform(dirname(__FILE__).'/odt2tei.xsl', $params);
    
    // indent here produce too much problems, use only for debug
    $this->doc->formatOutput=true;
    $this->doc->substituteEntities=true;
    $xml=$this->doc->saveXML();

    

    // regularisation of tags segments, ex: spaces tagged as italic
    $preg=self::sed_preg(file_get_contents(dirname(__FILE__).'/tei.sed'));
    $xml = preg_replace($preg[0], $preg[1], $xml);
    $this->loadXML($xml);
    $this->doc->formatOutput=true;
    

    /* 
    echo $xml;
    echo '<!-- ',print_r($this->message, true), ' -->'; // for debug show now xlml errors
    exit;
    */
    
    // header("Connection: Keep-alive");
    // echo 'Mem peak: ', memory_get_peak_usage(), ' true? ', memory_get_peak_usage(true), "\n";
    // print_r($this->message); // for debug show now xlml errors
    // xsl step to put some tei oddities like <hi rend="i"> (instead of <i>)
    $this->transform(dirname(__FILE__).'/tei_post.xsl');
    // echo 'Mem peak: ', memory_get_peak_usage(), ' true? ', memory_get_peak_usage(true), "\n";
  }

  /**
   * Build a search/replace regexp table from a sed script
   */
  public static function sed_preg($script) {
    $search=array();
    $replace=array();
    $lines=explode("\n", $script);
    $lines=array_filter($lines, 'trim');
    foreach($lines as $l){
      if ($l[0] != 's') continue;
      list($a,$s,$r)=explode($l[1], $l);
      $search[]=$l[1].$s.$l[1].'u';
      $replace[]=preg_replace('/\\\\([0-9]+)/', '\\$$1', $r);
    }
    return array($search, $replace);
  }


  /**
   * Load xml as dom
   */
  private function loadXML($xml) {
    $this->message=array();
    $oldError=set_error_handler(array($this,"err"), E_ALL);
    // if not set here, no indent possible for output
    $this->doc = new DOMDocument("1.0", "UTF-8");
    $this->doc->preserveWhiteSpace = false;
    $this->doc->recover=true;
    $this->doc->loadXML($xml, LIBXML_NOENT | LIBXML_NONET | LIBXML_NSCLEAN | LIBXML_NOCDATA | LIBXML_COMPACT);
    restore_error_handler();
    if (count($this->message)) {
      $this->doc->appendChild($this->doc->createComment("Error recovered in loaded XML document \n". implode("\n", $this->message)."\n"));
    }
  }
  /** record errors in a log variable, need to be public to used by loadXML */
  public function err( $errno, $errstr, $errfile, $errline, $errcontext) {
    if(strpos($errstr, "xmlParsePITarget: invalid name prefix 'xml'") !== FALSE) return;
    $this->message[]=$errstr;
  }

  
  /**
   * Transformation, applied to current doc
   */
  private function transform($xslFile, $pars=null) {
    // filePath should be correct, only internal resources are used here
    $this->xsl->load($xslFile);
    $this->proc->importStyleSheet($this->xsl);
    // transpose params
    if($pars && count($pars)) foreach ($pars as $key => $value) $this->proc->setParameter('', $key, $value);
    // we should have no errors here
    $this->doc=$this->proc->transformToDoc($this->doc);
  }

  /**
   *  Apply code to an uploaded File, or to a default file
   */
  public static function doPost($format='', $download=null, $defaultFile=null) {
    if (!isset($defaultFile)) $defaultFile=dirname(__FILE__).'/odt2tei.odt';
    
    do {
      // a file seems uploaded
      if(count($_FILES)) {
        reset($_FILES);
        $tmp=current($_FILES);
        if($tmp['tmp_name']) {
          $file=$tmp['tmp_name'];
          if ($tmp['name']) $fileName=substr($tmp['name'], 0, strrpos($tmp['name'], '.'));
          else $fileName="odt2tei";
          break;
        }
        else if($tmp['name']){
          echo $tmp['name'],' seems bigger than allowed size for upload in your php.ini : upload_max_filesize=',ini_get('upload_max_filesize'),', post_max_size=',ini_get('post_max_size');
          return false;
        }
      }
      if ($defaultFile) {
        $file=$defaultFile;
        $fileName=substr(basename($file), 0, strrpos(basename($file), '.'));
      }
    } while (false);
    
    
    if($format);
    else if(isset($_REQUEST['format'])) $format=$_REQUEST['format'];
    else $format="tei";
    if(isset($download));
    else if(isset($_REQUEST['download'])) $download=true;
    else $download=false;
    
    // headers
    if ($download) {
      if ($format == 'html') {
        header ("Content-Type: text/html; charset=UTF-8");
        $ext="html";
      }
      else {
        header("Content-Type: text/xml");
        $ext='xml';
      }
      header('Content-Disposition: attachment; filename="'.$fileName.'.'.$ext.'"');
      header('Content-Description: File Transfer');
      header('Expires: 0');
      header('Cache-Control: ');
      header('Pragma: ');
      flush();
    }
    else if ($format == 'html') header ("Content-Type: text/html; charset=UTF-8");
    // chrome do not like text/xml
    else {
      header ("Content-Type: text/xml;");
    }
    $odt=new Odt($file, $fileName);
    echo $odt->saveXML($format);
    exit;
  }
  /**
   * Apply code from Cli
   */
  public static function doCli() {
    $formats='odtx|tei|html';
    array_shift($_SERVER['argv']); // shift first arg, the script filepath
    if (!count($_SERVER['argv'])) exit('
    usage    : php -f Odt.php $formats ? src.odt
    format?  : optional dest format, default tei, others may be odtx, html
    src.odt  : glob patterns are allowed, but in quotes, to not be expanded by shell "folder/*.odt"
  ');
    $format="tei";
    while ($arg=array_shift($_SERVER['argv'])) {
      if ($arg[0]=='-') $format=substr($arg,1);
      else if(preg_match("/^($formats)\$/",$arg)) {
        $format=$arg;
      }
      else if(!isset($srcGlob)) {
        $srcGlob=$arg;
      }
      /*
      $destDir=array_shift($_SERVER['argv']);
      if (!$destDir) $destDir='';
      $destDir=rtrim($destDir, '/').'/';
      if (!file_exists($destDir)) mkdir($destDir, 0775, true);
      */
    }
    $ext=".$format";
    if ($ext=='.tei') $ext=".xml";
    $count = 0;
    foreach(glob($srcGlob) as $srcFile) {
      $count++;
      $destFile=dirname($srcFile).'/'.basename($srcFile, ".odt").$ext;
      print "$count. $srcFile > $destFile\n";
      $odt=new Odt($srcFile);
      $odt->save($format, $destFile);
    }
  }
}


?>
