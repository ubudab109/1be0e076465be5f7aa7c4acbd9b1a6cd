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

    // Set attributes
    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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

    public function find(int $id): self
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $this->attributes = $stmt->fetch(PDO::FETCH_ASSOC);
        return $this;
    }

    public function save(): array
    {
        if (isset($this->attributes['id'])) {
            $this->update();
        } else {
            $this->create();
        }
        return $this->attributes;
    }

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

    public function delete(): void
    {
        if (!isset($this->attributes['id'])) {
            throw new Exception('Cannot delete email without an ID.');
        }
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $this->attributes['id']]);
    }

    private function setTimestamps(): void
    {
        $now = (new DateTime())->format('Y-m-d H:i:s');
        if (!isset($this->attributes['created_at'])) {
            $this->attributes['created_at'] = $now;
        }
        $this->attributes['updated_at'] = $now;
    }
}