<?php
/**
 * @version $Id: eucalib.common.php
 * @package Eucalib: Component library for the Joomla! CMS
 * @subpackage Eucalib Common Files
 * @copyright Copyright (C) 2007 David Deutsch, All Rights Reserved
 * @author David Deutsch <skore@skore.de>
 * @license GNU/GPL v.2 or later http://www.gnu.org/copyleft/gpl.html
 *
 *                         _ _ _
 *                        | (_) |
 *     ___ _   _  ___ __ _| |_| |__
 *    / _ \ | | |/ __/ _` | | | '_ \
 *   |  __/ |_| | (_| (_| | | | |_) |
 *    \___|\__,_|\___\__,_|_|_|_.__/  v1.0
 *
 * The Extremely Useful Component LIBrary will rock your socks. Seriously. Reuse it!
 */

defined( '_VALID_MOS' ) or die( 'Restricted access' );

class eucaInstall
{
	function eucaInstall()
	{
		$this->errors = array();
	}

	function unpackFileArray( $array )
	{
		global $mosConfig_absolute_path;

		if ( !class_exists( 'Archive_Tar' ) ) {
			if ( class_exists( 'JConfig' ) ) {
				require_once( $mosConfig_absolute_path . '/libraries/archive/Tar.php' );
			} else {
				require_once( $mosConfig_absolute_path . '/includes/Archive/Tar.php' );
			}
		}

		foreach ( $array as $file ) {
			if ( $file[2] ) {
				$basepath = $mosConfig_absolute_path . '/administrator/components/' . _EUCA_APP_COMPNAME . '/';

				$fullpath	= $basepath . $file[0];
				$deploypath = $basepath . $file[1];
			} else {
				$basepath = $mosConfig_absolute_path . '/components/' . _EUCA_APP_COMPNAME . '/';

				$fullpath	= $basepath . $file[0];
				$deploypath = $basepath . $file[1];
			}

			$archive = new Archive_Tar( $fullpath, 'gz' );

			if ( !@is_dir( $deploypath ) ) {
				// Borrowed from php.net page on mkdir. Created by V-Tec (vojtech.vitek at seznam dot cz)
				$folder_path = array( strstr( $deploypath, '.' ) ? dirname( $deploypath ) : $deploypath );

				while ( !@is_dir( dirname( end( $folder_path ) ) )
						&& dirname(end($folder_path)) != '/'
						&& dirname(end($folder_path)) != '.'
						&& dirname(end($folder_path)) != '' ) {
					array_push( $folder_path, dirname( end( $folder_path ) ) );
				}

				while ( $parent_folder_path = array_pop( $folder_path ) ) {
					@mkdir( $parent_folder_path, 0644 );
				}
			}

			if ( $archive->extract( $deploypath ) ) {
				@unlink( $fullpath );
			}
		}
	}

	function deleteAdminMenuEntries()
	{
		global $database;

		$query = 'DELETE'
		. ' FROM #__components'
		. ' WHERE `option` = \'option=' . _EUCA_APP_COMPNAME . '\''
		;
		$database->setQuery( $query );

		if ( !$database->query() ) {
	    	$this->errors[] = array( $database->getErrorMsg(), $query );
		}
	}

	function createAdminMenuEntry( $entry )
	{
		// Create new entry
		$return = $this->AdminMenuEntry( $entry, 0, 0 );

		if ( $return === true ) {
			return;
		} else {
			return array( $return );
		}
	}

	function populateAdminMenuEntry( $array )
	{
		global $database;

		// get id from component entry
		$query = 'SELECT id'
		. ' FROM #__components'
		. ' WHERE `link` = \'option=' . _EUCA_APP_COMPNAME . '\''
		;
		$database->setQuery( $query );
		$id = $database->loadResult();

		$k = 0;
		$errors = array();
		foreach ( $array as $entry ) {
			if ( $this->AdminMenuEntry( $entry, $id, $k ) ) {
				$k++;
			}
		}
	}

