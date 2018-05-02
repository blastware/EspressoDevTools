<?php

namespace Blastware\EspressoDevTools\Domain\Services;

use Exception;
use PDO;
use PDOStatement;
use ZipArchive;

/**
 * MySQLBackup
 * Backup your MySQL databases by selecting tables (or not) and using compression (zip or gzip) !
 *
 * @author  ShevAbam <me@shevarezo.fr>
 * @link    https://github.com/shevabam/mysql-backup
 * @license GNU GPL 2.0
 */
class MySQLBackup
{

    /**
     * Database information
     *
     * @var array
     */
    public $db = array(
        'host'     => null,
        'port'     => null,
        'user'     => null,
        'password' => null,
        'name'     => null,
    );

    /**
     * Database connection link
     *
     * @var PDO $dbh
     */
    private $dbh;

    /**
     * Tables list
     *
     * @var array
     */
    public $tables = array();

    /**
     * Excluded tables list
     *
     * @var array
     */
    public $excludedTables = array();

    /**
     * Filename
     *
     * @var string
     */
    public $filename = 'dump';

    /**
     * Filename extension
     *
     * @var string
     */
    public $extension = 'sql';

    /**
     * Is file is delete at the end ?
     *
     * @var boolean
     */
    public $deleteFile = false;

    /**
     * Is file is downloaded automatically ?
     *
     * @var boolean
     */
    public $downloadFile = false;

    /**
     * Compress file format
     *
     * @var null
     */
    public $compressFormat;

    /**
     * Available compress formats
     *
     * @var array
     */
    public $compressAvailable = array('zip', 'gz', 'gzip');

    /**
     * Dump table structure ?
     *
     * @var boolean
     */
    public $dumpStructure = true;

    /**
     * Dump table data ?
     *
     * @var boolean
     */
    public $dumpData = true;

    /**
     * Add DROP TABLE IF EXISTS before CREATE TABLE ?
     *
     * @var boolean
     */
    public $addDropTable = true;

    /**
     * Add IF NOT EXISTS in CREATE TABLE statement ?
     *
     * @var boolean
     */
    public $addIfNotExists = true;

    /**
     * Add CREATE DATABASE IF NOT EXISTS ?
     *
     * @var boolean
     */
    public $addCreateDatabaseIfNotExists = true;


    /**
     * Initialization
     *
     * @param string $host     SQL host
     * @param string $user     Username
     * @param string $password Password
     * @param string $db       DB name
     * @param int    $port
     */
    public function __construct($host, $user, $password, $db, $port = 3306)
    {
        $this->db = array(
            'host'     => $host,
            'port'     => $port,
            'user'     => $user,
            'password' => $password,
            'name'     => $db,
        );
        $this->filename = 'dump_' . $db . '_' . date('Ymd-H\hi');
        // Connection to the database
        $this->databaseConnect();
    }


