<?php

namespace Src\Models;

use PDO;
use DateTime;
use Exception;

/**
 * Src/Models/Email
 * @property integer $id
 * @property string $email
 * @property string $message
 * @property DateTime|null $created_at
 * @property DateTime $updated_at
 */
class Email
{
    private $pdo;
    private $attributes = ['id', 'email', 'message', 'status', 'subject', 'details', 'created_at', 'updated_at'];
    private $table = 'emails';

    // Constructor
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * The function `setAttribute` assigns a value to a specified key in an array of attributes.
     * 
     * @param string $key The "key" parameter is a string that represents the key of the attribute you
     * want to set in the attributes array.
     * @param mixed value The `value` parameter in the `setAttribute` function is a variable that can hold
     * any type of value. It is assigned to the specified key in the attributes array.
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * The getAttribute function retrieves a value from an array using a specified key in PHP.
     * 
     * @param string $key The `key` parameter in the `getAttribute` function is a string type parameter.
     * It is used to specify the key of the attribute that you want to retrieve from the attributes
     * array.
     * 
     * @return mixed `getAttribute` function is returning the value of the attribute corresponding to the
     * given key from the `` array. If the key does not exist in the array, it will return
     * `null`.
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * The getAttributes function in PHP returns an array of attributes.
     * 
     * @return array An array of attributes is being returned.
     */
    public function getAttributes(): array|bool
    {
        return $this->attributes;
    }

    /**
     * The function retrieves all records from a database table and returns them as an associative
     * array.
     * 
     * @return array An array of associative arrays containing all the rows from the specified table in
     * the database. Each associative array represents a row with column names as keys and
     * corresponding values.
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * The function `findManyBy` retrieves multiple rows from a database table based on a given
     * attribute and value.
     * 
     * @param mixed %attribute The `attribute` parameter in the `findManyBy` function represents the
     * column name in the database table that you want to use for filtering the results. It is the
     * field based on which you want to perform the search operation.
     * @param mixed %value The `value` parameter in the `findManyBy` function represents the value or
     * values you want to search for in the database table based on the specified attribute. If `value`
     * is an array, the function will search for multiple values using the `IN` clause in the SQL
     * query. If
     * 
     * @return array The `findManyBy` function returns an array of rows fetched from the database based
     * on the provided attribute and value. The returned array contains associative arrays representing
     * the rows with column names as keys.
     */
    public function findManyBy(mixed $attribute, mixed $value): array
    {
        if (is_array($value)) {
            $placeholders = implode(", ", array_fill(0, count($value), "?"));
            $sql = "SELECT * FROM {$this->table} WHERE {$attribute} IN ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($value);
        } else {
            $sql = "SELECT * FROM {$this->table} WHERE {$attribute} = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$value]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * The function `find` retrieves a record from a database table based on the provided ID and stores
     * the attributes in the object.
     * 
     * @param int $id The `find` method in the code snippet you provided is a function that retrieves a
     * record from the database table based on the `id` provided as a parameter. The `id` parameter is
     * an integer that represents the unique identifier of the record you want to retrieve.
     * 
     * @return self The `find` method is returning the current instance of the class itself (``)
     * after fetching and setting the attributes of the object based on the database query result.
     */
    public function find(int $id): self
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $this->attributes = $stmt->fetch(PDO::FETCH_ASSOC);
        return $this;
    }

   /**
    * The `save` function checks if an object has an ID and either updates or creates a record
    * accordingly, returning the attributes.
    * 
    * @return array The `save()` method is returning an array of attributes after either updating or
    * creating a record in the database based on the presence of the 'id' attribute in the object.
    */
    public function save(): array
    {
        if (isset($this->attributes['id'])) {
            $this->update();
        } else {
            $this->create();
        }
        return $this->attributes;
    }

    /**
     * The create function prepares and executes an SQL INSERT statement using filtered attributes to
     * insert data into a database table.
     */
    private function create(): void
    {
        $this->setTimestamps();

        $filteredAttributes = array_filter($this->attributes, fn($key) => in_array($key, ['email', 'message', 'status', 'subject', 'details', 'created_at', 'updated_at']), ARRAY_FILTER_USE_KEY);

        $columns = array_keys($filteredAttributes);

        $placeholders = array_map(fn($col) => ":$col", $columns);

        $columnsList = implode(", ", $columns);

        $placeholdersList = implode(", ", $placeholders);

        $sql = "INSERT INTO {$this->table} ($columnsList) VALUES ($placeholdersList)";
        
        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($filteredAttributes);

        $this->attributes['id'] = $this->pdo->lastInsertId();
    }

    /**
     * The function `update` updates specific attributes of a record in a database table based on the
     * provided ID.
     */
    private function update(): void
    {
        $id = $this->attributes['id'] ?? null;
        if (!$id) {
            throw new Exception('ID must be set for an update operation.');
        }
        $attributesToUpdate = array_filter($this->attributes, fn($key) => in_array($key, ['email', 'message', 'status', 'subject', 'details', 'created_at', 'updated_at']), ARRAY_FILTER_USE_KEY);

        $attributesToUpdate['id'] = $id;

        $setClause = implode(", ", array_map(fn($col) => "$col = :$col", array_keys($attributesToUpdate)));

        $sql = "UPDATE {$this->table} SET $setClause WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($attributesToUpdate);
    }

    /**
     * The delete function deletes a record from a database table based on the ID provided.
     */
    public function delete(): void
    {
        if (!isset($this->attributes['id'])) {
            throw new Exception('Cannot delete email without an ID.');
        }
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $this->attributes['id']]);
    }

    /**
     * The function `setTimestamps` sets the `created_at` and `updated_at` attributes to the current
     * date and time if they are not already set.
     */
    private function setTimestamps(): void
    {
        $now = (new DateTime())->format('Y-m-d H:i:s');
        if (!isset($this->attributes['created_at'])) {
            $this->attributes['created_at'] = $now;
        }
        $this->attributes['updated_at'] = $now;
    }
}