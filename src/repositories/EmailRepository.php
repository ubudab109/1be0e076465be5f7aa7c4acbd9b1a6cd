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

    public function saveEmail(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->emailModel->setAttribute($key, $value);
        }
        $this->emailModel->save();
    }

    public function deleteEmail(int $id): void
    {
        $this->emailModel->setAttribute('id', $id);
        $this->emailModel->delete();
    }
}
