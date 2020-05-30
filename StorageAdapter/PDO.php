<?php

namespace Securimage\StorageAdapter;

use Securimage\StorageAdapter\AdapterInterface;

class PDO implements AdapterInterface
{
    protected $database_driver;
    protected $database_host;
    protected $database_user;
    protected $database_pass;
    protected $database_name;
    protected $database_table;
    protected $database_file;
    protected $skip_table_check;
    protected $pdo_conn;
    protected $expiry_time;

    public function __construct($options = null)
    {
        if (!empty($options) && is_array($options)) {
            foreach($options as $name => $val) {
                if (property_exists($this, $name)) {
                    $this->$name = $val;
                }
            }
        }

        $this->bootstrap();
    }

    public function store($captchaId, $captchaInfo)
    {
        return $this->saveCodeToDatabase($captchaId, $captchaInfo);
    }

    public function storeAudioData($captchaId, $audioData)
    {
        return $this->saveAudioToDatabase($captchaId, $audioData);
    }

    public function get($captchaId, $what = null)
    {
        $code = $this->getCodeFromDatabase($captchaId);

        if ($code) {
            $info = new \Securimage\CaptchaObject;
            $info->captchaId         = $captchaId;
            $info->code              = $code['code'];
            $info->code_display      = $code['code_display'];
            $info->creationTime      = (int)$code['created'];
            $info->captchaImageAudio = $code['audio_data'];

            return $info;
        }

        return null;
    }

    public function delete($captchaId)
    {
        return $this->clearCodeFromDatabase($captchaId);
    }

    protected function bootstrap()
    {
        if ($this->openDatabase()) {
            if (mt_rand(0, 1000) % 100 === 0) {
                // approximately once per 100 connections
                $this->purgeOldCodesFromDatabase();
            }
        }
    }

    /**
     * Get the PDO DSN string for connecting to the database
     *
     * @see Securimage::$database_driver Database driver
     * @throws Exception  If database specific options are not configured
     * @return string     The DSN for connecting to the database
     */
    protected function getDsn()
    {
        $dsn = sprintf('%s:', $this->database_driver);

        switch($this->database_driver) {
            case \Securimage::SI_DRIVER_SQLITE3:
                if (empty($this->database_file)) {
                    throw new \Exception('Database adapter "database_file" option cannot be empty when using SQLite3');
                }

                $dsn .= $this->database_file;
                break;

            case \Securimage::SI_DRIVER_MYSQL:
            case \Securimage::SI_DRIVER_PGSQL:
                if (empty($this->database_host)) {
                    throw new \Exception('Database adapter "database_host" option cannot be empty');
                } else if (empty($this->database_name)) {
                    throw new \Exception('Database adapter "database_name" option cannot be empty');
                } else if (empty($this->database_user)) {
                    throw new \Exception('Database adapter "database_user" option cannot be empty');
                }

                $dsn .= sprintf('host=%s;dbname=%s',
                                $this->database_host,
                                $this->database_name);
                break;
        }

        return $dsn;
    }

    /**
     * Opens a connection to the configured database.
     *
     * @see Securimage::$use_database Use database
     * @see Securimage::$database_driver Database driver
     * @see Securimage::$pdo_conn pdo_conn
     * @return bool true if the database connection was successful, false if not
     */
    protected function openDatabase()
    {
        $this->pdo_conn = false;

        $pdo_extension = 'PDO_' . strtoupper($this->database_driver);

        if (!extension_loaded($pdo_extension)) {
            trigger_error("Database support is turned on in Securimage, but the chosen extension $pdo_extension is not loaded in PHP.", E_USER_WARNING);
            return false;
        }

        if ($this->database_driver == \Securimage::SI_DRIVER_SQLITE3) {
            if (!file_exists($this->database_file)) {
                $fp = fopen($this->database_file, 'w+');
                if (!$fp) {
                    $err = error_get_last();
                    trigger_error("Securimage failed to create SQLite3 database file '{$this->database_file}'. Reason: {$err['message']}", E_USER_WARNING);
                    return false;
                }
                fclose($fp);
                chmod($this->database_file, 0666);
            } else if (!is_writeable($this->database_file)) {
                trigger_error("Securimage does not have read/write access to database file '{$this->database_file}. Make sure permissions are 0666 and writeable by user '" . get_current_user() . "'", E_USER_WARNING);
                return false;
            }
        }

        try {
            $dsn = $this->getDsn();

            $options = array();
            $this->pdo_conn = new \PDO($dsn, $this->database_user, $this->database_pass, $options);
        } catch (\PDOException $pdoex) {
            trigger_error("Database connection failed: " . $pdoex->getMessage(), E_USER_WARNING);
            return false;
        } catch (\Exception $ex) {
            trigger_error($ex->getMessage(), E_USER_WARNING);
            return false;
        }

        try {
            if (!$this->skip_table_check && !$this->checkTablesExist()) {
                // create tables...
                $this->createDatabaseTables();
            }
        } catch (\Exception $ex) {
            trigger_error($ex->getMessage(), E_USER_WARNING);
            $this->pdo_conn = false;
            return false;
        }

        return true;
    }

