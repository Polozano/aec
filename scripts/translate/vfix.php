<?php
/**
 * @version $Id: translate.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Translate J!1.0 -> J!1.5/1.6 language files
 * @copyright 2006-2015 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.3 http://www.gnu.org/licenses/gpl.html or, at your option, any later version
 */

// Point this to the root directory of your software
$path = realpath( dirname(__FILE__) .'/../../code' );

// Creating a temp directory
$temppath = dirname(__FILE__) .'/temp';

// (Make sure we have a fresh start)
if ( file_exists( $temppath ) ) {
	vTranslate::rrmdir( $temppath );
}

mkdir( $temppath );

// Log - this is broken cause I'm too stupid
if ( file_exists( $temppath."/log.txt" ) ) {
	unlink( $temppath."/log.txt" );
}

$log = new SplFileObject( $temppath."/log.txt", 'w' );

$all_targets = array();

// Get all our folders, including project root
$stddirs = array();
$stddirs[] = $path;
$stddirs = vTranslate::getFolders( $path, $stddirs, true );

// Create all the target directories
foreach( $stddirs as $sourcedir ) {
	$targetpath = str_replace( $path, $temppath, $sourcedir );

	vTranslate::log( "Preparing regular files in: " . $targetpath . "\n", $log );

	if ( !is_dir( $targetpath ) ) {
		mkdir( $targetpath );
	}

	$files = vTranslate::getFiles( $sourcedir );

	// Take note which files we might want to translate later on
	if ( !empty( $files ) ) {
		foreach ( $files as $file ) {
			$all_targets[] = array( 'source' => $sourcedir.'/'.$file, 'target' => $targetpath.'/'.$file );
		}
	}
}

$dirs = vTranslate::getFolders( $path );

$all_constants = array();

// Convert .php to .ini
foreach( $dirs as $sourcedir ) {
	vTranslate::log( "Processing: " . $sourcedir . "\n", $log );

	$targetpath = str_replace( $path, $temppath, $sourcedir );

	if ( !is_dir( $targetpath ) ) {
		mkdir( $targetpath );
	}

	$files = vTranslate::getFiles( $sourcedir );

	$translations_echo = $translations = array();
	foreach ( $files as $file ) {
		$lang = str_replace( '.ini', '', $file );

		$translations[] = $lang;

		$translations_echo[] = $lang;
	}

	vTranslate::log( "Translations found: " . implode( ", ", $translations_echo ) . "\n\n", $log );

	$translator = array();
	$translatef = array();
	foreach ( $translations as $translation ) {
		vTranslate::log( "Processing: " . $translation . "\n", $log );
		if ( !isset( $translator[$translation] ) ) {
			$translator[$translation] = array();
		}

		$file = new SplFileObject( $sourcedir.'/'.$translation.'.ini' );

		while ( !$file->eof() ) {
			if ( parse_ini_string( $file->fgets() ) === false ) {
				vTranslate::log( "Error in line: " . $file->current() . "\n", $log );
			}
		}

	}

}

// et voilà
vTranslate::log( "All done." . "\n\n", $log );

class vTranslate
{
	public function getFolders( $path, $list=array(), $other=false )
	{
		$iterator = new DirectoryIterator( $path );

		foreach( $iterator as $object ) {
			if ( $object->isDot() ) {
				continue;
			}

			if ( $object->isDir() ) {
				if ( strpos( $object->getFilename(), '-' ) !== false ) {
					if ( !$other ) {
						$list[] = $object->getPathname();
					}
				} else {
					if ( $other ) {
						$list[] = $object->getPathname();
					}
				}

				$list = array_merge( vTranslate::getFolders($object->getPathname(), $list, $other) );
			}
		}

		return $list;
	}

	public function getFiles($path)
	{
		$iterator = new DirectoryIterator( $path );

		$arr = array();
		foreach( $iterator as $object ) {
			if ( !$object->isDot() && !$object->isDir() ) {
				if ( strpos( $object->getFilename(), '.ini' ) ) {
					$arr[] = $object->getFilename();
				}
			}
		}

		return $arr;
	}

