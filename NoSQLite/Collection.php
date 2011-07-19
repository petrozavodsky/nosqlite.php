<?php

/**
 * NoSQLite key-value collection 
 */
class NoSQLite_Collection implements Iterator
{
    const KEY_COLUMN_NAME = 'key';
    const VALUE_COLUMN_NAME = 'value';
    
    /**
     * PDO instance
     * @var PDO
     */
    protected $_db = null;
    
    /**
     * Collection name
     * @var string 
     */
    protected $_name = null;
    
    /**
     * Documents stored in collection
     * @var array 
     */
    protected $_data = array();

    /**
     * Create collection stored in database
     * 
     * @param PDO $db PDO database instance
     * @param string $name collection name 
     */
    public function __construct($db, $name)
    {
        $this->_db = $db;
        $this->_name = $name;
        $this->_createTable();
        $this->_loadDocuments();
    }

    public function rewind() {
        return reset($this->_data);
    }

    public function current() {
        return current($this->_data);
    }

    public function key() {
        return key($this->_data);
    }

    public function next() {
        return next($this->_data);
    }

    public function valid() {
        return key($this->_data) !== null;
    }
    
    /**
     * Create storage table in database
     */
    protected function _createTable()
    {
        $stmt = 'CREATE TABLE IF NOT EXISTS "' . $this->_name;
        $stmt.= '" ("' . self::KEY_COLUMN_NAME . '" TEXT PRIMARY KEY, "';
        $stmt.= self::VALUE_COLUMN_NAME . '" TEXT);';
        $this->_db->exec($stmt);
    }
    
    /**
     * Load data from collection table
     */
    protected function _loadDocuments()
    {
        $stmt = $this->_db->prepare('SELECT * FROM ' . $this->_name);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {
            $this->_data[$row[0]] = $row[1];
        }
    }
    
    /**
     * Get document for specified key
     * 
     * @param string $key
     * @return string 
     * @throws InvalidArgumentException
     */
    public function get($key)
    {
        if (!is_string($key))
        {
            throw new InvalidArgumentException('Expected string as key');
        }
        
        if (isset($key, $this->_data))
        {
            return $this->_data[$key];
        }
        
        return null;
    }
    
    /**
     * Get all documents as array with key => document structure
     * 
     * @return array 
     */
    public function getAll()
    {
        return $this->_data;
    }
    
    /**
     * Set value on specified key
     * 
     * @param string $key
     * @param string $value
     * @throws InvalidArgumentException
     */
    public function set($key, $value)
    {
        if (!is_string($key))
        {
            throw new InvalidArgumentException('Expected string as key');
        }
        
        if (!is_string($value))
        {
            throw new InvalidArgumentException('Expected string as value');
        }
        
        if (isset($this->_data[$key]))
        {
            $queryString ='UPDATE ' . $this->_name . ' SET ' . self::VALUE_COLUMN_NAME . ' = :value WHERE ';
            $queryString.= self::KEY_COLUMN_NAME . ' = :key;';
        }
        else
        {
            $queryString = 'INSERT INTO ' . $this->_name . ' VALUES (:key, :value);';
        }
        
        $stmt = $this->_db->prepare($queryString);
        $stmt->bindParam(':key', $key, PDO::PARAM_STR);
        $stmt->bindParam(':value', $value, PDO::PARAM_STR);
        $stmt->execute();
        $this->_data[$key] = $value;
    }
    
    /**
     * Delete document from collection
     * 
     * @param string $key 
     */
    public function delete($key)
    {
        $stmt = $this->_db->prepare('DELETE FROM ' . $this->_name . ' WHERE ' . self::KEY_COLUMN_NAME . ' = :key;');
        $stmt->bindParam(':key', $key, PDO::PARAM_STR);
        $stmt->execute();
        
        unset($this->_data[$key]);
    }
}