<?php

	/**
	 * UpdateCheck
	 * Copyright (C) 2012 Kevin van Steijn, Maarten Oosting
	 *
	 * This program is free software; you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published by
	 * the Free Software Foundation; either version 2 of the License, or
	 * (at your option) any later version.
	 * 
	 * This program is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License along
	 * with this program; if not, write to the Free Software Foundation, Inc.,
	 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
	 */
	class Database
	{
		/**
		 * This variable is for database handle
		 *
		 * @since 2.1
		 * @access private
		 * @author Kevin van Steijn
		 */
		private static $_dbh = FALSE;
		
		/**
		 * This variable is for PDO handle
		 *
		 * @since 2.1
		 * @access private
		 * @author Kevin van Steijn
		 */
		private static $_sth;

		/**
		 * This variable is for database state
		 *
		 * @since 2.1
		 * @access private
		 * @author Kevin van Steijn
		 */
		private static $_error = array();

		/**
		 * This variable is for the storage of database driver
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static $driver = 'mysql';

		/**
		 * This variable is for the storage of database host
		 * Add here the path when using SQLite
		 *
		 * @since 2.1
		 * @access public
		 * @author Maarten Oosting
		 */
		public static $host = 'localhost';
		
		/**
		 * This variable is for the storage of database username
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static $user = '';
		
		/**
		 * This variable is for the storage of database password
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static $password = '';
		
		/**
		 * This variable is for the storage of databasename
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static $name = '';
		
		/**
		 * - 
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn, Maarten Oosting
		 */
		public function __construct($db_driver = FALSE, $db_user = FALSE, $db_password = FALSE, $db_name = FALSE, $db_host = FALSE)
		{	
			try {
				//Check argument. When not given using defaults
				if (!$db_driver) $db_driver = self::$driver;
				if(empty($db_driver)) throw new PDOException('Driver not available');
				
				if (!$db_host) $db_host = self::$host;
				if (!$db_name) $db_name = self::$name;
				if (!$db_user) $db_user = self::$user;
				if (!$db_password) $db_password = self::$password;

				//Checking Database driver
				if ($db_driver == 'sqlite') {
					$dbh = new PDO($db_driver.':'.$db_host);
				}elseif($db_driver == 'oci'){
					$dbh = new PDO($db_driver.':', $db_user, $db_password);
				}else if($db_driver == 'mysql' || $db_driver == "pgsql"){
					$dbh = new PDO($db_driver.':host='.$db_host.';dbname='.$db_name.';charset=utf8', $db_user, $db_password);
				} else throw new PDOException('Driver not available/supported');

				$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
				$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				
				self::$name = $db_name;
				self::$_dbh = $dbh;
			} 
			catch(PDOException $e) {
				self::$_dbh = FALSE;
			}
		}
		
		/**
		 * Get connect status
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function GetConnectStatus()
		{
			return (self::$_dbh) ? TRUE : FALSE;
		}
		
		/**
		 * Filter a string
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function Filter($string)
		{
			$arguments = array('\t', '\r', '\o', '\x0B', '\x00', '\x1a');
			$string = str_replace($arguments, '', strip_tags($string));
			$string = stripslashes($string);
			
			return $string;
		}
		
		/**
		 * Execute SQL query
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn, Maarten Oosting
		 */
		public static function Query($sql)
		{
			try {
				$dbh = self::$_dbh;
				if (!$dbh) throw new PDOException('No database connection');
				
				$object = $dbh->prepare($sql); 
				return $object;
			} catch(PDOException $e) {
				throw $e;
			} 
		}
		
		/**
		 * -
		 *
		 * @since 2.1
		 * @access public
		 * @author Michael van Kampen
		 */
		private static function GetFetch($tablename, $select_arg, $where_arg, $where_insertion)
		{
			if (!is_array($tablename)) $tablename = array($tablename);
			if (!is_array($select_arg)) $select_arg = array($select_arg);
			if (!is_array($where_arg)) $where_arg = array('id' => $where_arg);
			if (!in_array($where_insertion, array('AND', 'OR'))) $where_insertion = 'AND';
			
			$sql = 'SELECT ' .implode(', ', $select_arg) .' FROM ' .implode(' ,', $tablename);
	
			$where_total = count($where_arg);
			if ($where_total > 0) {
				$sql .= ' WHERE ';
				$sql .= self::SetInSQL($where_total, $where_arg, $where_insertion);
			}

			$sth = self::$_dbh->prepare($sql);
			
			if ($where_total > 0)
				self::SetBlindValue($sth, $where_total, $where_arg);
			
			return self::Execute($sth);
		}
		
		/**
		 * -
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn, Maarten Oosting
		 */
		public static function GetSingle($tablename, $where_arg, $select = '*', $where_insertion = 'AND')
		{
			try {
				$dbh = self::$_dbh;
				if (!$dbh) throw new PDOException('No database connection');
				
				$sth = self::GetFetch($tablename, $select, $where_arg, $where_insertion);
				$arguments = $sth->fetch(PDO::FETCH_ASSOC);
				
				return $arguments;
			} catch(PDOException $e) {
				throw $e;
			}
		}
		
		/**
		 * -
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn, Maarten Oosting
		 */
		public static function GetArray($tablename, $select = '*', $where_arg = array(), $where_insertion = 'AND')
		{
			try{
				$dbh = self::$_dbh;
				if (!$dbh) throw new PDOException('No database connection');
				
				$sth = self::GetFetch($tablename, $select, $where_arg, $where_insertion);
				$arguments = $sth->fetchAll(PDO::FETCH_ASSOC);
				return $arguments;
			} catch(PDOException $e){
				throw $e;
			}	
		}
		
		/**
		 * Search in a table
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function Search($tablename, $like_arg, $where_arg = array(), $select_arg = '*')
		{
			try {
				$dbh = self::$_dbh;
				if (!$dbh) throw new PDOException('No database connection');
				
				if (!is_array($tablename)) $tablename = array($tablename);
				if (!is_array($like_arg)) $update_arg = array('id' => $update_arg);
				if (!is_array($where_arg)) $where_arg = array('id' => $where_arg);
				if (!is_array($select_arg)) $select_arg = array($select_arg);
				
				$like_total = count($like_arg);
				if ($like_total == 0) throw new PDOException('LIKE is empty'); 
				
				$sql = 'SELECT ' .implode(', ', $select_arg) .' FROM ' .implode(' ,', $tablename) . ' WHERE (';
				$sql .= self::SetInSQL($like_total, $like_arg, 'OR', 'LIKE') . ')';
				
				$where_total = count($where_arg);
				if ($where_total > 0)
					$sql .= ' AND (	' . self::SetInSQL($where_total, $where_arg, 'AND') . ')';
				
				$sth = $dbh->prepare($sql);
				
				self::SetBlindValue($sth, $like_total, $like_arg);
				
				if ($where_total > 0)
					self::SetBlindValue($sth, $where_total, $where_arg, $like_total);
				
				$sth = self::Execute($sth);
				
				return $sth->fetchAll(PDO::FETCH_ASSOC);
			} catch(PDOException $e) { 
				throw $e;
			}
		}
		
		/**
		 * Create and execute a SQL update query
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn, Maarten Oosting
		 */
		public static function Update($tablename, $update_arg, $where_arg = array())
		{
			try {
				$dbh = self::$_dbh;
				if (!$dbh) throw new PDOException('No database connection');
				
				if (!is_array($tablename)) $tablename = array($tablename);
				if (!is_array($update_arg)) $update_arg = array('id' => $update_arg);
				if (!is_array($where_arg)) $where_arg = array('id' => $where_arg);
				
				$update_keys = array_keys($update_arg);
				$update_total = count($update_arg);
				$sql = 'UPDATE ' . implode('.', $tablename) . ' SET ';
				for($i = 0; $i < $update_total; $i++) {
					$sql .= $update_keys[$i] . ' = ?';
					if (($i + 1) < $update_total) $sql .= ',';
				}
				
				$where_total = count($where_arg);
				if ($where_total > 0) {
					$sql .= ' WHERE ';
					$sql .= self::SetInSQL($where_total, $where_arg, 'AND');
				}
	
				$sth = $dbh->prepare($sql);
				
				self::SetBlindValue($sth, $update_total, $update_arg);
				
				if ($where_total > 0)
					self::SetBlindValue($sth, $where_total, $where_arg, $update_total);
				
				self::Execute($sth);
				
				return TRUE;
			} catch(PDOException $e) { 
				throw $e;
			}
		}
		
		/**
		 * Create and execute a SQL insert query
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn, Maarten Oosting
		 */
		public static function Insert($tablename, $arguments)
		{
			try {
				$dbh = self::$_dbh;
				if (!$dbh) throw new PDOException('No database connection'); 
				
				if (!is_string($tablename)) throw new PDOException('Tablename is not a string'); 
				if (!is_array($arguments)) $arguments = array('id' => $arguments);
				
				$keys = array_keys($arguments);
				$total = count($arguments);
				$sql = "INSERT INTO $tablename (";
				$values = '';
				for ($i = 0; $i < $total; $i++) {
					$sql .= $keys[$i];
					$values .= '?';
					if (($i + 1) < $total) {
						$sql .= ',';
						$values .= ',';
					}
				}
				$sql .= ") VALUES ($values)";
				
				$sth = $dbh->prepare($sql);
				
				self::SetBlindValue($sth, $total, $arguments);
				self::Execute($sth);
				
				return $dbh->lastInsertId();
			} catch(PDOException $e) {
				throw $e;
			}
		}
		
		/**
		 * Delete and execute
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn, Maarten Oosting
		 */
		public static function Delete($tablename, $arguments)
		{
			try {
				$dbh = self::$_dbh;
				if (!$dbh) throw new PDOException('No database connection');
				
				if (!is_string($tablename)) throw new PDOException('Tablename is not a string'); 
				if (!is_array($arguments)) $arguments = array('id' => $arguments);
				
				$total = count($arguments);
				if ($total == 0) throw new PDOException('WHERE is empty'); 
				
				$sql = "DELETE FROM $tablename WHERE ";
				$sql .= self::SetInSQL($total, $arguments, 'AND');
				
				$sth = $dbh->prepare($sql);
				
				self::SetBlindValue($sth, $total, $arguments);
				self::Execute($sth);
				
				return TRUE;
			} catch(PDOException $e) { 
				throw $e;
			}	
		}
		
		/**
		 * Get backup of the database
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function Backup()
		{
			$output = '';
			$list = self::GetTableList();
			foreach ($list as $name) {
				$columns = self::GetTableColumns($name);
				$total = count($columns);
				$primary = FALSE;
				
				$output .= "CREATE TABLE $name(" . PHP_EOL;
				foreach ($columns as $i => $row) {
					$output .= $row['Field'] . " " . strtoupper($row['Type']) . " ";
					if ($row['Extra'] !== 'auto_increment') {
						$output .= ($row['Null'] == 'NO') ? "NOT NULL DEFAULT '{$row['Default']}'" : "DEFAULT NULL";
						if (!empty($row['Extra'])) $output .= " " . strtoupper($row['Extra']);	
					} else $output .= "NOT NULL AUTO_INCREMENT";
					
					if (($i + 1) < $total) $output .= ',' . PHP_EOL;
					if ($row['Key'] == 'PRI') $primary = $row['Field'];
				}
				
				if ($primary) $output .=  "," . PHP_EOL . "PRIMARY KEY ($primary)";
				$output .= PHP_EOL . ")";
				
				
				$output .= ";" . PHP_EOL . PHP_EOL;
			}
			
			// echo nl2br($output);
		}
		
		/**
		 * Get list of tables
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function GetTableList()
		{
			try {
				$dbh = self::$_dbh;
				if (!$dbh) throw new PDOException('No database connection');
				
				$sth = $dbh->query("SHOW TABLES");
				$list = $sth->fetchAll(PDO::FETCH_ASSOC);
				foreach ($list as $key => $value) {
					$name = key($value);
					$list[$key] = $value[$name];
				}	
				
				return $list;
			} catch(PDOException $e) {
				throw $e;
			}
		}
		
		/**
		 * Get columns of a table
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function GetTableColumns($name)
		{
			try {
				$dbh = self::$_dbh;
				if (!$dbh) throw new PDOException('No database connection');
				
				$sth = $dbh->query("SHOW COLUMNS IN $name");
				$arguments = $sth->fetchAll(PDO::FETCH_ASSOC);
				
				return $arguments;
			} catch(PDOException $e) {
				throw $e;
			}	
		}
		
		/**
		 * Get information of a table
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function GetTableInfo($name)
		{
			try {
				$dbh = self::$_dbh;
				if (!$dbh) throw new PDOException('No database connection');
				
				$sth = $dbh->query("SHOW COLUMNS IN $name");
				$arguments = $sth->fetchAll(PDO::FETCH_ASSOC);
				
				return $arguments;
			} catch(PDOException $e) {
				throw $e;
			}	
		}
		
		/**
		 * Get total of execute
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function GetTotal()
		{
			return self::$_sth->rowCount();
		}
		
		/**
		 * Execute and set data in class
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn
		 */
		private static function Execute($sth)
		{
			$sth->execute();
			self::$_sth = $sth;
			
			return $sth;
		}
		
		/**
		 * Create a SQL query
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn
		 */
		private static function SetInSQL($total, $arguments, $type, $insertion = '=')
		{
			$keys = array_keys($arguments);
			$sql = '';
			for ($i = 0; $i < $total; $i++) {
				$sql .= $keys[$i] . " $insertion ?";
				if (($i + 1) < $total) $sql .= " $type ";
			}
			
			return $sql;
		}
		
		/**
		 * Set data in bindValue
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn
		 */
		private static function SetBlindValue($sth, $total, $arguments, $key = 0)
		{
			$values = array_values($arguments);
			for ($i = 0; $i < $total; $i++) {
				$sth->bindValue(($key + $i + 1), self::Filter($values[$i]));
			}
		} 
	}

	class FTPSystem
	{
		/**
		 * This variable is for FTP handle
		 *
		 * @since 2.0
		 * @access private
		 * @author Kevin van Steijn
		 */
		private static $_ftp = FALSE;
		
		/**
		 * This variable is for the storage of FTP server
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static $server = '';
		
		/**
		 * This variable is for the storage of FTP user
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static $user = '';
		
		/**
		 * This variable is for the storage of FTP password
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static $password = '';
		
		/**
		 * This variable is for the storage of FTP path
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static $path = FALSE;
		
		/**
		 * Set a FTP connection
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public function __construct($ftp_server = FALSE, $ftp_user = FALSE, $ftp_password = FALSE)
		{
			if (!$ftp_server) $ftp_server = self::$server;
			if (!$ftp_user) $ftp_user = self::$user;	
			if (!$ftp_password) $ftp_password = self::$password;
			
			if (!empty($ftp_server) && !empty($ftp_user)) {
				self::$_ftp = @ftp_connect($ftp_server);
				if (self::$_ftp) {
					if (!@ftp_login(self::$_ftp, $ftp_user, $ftp_password)) {
						ftp_close(self::$_ftp);
						self::$_ftp = FALSE;
					}
				} else self::$_ftp = FALSE;
			}
		}
		
		/**
		 * Get connect status
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function GetConnectStatus()
		{
			return (self::$_ftp) ? TRUE : FALSE;
		}
		
		/**
		 * Get the correct path
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function CorrectPath($path, $number)
		{
			if (is_int($number)) {
				$expl = explode('/', $path);
				for($i = 0; $i <= $number; $i++) {
					unset($expl[$i]);
				}
				
				$path = implode('/', $expl);
			}
			
			return $path;
		}
		
		/**
		 * Create a folder
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function CreateFolder($path_root)
		{
			if (is_dir($path_root)) return TRUE;
			
			if (!is_dir($path_create = dirname($path_root))) {
				if (!self::CreateFolder($path_create)) return FALSE;
			}
			
			$ftp = self::$_ftp;
			if (!$ftp) return FALSE;
			
			return @ftp_mkdir($ftp, self::CorrectPath($path_root, self::$path));
		}
		
		/**
		 * Remove a folder
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function RemoveFolder($path_root)
		{
			if (!is_dir($path_root)) return FALSE;
			
			$ftp = self::$_ftp;
			if (!$ftp) return FALSE; 

			$path_correct = self::CorrectPath($path_root, self::$path);
			foreach (scandir($path_root) as $value) {
				if(!in_array($value, array('.', '..'))) {
					if (is_dir($path_delete = $path_root . '/' . $value)) {
						if (!self::RemoveFolder($path_delete)) return FALSE;
					} else if (!@ftp_delete($ftp, $path_correct . '/' . $value)) return FALSE;
				}
			}
			
			return @ftp_rmdir($ftp, $path_correct);
		}
		
		/**
		 * How the file should be handled
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function FileHandle($path, $content = '', $add = FALSE)
		{
			if (file_exists($path)) {
				return ($add) ? self::AddContent($path, $content) : self::WriteFile($path, $content);
			} else return self::CreateFile($path, $content);
		}
		
		/**
		 * Create a file
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function CreateFile($path, $content = '')
		{
			if (file_exists($path)) return TRUE;	
			
			$ftp = self::$_ftp;
			if (!$ftp) return FALSE;
			
			$tmp = tmpfile();
			if (!empty($content)) {
				fwrite($tmp, $content);
				fseek($tmp, 0);
			}
			
			$action = @ftp_fput($ftp, self::CorrectPath($path, self::$path), $tmp, FTP_ASCII);
			if (!empty($content)) fclose($tmp);
			
			return $action;
		}
		
		/**
		 * Overwrite a file
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function WriteFile($path, $content)
		{
			if (!file_exists($path)) return FALSE;
			
			$ftp = self::$_ftp;
			if (!$ftp) return FALSE;
			
			$tmp = tmpfile();
			fwrite($tmp, $content);
			fseek($tmp, 0);
			
			$action = @ftp_fput($ftp, self::CorrectPath($path, self::$path), $tmp, FTP_ASCII);
			fclose($tmp);
			
			return $action;
		}
		
		/**
		 * Upload a file
		 *
		 * @since 2.1
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function UploadFile($from, $to)
		{		
			if (file_exists($to)) return FALSE;	
			
			$ftp = self::$_ftp;
			if (!$ftp) return FALSE;

			return ftp_put($ftp, self::CorrectPath($to, self::$path), $from, FTP_ASCII);
		}
		
		/**
		 * Add content to a file
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function AddContent($path_root, $content_new)
		{
			if (!file_exists($path)) return FALSE;
			
			$ftp = self::$_ftp;
			if (!$ftp) return FALSE;
			
			if (!$handle = fopen($path_root, 'r')) return FALSE;
			if ($content_old = fread($handle, filesize($filename))) { 
				$tmp = tmpfile();
				fwrite($tmp, $content_old);
				fwrite($tmp, $content_new);
				fseek($tmp, 0);
		    
				$action = @ftp_fput($ftp, self::CorrectPath($path, self::$path), $tmp, FTP_ASCII);
				fclose($tmp);
			} else $action = FALSE;
			
			fclose($handle);
			
		    return $action;
		}
		
		/**
		 * Remove a file
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function RemoveFile($path)
		{
			if (!file_exists($path)) return FALSE;

			$ftp = self::$_ftp;
			if (!$ftp) return FALSE;
			
			return @ftp_delete($ftp, self::CorrectPath($path, self::$path));
		}
		
		/**
		 * How the remove should be handled
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function RemoveHandle($path)
		{
			return file_exists($path) ? self::RemoveFile($path) : self::RemoveFolder($path);
		}
	}
	
	class ServerSystem
	{
		/**
		 * Create a folder
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function CreateFolder($path_root)
		{
			if (is_dir($path_root)) return TRUE;
			
			$chmod = FALSE;
			if (is_dir($path_chmod = dirname($path_root))) {
				if (!is_writable($path_chmod)) {
					if(!@chmod($path_chmod, 0777)) return FALSE;
					$chmod = TRUE;
				}				
			}

			$action = @mkdir($path_root, 0755, TRUE);
			
			if ($chmod) @chmod($path_chmod, 0755);
			
			return $action;	
		}	
	
		/**
		 * Remove a folder
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function RemoveFolder($path_root)
		{
			if (!is_dir($path_root)) return FALSE;
			
			$chmod = FALSE;
			if (is_dir($path_chmod = dirname($path_root))) {
				if (!is_writable($path_chmod)) {
					if(!@chmod($path_chmod, 0777)) return FALSE;
					$chmod = TRUE;
				}				
			}

			$action = TRUE;
			foreach (scandir($path_root) as $value) {
				if(!in_array($value, array('.', '..'))) {
					$path_current = $path_root . '/' . $value;
					if (is_dir($path_current)) {
						if(!self::RemoveFolder($path_current)) {
							$action = FALSE;
							break;
						}
					} else if(!self::RemoveFile($path_current)) {
						$action = FALSE;
						break;
					}
				}
			}
			
			if ($action) $action = @rmdir($path_root);
			if ($chmod) @chmod($path_chmod, 0755);
			
			return $action;	
		}
		
		/**
		 * How the file should be handled
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function FileHandle($path, $content = '', $add = FALSE)
		{
			if (file_exists($path)) {
				return ($add) ? self::AddContent($path, $content) : self::WriteFile($path, $content);
			} else return self::CreateFile($path, $content);
		}
	
		/**
		 * Create a file
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function CreateFile($path_root, $content = '')
		{
			if (file_exists($path_root)) return TRUE;
			
			$chmod = FALSE;
			if (is_dir($path_chmod = dirname($path_root))) {
				if (!is_writable($path_chmod)) {
					if(!@chmod($path_chmod, 0777)) return FALSE;
					$chmod = TRUE;
				}				
			}
			
			if ($handle = fopen($path_root, 'w')) {
				$action = TRUE;
				if (!empty($content)) {
					if (!fwrite($handle, $content)) $action = FALSE;	
				}
				
				fclose($handle);
			} else $action = FALSE;
			
			if ($chmod) @chmod($path_chmod, 0755);
			
			return $action;		
		}
	
		/**
		 * Overwrite a file
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function WriteFile($path_root, $content)
		{
			if (!file_exists($path_root)) return FALSE;	
			
			$chmod = FALSE;
			if (is_dir($path_chmod = dirname($path_root))) {
				if (!is_writable($path_chmod)) {
					if(!@chmod($path_chmod, 0777)) return FALSE;
					$chmod = TRUE;
				}				
			}
			
			$action = FALSE;
			if ($handle = fopen($path_root, 'w')) {
				if (fwrite($handle, $content)) $action = TRUE;	
				fclose($handle);
			}
			
			if ($chmod) @chmod($path_chmod, 0755);
			
			return $action;
		}
		
		/**
		 * Add content to a file
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function AddContent($path_root, $content)
		{
			if (!file_exists($path_root)) return FALSE;	
			
			$chmod = FALSE;
			if (is_dir($path_chmod = dirname($path_root))) {
				if (!is_writable($path_chmod)) {
					if(!@chmod($path_chmod, 0777)) return FALSE;
					$chmod = TRUE;
				}				
			}
			
			$action = FALSE;
			if ($handle = fopen($path_root, 'a')) {
				if (fwrite($handle, $content)) $action = TRUE;	
				fclose($handle);
			}
			
			if ($chmod) @chmod($path_chmod, 0755);
			
			return $action;
		}
		
		/**
		 * Remove a file
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function RemoveFile($path_root)
		{
			if (!file_exists($path)) return FALSE;

			$chmod = FALSE;
			if (is_dir($path_chmod = dirname($path_root))) {
				if (!is_writable($path_chmod)) {
					if(!@chmod($path_chmod, 0777)) return FALSE;
					$chmod = TRUE;
				}				
			}
			
			$action = @unlink($path_root);
			
			if ($chmod) @chmod($path_chmod, 0755);
			
			return $action;
		}
		
		/**
		 * How the remove should be handled
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function RemoveHandle($path)
		{
			return file_exists($path) ? self::RemoveFile($path) : self::RemoveFolder($path);
		}
	}
	
	class FileSystem
	{		
		/**
		 * How file system must be handled
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function SwitchSystem()
		{
			$class = new FTPSystem();			
			return ($class->GetConnectStatus()) ? $class : new ServerSystem();
		}
		
		/**
		 * Folder validation
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function FolderValid($filename)
		{
			return preg_match("/^[a-zA-Z0-9_\/]+$/", $filename) ? TRUE : FALSE;
		}
		
		/**
		 * Create a log from a array
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function CreateLog($title, $arguments)
		{
			$log = '/**'. PHP_EOL;
			$log .= ' * ' . $title . PHP_EOL;
			$log .= ' * ' . date("F j, Y, g:i a") . PHP_EOL;
			$log .= ' */' .  PHP_EOL . PHP_EOL;
			
			foreach ($arguments as $key => $value) {
				if (is_array($value)) {
					if (count($value) > 0) {
						$log .= $key . PHP_EOL . PHP_EOL;
						foreach ($value as $text) $log .= $text . PHP_EOL;
					}
				} else $log .= $value . PHP_EOL . PHP_EOL;
			}
			
			return $log;
		}
	}
	
	class UpdateCheck
	{
		/**
		 * Version of the Updatecheck system
		 *
		 * @since 1.0
		 * @access private
		 * @author Kevin van Steijn
		 */
		private static $_version = '2.1';
		
		/**
		 * This variable is used for the storage of cancel for the update
		 *
		 * @since 2.0
		 * @access private
		 * @author Kevin van Steijn
		 */
		private static $_cancel = FALSE;
		
		/**
		 * This variable is used for the storage of information for the updates
		 *
		 * @since 1.2
		 * @access private
		 * @author Kevin van Steijn
		 */
		private static $_arg = array();
		
		/**
		 * This variable is used for the storage of error logs
		 *
		 * @since 1.2
		 * @access private
		 * @author Kevin van Steijn
		 */
		private static $_error = array();
		
		/**
		 * This variable is used for the storage of succes logs
		 *
		 * @since 2.0
		 * @access private
		 * @author Kevin van Steijn
		 */
		private static $_succes = array();
		
		/**
		 * This variable is used for the storage of log action
		 *
		 * @since 2.0
		 * @access private
		 * @author Kevin van Steijn
		 */
		private static $_log = FALSE;
	
		/**
		 * Add UpdateCheck to the UpdateCheck system
		 *
		 * @since 1.2
		 * @access public
		 * @author Kevin van Steijn
		 */
		public function __construct()
		{
			self::SetUpdate('http://code.kvansteijn.nl/updatecheck', self::$_version);	
		}
		
		/**
		 * Add a system to the UpdateCheck system 
		 *
		 * @since 1.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function SetUpdate($url, $version_current)
		{
			try {
				if ($content = self::GetContent($url . '/install.txt', 1)) {
					$version_list = explode(PHP_EOL . PHP_EOL, $content);				
					if (count($version_list) > 0) {
						$version_arg = array();
						
						$information = debug_backtrace();
						$dir = dirname($information[0]['file']);
						
						foreach ($version_list as $content) {
							$lines = explode(PHP_EOL, $content);
							$version_get = floatval($lines[0]);
							if ($version_get > 0) {
								unset($lines[0]);
								
								$version_arg["$version_get"] = array(
									'url' => $url . '/' . $version_get,
									'version' => $version_get,
									'dir' => $dir,
									'list' => $lines
								);
							} else throw new Exception('Incorrect install.txt from ' . $url);
						}
					}
					
					if (count($version_arg) > 0) {
						ksort($version_arg);
						foreach ($version_arg as $version_get => $arguments) {
							if ($version_current < $version_get) {
								self::$_arg[$url] = $arguments;
								break;
							}
						}
					}
				} else throw new Exception('Failed to load install.txt from ' . $url); 
			} catch (Exception $e) {
				self::$_error[] = $e;	
			}
		}
		
		/**
		 * Get content from a url
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function GetContent($url, $timeout)
		{
			$ctx = stream_context_create(array('http' => array('timeout' => $timeout)));
			return @file_get_contents($url, 0, $ctx);
		}
		
		/**
		 * Get list of updates
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function GetVersion()
		{
			return self::$_version;
		}
		
		/**
		 * Get list of updates
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function GetUpdates()
		{
			return self::$_arg;
		}
		
		/**
		 * Get succes logs
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function GetSuccesLogs()
		{
			return self::$_succes;
		}
		
		/**
		 * Get error logs
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function GetErrorLogs()
		{
			return self::$_error;
		}
		
		/**
		 * Get log status
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function GetLogStatus()
		{
			return self::$_log;
		}
		
		/**
		 * Set log status
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function SetLogStatus($action)
		{
			if (!is_bool($action)) return;
			
			self::$_log = $action;
		}
		
		/**
		 * Set update function on or off
		 *
		 * @since 2.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function SetCancel($action)
		{
			if (!is_bool($action)) return;
			
			self::$_cancel = $action;
		}
		
		/**
		 * How the update must be handled
		 *
		 * @since 2.0
		 * @access private
		 * @author Kevin van Steijn
		 */
		private static function SwitchAction($action, $path_root, $path_given, $update_current, $update_class)
		{
			$log = $path_given . ' in ' . $path_root;
			$path_current = $path_root . '/' . $path_given;
			
			switch($action) {
				case 'create_folder':
					if ($update_class->CreateFolder($path_current)) return 'Create folder(s) ' . $log;
					throw new Exception('Failed to create folder(s) ' . $log);
					break;
				case 'remove_folder':
					if ($update_class->RemoveFolder($path_current)) return 'Remove folder ' . $log;
					throw new Exception('Failed to remove folder ' . $log);
					break;
				case 'create_file':
					$content = self::GetContent($update_current['url'] . '/' . $path_given, 1);
					if (!$content) $content = '';
					
					if ($update_class->CreateFile($path_current, $content)) return 'Create file ' . $log;
					throw new Exception('Failed to create file ' . $log);
					break;
				case 'write_file':
					if ($content = self::GetContent($update_current['url'] . '/' . $path_given, 1)) {
						if ($update_class->WriteFile($path_current, $content)) return 'Write file ' . $log;
					}
					throw new Exception('Failed to write file ' . $log);
					break;
				case 'remove_file':
					if ($update_class->RemoveFile($path_current)) return 'Remove file ' . $log;
					throw new Exception('Failed to remove file ' . $log);
					break;
				case 'remove':
					$path_type = $update_current['type'];
					if ($update_class->RemoveHandle($path_current)) return 'Remove ' . $path_type . ' ' . $log;
					throw new Exception('Failed to remove ' . $path_type . ' ' . $log);
					break;
				default:
					try {
						if (!is_dir(dirname($path_current))) {
							if ($output = self::SwitchAction('create_folder', $path_root, dirname($path_given), $update_current, $update_class)) {
								self::$_succes[] = $output;
							}
						}
						
						$url = $update_current['url'];
						if ($content = self::GetContent($url . '/' . $path_given, 1)) {
							if ($update_class->FileHandle($path_current, $content)) {
								return 'Create file ' . $log;
							} else throw new Exception('Failed to update/create file ' . $log);
						} else throw new Exception('Failed to load ' . $path_given .' from ' . $url);
					} catch (Exception $e) {
						self::$_error[] = $e;
						throw new Exception('Failed to update/create file ' . $log);
					}
					break;
			}
		}
		
		/**
		 * Start Update
		 *
		 * @since 1.0
		 * @access public
		 * @author Kevin van Steijn
		 */
		public static function Update()
		{
			if (self::$_cancel) return;
		
			self::$_error = array();
			$update_arg = self::$_arg;
			
			if (count($update_arg) == 0) return;
			
			$update_class = FileSystem::SwitchSystem();
			$update_log = self::$_log;		
			foreach ($update_arg as $url => $update_current) {
				$path_root = $update_current['dir'];
				$update_error = array();
				$update_succes = array();
					
				foreach ($update_current['list'] as $path_given) {
					if (strpos($path_given, '.') !== FALSE) {
						$expl = explode('.', $path_given);
						if (end($expl) == 'txt' && ($amount = count($expl)) > 2) unset($expl[($amount - 1)]);
						$path_given = implode('.', $expl);
					}
					
					$path_type = FileSystem::FolderValid($path_given) ? 'folder' : 'file';
					if (strpos($path_given, ' ') !== FALSE) {
						$expl = explode(' ', $path_given);
						$action = $expl[0];
						
						unset($expl[0]);
						$path_given = implode('/', $expl);
					} else $action = ($path_type == 'folder') ? 'create_folder' : 'default';
					
					try {
						$update_current['type'] = $path_type;	
						$output = self::SwitchAction($action, $path_root, $path_given, $update_current, $update_class);
						self::$_succes[] = $output;
						$update_succes[] = $output;
					} catch (Exception $e) {
						self::$_error[] = $e;
						$update_error[] = $e->getMessage();
					}
				}
				
				if ($update_log) {
					try {
						$log = FileSystem::CreateLog('Version ' . $arg['version'], 
							array('Succes' => $update_succes, 'Error' => $update_error)
						);
						
						if (!$update_class->FileHandle($path_root . '/log.txt', $log, TRUE))
							throw new Exception('Failed to update/create file log.txt in ' . $path_root);
					} catch (Exception $e) {
						self::$_error[] = $e;	
					}
				}
			}
		} 
	}

?>