	public function parseLine( $line )
	{
		// Clean up line
		$line = trim( $line );

		$comments = array( '/**', '* ', '//', '#' );

		$comment = '';
		foreach ( $comments as $c ) {
			if ( strpos( $line, $c ) === 0 ) {
				$comment = trim( str_replace( $c, '', $line ) );
			}
		}

		$return = array();

		$return['type']		= 'empty';

		if ( $comment == 'Dont allow direct linking' ) {
			$comment = '';
		}

		if ( empty( $line ) ) {
			// Skip
		} elseif ( !empty( $comment ) ) {
			// Custom hacks to modify dates and file references
			//$comment = str_replace( '2010', '2011', $comment );
			//$comment = str_replace( '.php', '.ini', $comment );

			$return['type']		= 'comment';
			$return['content']	= $comment;
		} elseif ( strpos( strtolower($line), 'define' ) === 0 ) {
			$return['type'] = 'ham';

			// If lines have their content in ""'s, move that to '''s
			if ( strpos( $line, '\', "' ) && strpos( $line, '");' ) ) {
				$line = str_replace( '\', "', '\', \'', $line );
				$line = str_replace( '");', '\');', $line );
			}

			// Get the language key
			$defstart	= strpos( $line, '\'' );
			$defend		= strpos( $line, '\'', $defstart+1 );

			$name = substr( $line, $defstart+1, $defend-$defstart-1 );

			$constart	= strpos( $line, '\'', $defend+1 );
			$conend		= strrpos( $line, '\'' );

			// Get the translation
			$content = substr( $line, $constart+1, $conend-$constart-1 );

			$content = str_replace( "\'", "'", $content );
			$content = str_replace( '\"', '"', $content );

			// Use ini-style encoding for double quotes
			$content = str_replace( '"', '"_QQ_"', $content );

			$return['content'] = array( 'name' => $name, 'content' => $content );
		}

		return $return;
	}


	public function ISO3166_2ify( $lang )
	{
		$ll = explode( '-', $lang );

		$lang_codes = array( 	'brazilian_portoguese' => 'pt-BR',
								'brazilian_portuguese' => 'pt-BR',
								'czech' => 'cz-CZ',
								'danish' => 'da-DA',
								'dutch' => 'nl-NL',
								'english' => 'en-GB',
								'french' => 'fr-FR',
								'german' => 'de-DE',
								'germani' => 'de-DE-informal',
								'germanf' => 'de-DE-formal',
								'greek' => 'el-GR',
								'italian' => 'it-IT',
								'japanese' => 'ja-JP',
								'russian' => 'ru-RU',
								'simplified_chinese' => 'zh-CN',
								'spanish' => 'es-ES',
								'swedish' => 'sv-SE',
								'arabic' => 'ar-DZ',
								'belarusian' => 'be-BY',
								'bulgarian' => 'bg-BG',
								'bengali' => 'bn-IN',
								'bosnian' => 'bs-BA',
								'esperanto' => 'eo-XX',
								'basque' => 'eu-ES',
								'persian' => 'fa-IR',
								'finnish' => 'fi-FI',
								'hebrew' => 'he-IL',
								'croatian' => 'hr-HR',
								'hungarian' => 'hu-HU',
								'korean' => 'ko-KR',
								'lao' => 'lo-LA',
								'lithuanian' => 'lt-LT',
								'latvian' => 'lv-LV',
								'macedonian' => 'mk-MK',
								'norwegian' => 'nb-NO',
								'polish' => 'pl-PL',
								'portoguese' => 'pt-PT',
								'romanian' => 'ro-RO',
								'sindhi' => 'sd-PK',
								'sinhala' => 'si-LK',
								'slovak' => 'sk-SK',
								'shqip' => 'sq-AL',
								'montenegrin' => 'sr-ME',
								'serbian' => 'sr-RS',
								'syriac' => 'sy-IQ',
								'tamil' => 'ta-LK',
								'thai' => 'th-TH',
								'turkish' => 'tr-TR',
								'ukrainian' => 'uk-UA',
								'vietnamese' => 'vi-VN',
								'traditional_chinese' => 'zh-TW'
								);

		if ( isset( $lang_codes[$ll[0]] ) ) {
			return $lang_codes[$ll[0]];
		} else {
			return 'error-'.$ll[0];
		}
	}

	public function rrmdir( $dir )
	{
		if ( is_dir($dir) ) {
			$objects = scandir($dir);
			foreach ( $objects as $object ) {
				if ( $object != "." && $object != ".." ) {
					if ( filetype($dir."/".$object) == "dir" ) {
						vTranslate::rrmdir( $dir."/".$object );
					} else {
						unlink( $dir."/".$object );
					}
				}
			}

			reset($objects);

			rmdir($dir);
		}
	}

	public function safeEncode( $content )
	{
		if ( !mb_check_encoding( $content, 'UTF-8' ) || !( $content === mb_convert_encoding( mb_convert_encoding( $content, 'UTF-32', 'UTF-8' ), 'UTF-8', 'UTF-32' ) ) ) {
			$content = mb_convert_encoding( $content, 'UTF-8' );
		}

		return $content;
	}

	public function log( $thing, $log )
	{
		echo $thing;

		$log->fwrite( $thing );
	}
}