    /**
     * Checks if the necessary database tables for storing captcha codes exist
     *
     * @throws Exception If the table check failed for some reason
     * @return boolean true if the database do exist, false if not
     */
    protected function checkTablesExist()
    {
        $table = $this->pdo_conn->quote($this->database_table);

        switch($this->database_driver) {
            case \Securimage::SI_DRIVER_SQLITE3:
                // query row count for sqlite, PRAGMA queries seem to return no
                // rowCount using PDO even if there are rows returned
                $query = "SELECT COUNT(id) FROM $table";
                break;

            case \Securimage::SI_DRIVER_MYSQL:
                $query = "SHOW TABLES LIKE $table";
                break;

            case \Securimage::SI_DRIVER_PGSQL:
                $query = "SELECT * FROM information_schema.columns WHERE table_name = $table;";
                break;
        }

        $result = $this->pdo_conn->query($query);

        if (!$result) {
            $err = $this->pdo_conn->errorInfo();

            if ($this->database_driver == \Securimage::SI_DRIVER_SQLITE3 &&
                $err[1] === 1 && strpos($err[2], 'no such table') !== false)
            {
                return false;
            }

            throw new \Exception("Failed to check tables: {$err[0]} - {$err[1]}: {$err[2]}");
        } else if ($this->database_driver == \Securimage::SI_DRIVER_SQLITE3) {
            // successful here regardless of row count for sqlite
            return true;
        } else if ($result->rowCount() == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Create the necessary databaes table for storing captcha codes.
     *
     * Based on the database adapter used, the tables will created in the existing connection.
     *
     * @see Securimage::$database_driver Database driver
     * @return boolean true if the tables were created, false if not
     */
    protected function createDatabaseTables()
    {
        $queries = array();

        switch($this->database_driver) {
            case \Securimage::SI_DRIVER_SQLITE3:
                $queries[] =
                  "CREATE TABLE \"{$this->database_table}\" (
                   id VARCHAR(40),
                   namespace VARCHAR(32) NOT NULL,
                   code VARCHAR(32) NOT NULL,
                   code_display VARCHAR(32) NOT NULL,
                   created INTEGER NOT NULL,
                   audio_data BLOB NULL,
                   PRIMARY KEY(id)
                )";

                $queries[] = "CREATE INDEX ndx_created ON {$this->database_table} (created)";
                break;

            case \Securimage::SI_DRIVER_MYSQL:
                $queries[] =
                  "CREATE TABLE `{$this->database_table}` (
                  `id` VARCHAR(40) NOT NULL,
                  `namespace` VARCHAR(32) NOT NULL,
                  `code` VARCHAR(32) NOT NULL,
                  `code_display` VARCHAR(32) NOT NULL,
                  `created` INT NOT NULL,
                  `audio_data` MEDIUMBLOB NULL,
                  PRIMARY KEY(id),
                  INDEX(created)
                )";

                break;

            case \Securimage::SI_DRIVER_PGSQL:
                $queries[] =
                  "CREATE TABLE {$this->database_table} (
                  id character varying(40) NOT NULL,
                  namespace character varying(32) NOT NULL,
                  code character varying(32) NOT NULL,
                  code_display character varying(32) NOT NULL,
                  created integer NOT NULL,
                  audio_data bytea NULL,
                  CONSTRAINT pkey_id PRIMARY KEY (id)
                )";

                $queries[] = "CREATE INDEX ndx_created ON {$this->database_table} (created);";
                break;
        }

        $this->pdo_conn->beginTransaction();

        foreach($queries as $query) {
            $result = $this->pdo_conn->query($query);

            if (!$result) {
                $err = $this->pdo_conn->errorInfo();
                trigger_error("Failed to create table.  {$err[1]}: {$err[2]}", E_USER_WARNING);
                $this->pdo_conn->rollBack();
                $this->pdo_conn = false;
                return false;
            }
        }

        $this->pdo_conn->commit();

