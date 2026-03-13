<?php

class DB
{
    private $conn;

    public function __construct()
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $user = getenv('DB_USERNAME') ?: 'root';
        $pass = getenv('DB_PASSWORD') ?: '';
        $db   = getenv('DB_DATABASE') ?: 'torymail';

        $this->conn = new mysqli($host, $user, $pass, $db);

        if ($this->conn->connect_error) {
            die('Database connection failed: ' . $this->conn->connect_error);
        }

        $this->conn->set_charset('utf8mb4');
    }

    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * Execute a query with parameters and return the statement
     */
    private function execute_safe($sql, $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error: " . $this->conn->error . " | Query: " . $sql);
            return false;
        }

        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_float($param)) $types .= 'd';
                else $types .= 's';
            }
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $stmt;
    }

    /**
     * Get single row
     */
    public function get_row_safe($sql, $params = [])
    {
        $stmt = $this->execute_safe($sql, $params);
        if (!$stmt) return null;

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row;
    }

    /**
     * Get multiple rows
     */
    public function get_list_safe($sql, $params = [])
    {
        $stmt = $this->execute_safe($sql, $params);
        if (!$stmt) return [];

        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    /**
     * Count rows
     */
    public function num_rows_safe($sql, $params = [])
    {
        $stmt = $this->execute_safe($sql, $params);
        if (!$stmt) return 0;

        $result = $stmt->get_result();
        $count = $result->num_rows;
        $stmt->close();
        return $count;
    }

    /**
     * Get single value
     */
    public function get_value_safe($sql, $params = [])
    {
        $stmt = $this->execute_safe($sql, $params);
        if (!$stmt) return null;

        $result = $stmt->get_result();
        $row = $result->fetch_row();
        $stmt->close();
        return $row ? $row[0] : null;
    }

    /**
     * Insert record
     */
    public function insert_safe($table, $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";

        $stmt = $this->execute_safe($sql, array_values($data));
        if (!$stmt) return false;

        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    /**
     * Update record
     */
    public function update_safe($table, $data, $where_sql, $where_params = [])
    {
        $setParts = [];
        foreach (array_keys($data) as $col) {
            $setParts[] = "`$col` = ?";
        }
        $setStr = implode(', ', $setParts);
        $sql = "UPDATE `$table` SET $setStr WHERE $where_sql";

        $params = array_merge(array_values($data), $where_params);
        $stmt = $this->execute_safe($sql, $params);
        if (!$stmt) return false;

        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }

    /**
     * Delete record
     */
    public function remove_safe($table, $where_sql, $where_params = [])
    {
        $sql = "DELETE FROM `$table` WHERE $where_sql";
        $stmt = $this->execute_safe($sql, $where_params);
        if (!$stmt) return false;

        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }

    /**
     * Increment column
     */
    public function increment_safe($table, $column, $amount, $where_sql, $where_params = [])
    {
        $sql = "UPDATE `$table` SET `$column` = `$column` + ? WHERE $where_sql";
        $params = array_merge([$amount], $where_params);
        $stmt = $this->execute_safe($sql, $params);
        if (!$stmt) return false;
        $stmt->close();
        return true;
    }

    /**
     * Decrement column
     */
    public function decrement_safe($table, $column, $amount, $where_sql, $where_params = [])
    {
        $sql = "UPDATE `$table` SET `$column` = `$column` - ? WHERE $where_sql";
        $params = array_merge([$amount], $where_params);
        $stmt = $this->execute_safe($sql, $params);
        if (!$stmt) return false;
        $stmt->close();
        return true;
    }

    /**
     * Transaction methods
     */
    public function beginTransaction()
    {
        $this->conn->begin_transaction();
    }

    public function commit()
    {
        $this->conn->commit();
    }

    public function rollBack()
    {
        $this->conn->rollback();
    }

    /**
     * Raw query (use with caution)
     */
    public function raw_query($sql)
    {
        return $this->conn->query($sql);
    }

    /**
     * Escape string
     */
    public function escape($str)
    {
        return $this->conn->real_escape_string($str);
    }

    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