	function AdminMenuEntry ( $entry, $id, $ordering )
	{
		global $database;

		$values = array();
		$values[] = 0;
		$values[] = $entry[1];
		$values[] = 'option=' . _EUCA_APP_COMPNAME;
		$values[] = 0;
		$values[] = $id;
		$values[] = 'option=' . _EUCA_APP_COMPNAME . '&task=' . $entry[0];
		$values[] = $entry[1];
		$values[] = _EUCA_APP_COMPNAME;
		$values[] = isset( $entry[3] ) ? $entry[3] : $ordering;
		$values[] = $entry[2];
		$values[] = 0;
		$values[] = '';
		if ( class_exists( 'JConfig' ) ) {
			$values[] = 1;
		}

		$query = 'INSERT INTO #__components'
		. ' VALUES '
		. '(\'' . implode( '\', \'', $values) . '\')'
		;

		$database->setQuery( $query );

		if ( !$database->query() ) {
	    	$this->errors[] = array( $database->getErrorMsg(), $query );
	    	return false;
		} else {
			return true;
		}
	}
}

class eucaInstallDB
{
	function eucaInstallDB()
	{
		$this->errors = array();
	}

	function ColumninTable( $column=null, $table=null, $prefix=true )
	{
		global $database;

		$result = null;

		if ( !empty( $column ) ) {
			$this->column = $column;
		}

		if ( !empty( $table ) ) {
			if ( $prefix ) {
				$this->table = _EUCA_APP_SHORTNAME . '_' . $table;
			} else {
				$this->table = $table;
			}
		}

		$query = 'SHOW COLUMNS FROM #__' . $this->table
		. ' LIKE \'' . $this->column . '\''
		;

		$database->setQuery( $query );
		$database->loadObject( $result );

		if( is_object( $result ) ) {
			if ( strcmp($result->Field, $column) === 0 ) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	function dropColifExists( $column, $table, $prefix=true )
	{
		if ( $this->ColumninTable( $column, $table, $prefix ) ) {
			return $this->dropColumn( $column );
		}
	}

	function addColifNotExists( $column, $options, $table, $prefix=true )
	{
		if ( !$this->ColumninTable( $column, $table, $prefix ) ) {
			return $this->addColumn( $options );
		}
	}

	function addColumn( $options )
	{
		global $database;

		$query = 'ALTER TABLE #__' . $this->table
		. ' ADD COLUMN `' . $this->column . '` ' . $options
		;

		$database->setQuery( $query );

		$result = $database->query();

		if ( !$result ) {
	    	$this->errors[] = array( $database->getErrorMsg(), $query );
	    	return false;
		} else {
			return true;
		}
	}

	function dropColumn( $options )
	{
		global $database;

		$query = 'ALTER TABLE #__' . $this->table
		. ' DROP COLUMN `' . $this->column . '`'
		;

		$database->setQuery( $query );

		$result = $database->query();

		if ( !$result ) {
	    	$this->errors[] = array( $database->getErrorMsg(), $query );
	    	return false;
		} else {
			return true;
		}
	}

}

class eucaInstalleditfile
{
	function eucaInstalleditfile()
	{
		$this->errors = array();
	}

	function fileEdit( $path, $search, $replace, $throwerror )
	{
		$originalFileHandle = fopen( $path, 'r' );

		if ( $originalFileHandle != false ) {
			// Transfer File into variable $oldData
			$oldData = fread( $originalFileHandle, filesize( $path ) );
			fclose( $originalFileHandle );

			$newData = str_replace( $search, $replace, $oldData );

			$oldperms = fileperms( $path );
			@chmod( $path, $oldperms | 0222 );

			if ( $fp = fopen( $path, 'wb' ) ) {
				if ( fwrite( $fp, $newData, strlen( $newData ) ) != -1 ) {
					fclose( $fp );
					@chmod( $path, $oldperms );
					return true;
				}
		    }
		}

		$this->error[] = $throwerror;
		return false;
	}

}

?>