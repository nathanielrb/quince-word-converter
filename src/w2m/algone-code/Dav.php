<?php
/**

<h1>Webdav client</h1>

© 2010, 2012 <a onclick="this.href='mailto'+'\x3A'+'frederic.glorieux'+'\x40'+'fictif.org'">Frédéric Glorieux</a>,
<a href="http://www.cecill.info/licences/Licence_CeCILL-C_V1-fr.html">licence CeCILL-C</a>
(LGPL compatible droit français)

<p>
This class with no deps is mostly used for a subversion client over http (through webdav).
Take note of the method update(), comparing dates from remote and local.
Remote manipulation of files is not implemented (there much more handy client for that)
This class will not work for subversion over <a href="http://phpseclib.sourceforge.net/">ssh</a>.
</p>


Work start on a very complex and long class, code is going lighter and more robust,
using native libraries in PHP5.
<ul>
	<li>Move from socket to stream contexts</li>
	<li>Replace sax hooks by dom</li>
	<li>Update coding style (ex : camelCase)</li>
</ul>

<h2>Original download</h2>
<a href="http://www.phpkode.com/source/s/webdav-client/webdav-client/class_webdav_client.php">
a php based nearly rfc 2518 conforming client.
</a>

<ul>
	<li>@author Christian Juerges <christian.juerges@xwave.ch>, Xwave GmbH, Josefstr. 92, 8005 Zuerich - Switzerland.</li>
	<li>@copyright (C) 2003/2004, Christian Juerges</li>
	<li>@license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License</li>
</ul>
*/

define("CRLF", "\r\n");
// set a default timezone
$tz=@date_default_timezone_get();
if (!$tz) $tz='Europe/Paris';
date_default_timezone_set($tz);

error_reporting(-1);
ini_set('display_errors', 'true');

class Dav {

	/** Server name to request */
	static $server;
	/** A stream where to output messages. */
	static $logStream ;
	/** default mode for created directory */
	static $dirMod=0775;
	/** default mode for created files */
	static $fileMod=0664;
	/** give a user agent ? */
	static $userAgent = 'Dav.php';

	/**
	 * Update local copy with remote dir, download only newer files (like an svn up)
	 * @param dir local directory
	 * @param path remote path
	 */
	static function update($uri, $dir, $force=false) {
		// suppose uri as dir
		$uri=rtrim($uri,'/').'/';
		$dir=rtrim($dir,'/').'/';
		if (!is_dir($dir)) {
			mkdir($dir,self::$dirMod,true);
			// plante OVH
			// chmod($dir, self::$dirMod);
		}
		$ls = self::propfind($uri);
		$count = count($ls);
		for ($i=0; $i < $count; $i++) {
			$res=$ls[$i];
			$name=basename($res['href']);
			$file=$dir.$name;
			// could also test if href end by / ?
			if (isset($res['collection'])) {
				echo CRLF.$res['href'].' — '.$file;
				if (!is_dir($file)) {
					mkdir($file, self::$dirMod,true);
					// plante OVH
					// chmod($file, self::$dirMod);
				}
				self::update($uri.$name, $file, $force);
			}
			else {
				$mtime=strtotime($res['lastmodified']);
				if (file_exists($file) && filemtime($file) > $mtime && !$force) continue;
				echo CRLF.'		'.$res['href'].' >>> '.$file;
				self::copy($uri.$name, $file);
			}
		}
	}

	/**
	 * Get's directory information from webdav server into flat a array using PROPFIND
	 * @param string path
	 * @return array dirinfo, false on error
	 */
	static function propfind($uri) {
		$context = stream_context_create(
			array (
				'http' => array (
					'method' => 'PROPFIND',
					'ignore_errors' => true, // propfind answer correctly a 207 Multi-Status that PHP understand as an error, PHP >= 5.2.10
					'header' => 'Content-type: text/xml'.CRLF.'Depth: 1'.CRLF,
					'content' => '<?xml version="1.0"?>
<D:propfind xmlns:D="DAV:">
	<D:allprop/>
</D:propfind>
'				)
			)
		);
		// do not work PHP < 5.2.10
		$contents = file_get_contents( $uri, false, $context );

		$doc = new DOMDocument();
		$doc->loadXML($contents, LIBXML_NOENT | LIBXML_NONET | LIBXML_NSCLEAN | LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_NOERROR | LIBXML_NOWARNING);
		$ls=array();
		// first block is the folder itself
		// if(rtrim($href,'/') == rtrim($path,'/')) continue;
		$i=-1;
		foreach($doc->getElementsByTagNameNS ( 'DAV:' , 'response' ) as $response) {
			$i++;
			if ($i===0) continue;
			$href=$response->getElementsByTagNameNS ( 'DAV:' , 'href' )->item(0)->textContent;
			$res=array();
			$res['href']=$href;
			if($response->getElementsByTagNameNS ( 'DAV:' , 'collection')->length) $res['collection']=true;
			$res['lastmodified']=$response->getElementsByTagNameNS ( 'DAV:' , 'getlastmodified' )->item(0)->textContent;
			$ls[]=$res;
		}
		return $ls;
	}

	/**
	 * Gets a file from a collection into local filesystem.
	 * fopen() is used.
	 * @param string srcpath, string localpath
	 * @return true on success. false on error.
	 */
	static function copy($uri, $file) {
		if(copy($uri,$file)) {
			// plante OVH
			// chmod($localPath, self::$fileMod);
			return true;
		}
		$errors= error_get_last();
		self::log("GET, remote resource, impossible to read ".$uri . " ".$errors['message']);
		return false;
	}

	/**
	 * set debug on output stream.
	 * @param resource stream
	 */
	static function setLog($stream) {
		if (!is_resource($stream)) return false;
		self::$logStream = $stream;
	}


	/**
	 * Private method translate_uri
	 * TODO : verify if simplification is not possible (what about encoding ?)
	 *
	 * translates an uri to raw url encoded string.
	 * Removes any html entity in uri
	 * @param string uri
	 * @return string translated_uri
	 * @access private
	 */
	static function translate_uri($uri) {
		// remove all html entities...
		$native_path = html_entity_decode($uri);
		$parts = explode('/', $native_path);
		for ($i = 0; $i < count($parts); $i++) {
			$parts[$i] = rawurlencode($parts[$i]);
		}
		return implode('/', $parts);
	}

	/**
	 * Private method log
	 *
	 * a simple php error_log wrapper.
	 * @param string err_string
	 * @access private
	 */
	static function log($message) {
		if (is_resource(self::$logStream)) {
			// error_log($message);
			fputs(self::$logStream,	CRLF.$message);
			flush();
		}
		return false;
	}
}

?>