        return true;
    }

    /**
     * Saves the CAPTCHA data to the configured database.
     */
    protected function saveCodeToDatabase($captchaId, $captchaInfo)
    {
        $success = false;

        if ($this->pdo_conn) {
            $time      = $captchaInfo->creationTime;
            $code      = $captchaInfo->code;
            $code_disp = $captchaInfo->code_display;

            // This is somewhat expensive in PDO Sqlite3 (when there is something to delete)
            // Clears previous captcha for this client from database so we can do a straight insert
            // without having to do INSERT ... ON DUPLICATE KEY or a find/update
            $this->clearCodeFromDatabase($captchaId);

            $query = "INSERT INTO {$this->database_table} (id, code, code_display, namespace, created) VALUES(?, ?, ?, '', ?)";

            $stmt    = $this->pdo_conn->prepare($query);
            $success = $stmt->execute(array($captchaId, $code, $code_disp, $time));

            if (!$success) {
                $err   = $stmt->errorInfo();
                $error = "Failed to insert code into database. {$err[1]}: {$err[2]}.";

                if ($this->database_driver == \Securimage::SI_DRIVER_SQLITE3) {
                    $err14 = ($err[1] == 14);
                    if ($err14) {
                        $error .= sprintf(
                            " Ensure database directory and file are writeable by user '%s' (%d).",
                            get_current_user(), getmyuid()
                        );
                    }
                }

                trigger_error($error, E_USER_WARNING);
            }
        }

        return $success !== false;
    }

    /**
     * Saves CAPTCHA audio to the configured database
     *
     * @param string $data Audio data
     * @return boolean true on success, false on failure
     */
    protected function saveAudioToDatabase($captchaId, $data)
    {
        $success = false;

        if ($this->pdo_conn) {
            $query = "UPDATE {$this->database_table} SET audio_data = :audioData WHERE id = :id";
            $stmt  = $this->pdo_conn->prepare($query);
            $stmt->bindParam(':audioData', $data, \PDO::PARAM_LOB);
            $stmt->bindParam(':id', $captchaId);
            $success = $stmt->execute();
        }

        return $success !== false;
    }

    /**
     * Retrieves a stored code from the database for based on the captchaId or
     * IP address if captcha ID not used.
     *
     * @return string|array Empty string if no code was found or has expired,
     * otherwise returns array of code information.
     */
    protected function getCodeFromDatabase($captchaId)
    {
        $code = '';

        if ($this->pdo_conn) {
            $query  = "SELECT * FROM {$this->database_table} WHERE id = ?";
            $stmt   = $this->pdo_conn->prepare($query);
            $result = $stmt->execute(array($captchaId));

            if (!$result) {
                $err = $this->pdo_conn->errorInfo();
                trigger_error("Failed to select code from database.  {$err[0]}: {$err[1]}", E_USER_WARNING);
            } else {
                if ( ($row = $stmt->fetch()) !== false ) {
                    if ($this->database_driver == \Securimage::SI_DRIVER_PGSQL && is_resource($row['audio_data'])) {
                        // pg bytea data returned as stream resource
                        $data = '';
                        while (!feof($row['audio_data'])) {
                            $data .= fgets($row['audio_data']);
                        }
                        $row['audio_data'] = $data;
                    }

                    $code = array(
                        'code'         => $row['code'],
                        'code_display' => $row['code_display'],
                        'created'      => $row['created'],
                        'audio_data'   => $row['audio_data'],
                    );
                }
            }
        }

        return $code;
    }

    /**
     * Remove a stored code from the database based on captchaId or IP address.
     */
    protected function clearCodeFromDatabase($captchaId)
    {
        if ($this->pdo_conn) {
            $id = $this->pdo_conn->quote($captchaId);

            $query = sprintf("DELETE FROM %s WHERE id = %s",
                $this->database_table, $id
            );

            $result = $this->pdo_conn->query($query);
            if (!$result) {
                trigger_error("Failed to delete code from database.", E_USER_WARNING);
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * Deletes old (expired) codes from the database
     */
    protected function purgeOldCodesFromDatabase()
    {
        $result = 0;

        if ($this->pdo_conn) {
            $now   = time();
            $limit = (!is_numeric($this->expiry_time) || $this->expiry_time < 1) ? 86400 : $this->expiry_time;

            $query = sprintf("DELETE FROM %s WHERE %s - created > %s",
                $this->database_table,
                $now,
                $this->pdo_conn->quote("$limit", \PDO::PARAM_INT)
            );

            $result = $this->pdo_conn->query($query);
        }

        return $result;
    }
}