    /**
     * Database connection link
     */
    private function databaseConnect()
    {
        $dsn = 'mysql:host=' . $this->db['host'] . ';port=' . $this->db['port'] . ';dbname=' . $this->db['name'];
        $options = array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            PDO::ATTR_PERSISTENT         => true,
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        );
        // Create a new PDO instance
        try {
            $this->dbh = new PDO($dsn, $this->db['user'], $this->db['password'], $options);
        } // Catch any errors
        catch (Exception $e) {
            exit($e->getMessage());
        }
    }


    /**
     * Query fetcher
     *
     * @param  string  $q        Query
     * @param  boolean $fetchAll fetchAll or fetch
     * @return PDO|array|bool
     */
    private function query($q, $fetchAll = true)
    {
        $stmt = $this->dbh->query($q);
        if ($fetchAll === true) {
            return $stmt->fetchAll();
        }
        return $stmt->fetch();
    }


    /**
     * Set filename (default : dump_{db name}_{yymmdd-HHhMM}.sql)
     *
     * @param  string $name Filename
     * @return object MySQLBackup
     */
    public function setFilename($name)
    {
        $this->filename = $name;
        return $this;
    }


    /**
     * Set download file (default : false)
     *
     * @param  bool $p Allow to download file or not
     * @return object MySQLBackup
     */
    public function setDownload($p)
    {
        $this->downloadFile = $p;
        return $this;
    }


    /**
     * Set compress file format (default : null - no compress)
     *
     * @param  string $p Compress format available in $this->compressAvailable
     * @return object MySQLBackup
     */
    public function setCompress($p)
    {
        if (in_array($p, $this->compressAvailable, true)) {
            $this->compressFormat = $p;
        }
        return $this;
    }


    /**
     * Set delete file (default : false)
     *
     * @param  bool $p Allow to delete file or not
     * @return object MySQLBackup
     */
    public function setDelete($p)
    {
        $this->deleteFile = $p;
        return $this;
    }


    /**
     * Dump the structure ? (default : true)
     *
     * @param  bool $p Dump structure or not
     * @return object MySQLBackup
     */
    public function setDumpStructure($p)
    {
        $this->dumpStructure = $p;
        return $this;
    }


    /**
     * Dump the data ? (default : true)
     *
     * @param  bool $p Dump data or not
     * @return object MySQLBackup
     */
    public function setDumpData($p)
    {
        $this->dumpData = $p;
        return $this;
    }


    /**
     * Add DROP TABLE IF EXISTS before CREATE TABLE statement (default : true)
     *
     * @param  bool $p Add DROP TABLE IF EXISTS or not
     * @return object MySQLBackup
     */
    public function addDropTable($p)
    {
        $this->addDropTable = $p;
        return $this;
    }


    /**
     * Add "IF NOT EXISTS" after CREATE TABLE statement (default : true)
     *
     * @param  bool $p Add IF NOT EXISTS or not
     * @return object MySQLBackup
     */
    public function addIfNotExists($p)
    {
        $this->addIfNotExists = $p;
        return $this;
    }


    /**
     * Add "CREATE DATABASE IF NOT EXISTS" (default : true)
     *
     * @param  bool $p Add CREATE DATABASE IF NOT EXISTS or not
     * @return object MySQLBackup
     */
    public function addCreateDatabaseIfNotExists($p)
    {
        $this->addCreateDatabaseIfNotExists = $p;
        return $this;
    }


    /**
     * Add table name to dump
     *
     * @param  string $table Table name to dump
     * @return object MySQLBackup
     */
    public function addTable($table)
    {
        if (! in_array($table, $this->tables, true)) {
            $this->tables[] = $table;
        }
        return $this;
    }


    /**
     * Dump selected tables
     *
     * @param  array $tables Tables to backup
     * @return object MySQLBackup
     */
    public function addTables(array $tables)
    {
        if (is_array($tables) && count($tables) > 0) {
            foreach ($tables as $t) {
                $this->addTable($t);
            }
        }
        return $this;
    }


    /**
     * Dump all tables
     *
     * @return object MySQLBackup
     */
    public function addAllTables()
    {
        $result = $this->query('SHOW TABLES');
        foreach ($result as $row) {
            $this->addTable($row[0]);
        }
        return $this;
    }


    /**
     * Exclude tables
     *
     * @param array $tables
     * @return object MySQLBackup
     */
    public function excludeTables(array $tables)
    {
        if (is_array($tables) && count($tables) > 0) {
            $this->excludedTables = $tables;
        }
        return $this;
    }


    /**
     * @param string $table
     * @param bool   $as_array
     * @return array|string
     */
    public function getTableFieldNames($table, $as_array = false)
    {
        if ($this->tables === array()) {
            $this->addAllTables();
        }
        $query = $this->dbh->query("DESCRIBE {$table}");
        $table_fields = $query->fetchAll(PDO::FETCH_COLUMN);
        if ($as_array) {
            return $table_fields;
        }
        return '`' . implode('`, `', $table_fields) . '`';
    }

    /**
     * Dump SQL database with selected tables
     *
     * @throws Exception
     */
    public function dump()
    {
        $return = '';
        if ($this->tables === array()) {
            $this->addAllTables();
        }
        $return .= "--\n";
        $return .= '-- Backup ' . $this->db['name'] . ' - ' . date('Y-m-d H:i:s') . "\n";
        $return .= "--\n\n\n";
        $return .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $return .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $return .= "\n\n";
        if ($this->addCreateDatabaseIfNotExists === true) {
            $return .= 'CREATE DATABASE IF NOT EXISTS `' . $this->db['name'] . "`;\n";
            $return .= 'USE `' . $this->db['name'] . '`;';
            $return .= "\n\n\n";
        }
        foreach ($this->tables as $table) {
            // We skip excluded tables
            if (in_array($table, $this->excludedTables, true)) {
                continue;
            }
            /** @var PDOStatement $query */
            $query = $this->dbh->query("SELECT * FROM `{$table}`");
            $query->execute();
            $num_fields = $query->columnCount();
            $return .= "--\n";
            $return .= "-- Table structure for table `{$table}`\n";
            $return .= "--\n\n";
            // Dump structure ?
            if ($this->dumpStructure === true) {
                // Add DROP TABLE ?
                if ($this->addDropTable === true) {
                    $return .= "DROP TABLE IF EXISTS `{$table}`;\n";
                }
                $create_table_q = $this->query('SHOW CREATE TABLE `' . $table . '`', false);
                $create_table = $create_table_q[1];
                // Add IF NOT EXISTS ?
                if ($this->addIfNotExists === true) {
                    $create_table = preg_replace(
                        '/^CREATE TABLE/',
                        'CREATE TABLE IF NOT EXISTS',
                        $create_table
                    );
                }
                $return .= $create_table . ";\n\n";
            }
            // Dump data ?
            if ($this->dumpData === true) {
                $data = $query->fetchAll();
                if (empty($data)) {
                    continue;
                }
                $fields = $this->getTableFieldNames($table);
                $return .= "--\n";
                $return .= '-- Dumping data for table ' . $table . "\n";
                $return .= "--\n\n";
                $return .= "INSERT INTO `{$table}` ({$fields}) VALUES ";
                foreach ($data as $row) {
                    $return .= "\n(";
                    for ($i = 0; $i < $num_fields; $i++) {
                        $column_meta = $query->getColumnMeta($i);
                        $int = isset($column_meta['native_type'])
                               && in_array(
                                   $column_meta['native_type'],
                                   array('TINY', 'SHORT', 'LONG', 'LONGLONG', 'INT24'),
                                   true
                               );
                        $return .= $int
                            ? ''
                            : "'";
                        if (isset($row[ $i ])) {
                            $return .= str_replace(PHP_EOL, '\n', addslashes($row[ $i ]));
                        } else {
                            $return .= 'NULL';
                        }
                        $return .= $int
                            ? ''
                            : "'";
                        if ($i < ($num_fields - 1)) {
                            $return .= ', ';
                        }
                    }
                    $return .= '),';
                }
                $return = rtrim($return, ',');
                $return .= ";\n\n";
                $return .= '-- --------------------------------------------------------';
                $return .= "\n\n";
            }
        }
        // Save content in file
        $written = file_put_contents($this->filepath(), $return);
        if ($written === false) {
            return false;
        }
        // Zip the file ?
        if ($this->compressFormat !== null) {
            $this->compress();
        }
        // Download the file ?
        if ($this->downloadFile === true) {
            $this->download();
        }
        // Delete the file ?
        if ($this->deleteFile === true) {
            $this->delete();
        }
        return true;
    }


    /**
     * Download the dump file
     *
     * @param string $extension
     * @param bool   $full_path
     * @return string
     */
    private function filepath($extension = '', $full_path = true)
    {
        $extension = $extension !== ''
            ? $extension
            : $this->extension;
        $filename = $full_path
            ? $this->filename
            : basename($this->filename);
        return $filename . '.' . trim($extension, '.');
    }


    /**
     * Download the dump file
     */
    private function download()
    {
        header('Content-disposition: attachment; filename="' . $this->filepath() . '"');
        header('Content-type: application/octet-stream');
        readfile($this->filepath());
    }


    /**
     * Compress the file
     *
     * @throws Exception
     */
    private function compress()
    {
        switch ($this->compressFormat) {
            case 'zip':
                if (class_exists('ZipArchive')) {
                    $zip = new ZipArchive();
                    if ($zip->open($this->filepath('zip'), ZipArchive::CREATE) === true) {
                        $zip->addFile($this->filepath(), $this->filepath('', false));
                        $zip->close();
                        // We delete the sql file
                        $this->delete();
                        // Changing file extension to zip
                        $this->extension = 'zip';
                    }
                } else {
                    throw new Exception('ZipArchive object does not exists');
                }
                break;
            case 'gz':
            case 'gzip':
                $content = file_get_contents($this->filepath());
                file_put_contents($this->filepath('sql.gz'), gzencode($content, 9));
                // We delete the sql file
                $this->delete();
                // Changing file extension to gzip
                $this->extension = 'sql.gz';
                break;
        }
    }


    /**
     * Delete the file
     */
    private function delete()
    {
        if (file_exists($this->filepath())) {
            unlink($this->filepath());
        }
    }


    /**
     * @param string $file
     * @since $VID:$
     * @return array|bool|string
     */
    public function restore($file = '')
    {
        $file = $file !== ''
            ? $file
            : $this->filepath();
        if (file_exists($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        } else {
            return 'Could not locate a valid backup file, please upload one to ' . $this->filepath();
        }
        $buffer = '';
        foreach ($lines as $line) {
            $line = ltrim($line);
            // Skipping comments
            if (strpos($line, '--') === 0 || strpos($line, '#') === 0) {
                continue;
            }
            // Skip empty lines
            if ($line === '') {
                continue;
            }
            // multi-line query
            if ($line[ strlen($line) - 1 ] !== ';') {
                $buffer .= $line;
                continue;
            }
            if ($buffer) {
                $line = $buffer . $line;
                // reset the buffer
                $buffer = '';
            }
            $result = $this->dbh->query(str_replace('\n', PHP_EOL, $line));
            if (! $result) {
                return $this->dbh->errorInfo();
            }
        }
        return true;
    }
}
