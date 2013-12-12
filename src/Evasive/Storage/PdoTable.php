<?php
namespace Evasive\Storage;

class PdoTable implements StorageInterface
{

    const TABLE_NAME = 'evasive';

    /**
     * @var \PDO PDO instance.
     */
    protected $pdo;
    
    /**
     * @var array Database options.
     */
    private $dbOptions;

    public function __construct(array $options = [])
    {
        $this->pdo = $options['dbConnection'];
        
        if (\PDO::ERRMODE_EXCEPTION !== $this->pdo->getAttribute(\PDO::ATTR_ERRMODE)) {
            throw new \RuntimeException(sprintf('"%s" requires PDO error mode attribute be set to throw Exceptions (i.e. $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION))', __CLASS__));
        }
        
        $this->dbOptions = array_merge(array(
            'db_table'    => 'evasive',
            'db_id_col'   => 'id',
            'db_data_col' => 'data',
            'db_time_col' => 'timestamp',
        ), $options);
        
        $dbTable = $this->dbOptions['db_table'];
        $dbIdCol = $this->dbOptions['db_id_col'];
        $dbTimeCol = $this->dbOptions['db_time_col'];
        
        // delete old requests
        $sql = "DELETE FROM {$dbTable} WHERE {$dbTimeCol} < :time";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':time', time() - (3600 * 24), \PDO::PARAM_INT);
            $stmt->execute();
        } catch (\PDOException $e) {
            // exception means that the schema version table doesn't exist, so create it
            $this->pdo->query("CREATE TABLE IF NOT EXISTS `evasive` (`id` varchar(255) NOT NULL, `timestamp` int(11) NOT NULL, `data` mediumtext NOT NULL, PRIMARY KEY (`id`))");
        }
        
    }

    public function get()
    {
        $id = session_id();
        
        try {
            $sql = "SELECT data FROM " . self::TABLE_NAME . " WHERE id = :id";
        
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, \PDO::PARAM_STR);
        
            $stmt->execute();
            // it is recommended to use fetchAll so that PDO can close the DB cursor
            // we anyway expect either no rows, or one row with one column.
            $sessionRows = $stmt->fetchAll(\PDO::FETCH_NUM);
        
            if (count($sessionRows) == 1) {
                return unserialize($sessionRows[0][0]);
            }
        
            return null;
        } catch (\PDOException $e) {
            throw new \RuntimeException(sprintf('PDOException was thrown when trying to read the evasive data: %s', $e->getMessage()), 0, $e);
        }
        
      
        return null;
    }

    public function store($data)
    {
        $this->write(session_id(), $data);
    }

    public function update($data)
    {
        $data = array_merge($this->get(), $data);
        
        $this->write(session_id(), $data);
    }
    
    
    /**
     * {@inheritDoc}
     */
    public function write($id, $data)
    {
        // get table/column
        $dbTable   = $this->dbOptions['db_table'];
        $dbDataCol = $this->dbOptions['db_data_col'];
        $dbIdCol   = $this->dbOptions['db_id_col'];
        $dbTimeCol = $this->dbOptions['db_time_col'];
    
        //session data can contain non binary safe characters so we need to encode it
        $encoded = serialize($data);
    
        try {
            $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    
            if ('mysql' === $driver) {
                // MySQL would report $stmt->rowCount() = 0 on UPDATE when the data is left unchanged
                // it could result in calling createNewSession() whereas the session already exists in
                // the DB which would fail as the id is unique
                $stmt = $this->pdo->prepare(
                    "INSERT INTO $dbTable ($dbIdCol, $dbDataCol, $dbTimeCol) VALUES (:id, :data, :time) " .
                    "ON DUPLICATE KEY UPDATE $dbDataCol = VALUES($dbDataCol), $dbTimeCol = VALUES($dbTimeCol)"
                );
                $stmt->bindParam(':id', $id, \PDO::PARAM_STR);
                $stmt->bindParam(':data', $encoded, \PDO::PARAM_STR);
                $stmt->bindValue(':time', time(), \PDO::PARAM_INT);
                $stmt->execute();
            } elseif ('oci' === $driver) {
                $stmt = $this->pdo->prepare("MERGE INTO $dbTable USING DUAL ON($dbIdCol = :id) ".
                    "WHEN NOT MATCHED THEN INSERT ($dbIdCol, $dbDataCol, $dbTimeCol) VALUES (:id, :data, sysdate) " .
                    "WHEN MATCHED THEN UPDATE SET $dbDataCol = :data WHERE $dbIdCol = :id");
    
                $stmt->bindParam(':id', $id, \PDO::PARAM_STR);
                $stmt->bindParam(':data', $encoded, \PDO::PARAM_STR);
                $stmt->execute();
            } else {
                $stmt = $this->pdo->prepare("UPDATE $dbTable SET $dbDataCol = :data, $dbTimeCol = :time WHERE $dbIdCol = :id");
                $stmt->bindParam(':id', $id, \PDO::PARAM_STR);
                $stmt->bindParam(':data', $encoded, \PDO::PARAM_STR);
                $stmt->bindValue(':time', time(), \PDO::PARAM_INT);
                $stmt->execute();
    
                if (!$stmt->rowCount()) {
                    // No session exists in the database to update. This happens when we have called
                    // session_regenerate_id()
                    $this->createNewSession($id, $data);
                }
            }
        } catch (\PDOException $e) {
            throw new \RuntimeException(sprintf('PDOException was thrown when trying to write the evasive data: %s', $e->getMessage()), 0, $e);
        }
    
        return true;
    }
    
    /**
     * Creates a new session with the given $id and $data
     *
     * @param string $id
     * @param string $data
     *
     * @return boolean True.
     */
    private function createNewSession($id, $data = '')
    {
        // get table/column
        $dbTable   = self::TABLE_NAME;
    
        $sql = "INSERT INTO $dbTable (id, data, timestamp) VALUES (:id, :data, :time)";
    
        //session data can contain non binary safe characters so we need to encode it
        $encoded = base64_encode($data);
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', session_id(), \PDO::PARAM_STR);
        $stmt->bindParam(':data', serialize($data), \PDO::PARAM_STR);
        $stmt->bindValue(':time', time(), \PDO::PARAM_INT);
        $stmt->execute();
    
        return true;
    }
    
}