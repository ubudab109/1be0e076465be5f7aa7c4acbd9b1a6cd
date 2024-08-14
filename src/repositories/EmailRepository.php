<?php

namespace Src\Repositories;

use Src\Models\Email;
use PDO;

class EmailRepository
{
    private $emailModel;

    public function __construct(PDO $pdo)
    {
        $this->emailModel = new Email($pdo);
    }

    public function getAllEmails(): array
    {
        return $this->emailModel->all();
    }

    public function getEmailById(int $id): Email
    {
        return $this->emailModel->find($id);
    }

    public function saveEmail(array $attributes): array
    {
        foreach ($attributes as $key => $value) {
            $this->emailModel->setAttribute($key, $value);
        }

        $this->emailModel->save();
        return [
            'id' => $this->emailModel->getAttribute('id'),
            'email' => $this->emailModel->getAttribute('email'),
            'message' => $this->emailModel->getAttribute('message'),
            'status' => $this->emailModel->getAttribute('status'),
            'subject' => $this->emailModel->getAttribute('subject'),
        ];
    }

    public function deleteEmail(int $id): void
    {
        $this->emailModel->setAttribute('id', $id);
        $this->emailModel->delete();
    }

    public function findManyBy(mixed $column, mixed $value): array
    {
        return $this->emailModel->findManyBy($column, $value);
    }
}
