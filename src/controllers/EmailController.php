<?php

namespace Src\Controllers;

use Laminas\Diactoros\Response\JsonResponse;
use Src\Repositories\EmailRepository;

class EmailController
{
    private $emailRepository;

    public function __construct(EmailRepository $emailRepository)
    {
        $this->emailRepository = $emailRepository;
    }

    public function getEmails(): JsonResponse
    {
        $emails = $this->emailRepository->getAllEmails();
        return new JsonResponse([
            'status' => 'success',
            'message' => 'Email data fetched successfully',
            'data' => $emails
        ], 200);
    }
}