<?php

namespace Securimage\StorageAdapter;

use Securimage\StorageAdapter\AdapterInterface;

class Mysqli implements AdapterInterface
{
    protected $database_host;
    protected $database_user;
    protected $database_pass;
    protected $database_name;
    protected $database_table;
    protected $database_file;
    protected $skip_table_check;
    protected $mysqli_conn;
    protected $expiry_time = 900;

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
     * Opens a connection to the configured database.
     *
     * @see Securimage::$use_database Use database
     * @see Securimage::$database_driver Database driver
     *
     * @return bool true if the database connection was successful, false if not
     */
    protected function openDatabase()
    {
        if ($this->mysqli_conn && is_object($this->mysqli_conn) && $this->mysqli_conn instanceof \mysqli) {
            return true;
        }

        if (!extension_loaded('mysqli')) {
            trigger_error("Mysqli adapter is enabled in Securimage, but the mysqli extension is not loaded in PHP.", E_USER_WARNING);
            return false;
        }

        $this->mysqli_conn = new \mysqli($this->database_host, $this->database_user, $this->database_pass, $this->database_name);

        if (mysqli_connect_error()) {
            trigger_error("Mysqli database connection failed.  Error " . mysqli_connect_errno() . ': ' . mysqli_connect_error(), E_USER_WARNING);
            $this->mysqli_conn = null;
            return false;
        }

        try {
            if (!$this->skip_table_check && !$this->checkTablesExist()) {
                // create tables...
                $this->createDatabaseTables();
            }
        } catch (\Exception $ex) {
            trigger_error($ex->getMessage(), E_USER_WARNING);
            $this->mysqli_conn = null;
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
        $table  = $this->database_table;
        $query  = "SHOW TABLES LIKE '$table'";
        $result = $this->mysqli_conn->query($query);

        if (!$result) {
            $errno = $this->mysqli_conn->errno;
            $error = $this->mysqli_conn->error;

            throw new \Exception("Failed to check Securimage tables.  Error {$errno}: {$error}");
        } else if ($result->num_rows < 1) {
            $result->free();
            return false;
        } else {
            $result->free();
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
        $query =
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

        $result = $this->mysqli_conn->query($query);

        if (!$result) {
            $errno = $this->mysqli_conn->errno;
            $error = $this->mysqli_conn->error;

            trigger_error("Failed to create table.  Error {$errno}: {$error}", E_USER_WARNING);
            $this->mysqli_conn = null;
            return false;
        }

        return true;
    }

    /**
     * Saves the CAPTCHA data to the configured database.
     */
    protected function saveCodeToDatabase($captchaId, $captchaInfo)
    {
        $success = false;

        if ($this->mysqli_conn) {
            $time      = $captchaInfo->creationTime;
            $code      = $captchaInfo->code;
            $code_disp = $captchaInfo->code_display;

            $query = "REPLACE INTO {$this->database_table} (id, code, code_display, namespace, created) VALUES(?, ?, ?, '', ?)";
            $stmt  = $this->mysqli_conn->prepare($query);
            $stmt->bind_param('sssi', $captchaId, $code, $code_disp, $time);

            $success = $stmt->execute();

            if (!$success) {
                $errno = $stmt->errno;
                $error = $stmt->error;

                trigger_error("Failed to insert code into database.  Error {$errno}: {$error}", E_USER_WARNING);
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

        if ($this->mysqli_conn) {
            $query = "UPDATE `{$this->database_table}` SET audio_data = ? WHERE id = ?";
            $stmt  = $this->mysqli_conn->prepare($query);
            $stmt->bind_param('ss', $data, $captchaId);
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

        if ($this->mysqli_conn) {
            $query  = "SELECT * FROM `{$this->database_table}` WHERE id = ?";
            $stmt   = $this->mysqli_conn->prepare($query);
            $stmt->bind_param('s', $captchaId);

            $result = $stmt->execute();

            if (!$result) {
                $errno  = $stmt->errno;
                $errmsg = $stmt->error;
                trigger_error("Failed to select code from database.  {$errno}: {$errmsg}", E_USER_WARNING);
            } else {
                $stmt->bind_result($id, $namespace, $code_val, $code_display, $created, $audio_data);
                if ($stmt->fetch() === true) {
                    $code = array(
                        'code'         => $code_val,
                        'code_display' => $code_display,
                        'created'      => $created,
                        'audio_data'   => $audio_data,
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
        if ($this->mysqli_conn) {
            $query = "DELETE FROM `{$this->database_table}` WHERE id = ?";
            $stmt  = $this->mysqli_conn->prepare($query);
            $stmt->bind_param('s', $captchaId);
            $result = $stmt->execute();

            if (!$result) {
                $errno = $stmt->errno;
                $error = $stmt->error;
                trigger_error("Failed to delete code from database.  Error {$errno}: {$error}", E_USER_WARNING);
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

        if ($this->mysqli_conn) {
            $now   = time();
            $limit = (!is_numeric($this->expiry_time) || $this->expiry_time < 1) ? 86400 : $this->expiry_time;

            $query = sprintf("DELETE FROM `%s` WHERE %s - created > %s",
                $this->database_table,
                $now,
                $limit
            );

            $result = $this->mysqli_conn->query($query);
        }

        return $result;
    }
}
