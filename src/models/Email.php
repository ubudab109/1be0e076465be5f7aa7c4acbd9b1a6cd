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
    private $attributes = ['id', 'email', 'message', 'created_at', 'updated_at'];
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

    // Get all emails
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Find email by ID
    public function find(int $id): self
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $this->attributes = $stmt->fetch(PDO::FETCH_ASSOC);
        return $this;
    }

    // Save or update email
    public function save(): void
    {
        if (isset($this->attributes['id'])) {
            $this->update();
        } else {
            $this->create();
        }
    }

    private function create(): void
    {
        $columns = implode(", ", array_keys($this->attributes));
        $placeholders = ":" . implode(", :", array_keys($this->attributes));
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $this->setTimestamps();
        $stmt->execute($this->attributes);
    }

    private function update(): void
    {
        $id = $this->attributes['id'];
        unset($this->attributes['id']);
        $this->setTimestamps();
        $setClause = implode(", ", array_map(fn($col) => "$col = :$col", array_keys($this->attributes)));
        $sql = "UPDATE {$this->table} SET $setClause WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $this->attributes['id'] = $id;  // Re-add id for the WHERE clause
        $stmt->execute($this->attributes);
    }

    // Delete email
    public function delete(): void
    {
        if (!isset($this->attributes['id'])) {
            throw new Exception('Cannot delete email without an ID.');
        }
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $this->attributes['id']]);
    }

    // Set timestamps
    private function setTimestamps(): void
    {
        $now = (new DateTime())->format('Y-m-d H:i:s');
        if (!isset($this->attributes['created_at'])) {
            $this->attributes['created_at'] = $now;
        }
        $this->attributes['updated_at'] = $now;
    }
}
