<?php

namespace Src\Controllers;

use DateTime;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Src\Repositories\EmailRepository;
use Src\Services\EmailService;

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

    public function sendEmail(ServerRequestInterface $request): JsonResponse
    {
        $body = json_decode($request->getBody()->getContents(), true);
        if (empty($body['email']) || empty($body['message']) || empty($body['subject'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Validation failed: "email", "message" and "subject" fields are required'
            ], 400);
        }

        $email = $this->emailRepository->saveEmail([
            'email' => $body['email'],
            'message' => $body['message'],
            'status' => 'pending',
            'subject' => $body['subject'],
        ]);
        // Create a job payload
        $job = [
            'email' => $email['email'],
            'message' => $email['message'],
            'mail_id' => $email['id'],
            'subject' => $email['subject'],
        ];

        $mailService = new EmailService($this->emailRepository);
        $redis = $mailService->getRedis(); // Access the Redis client from EmailService

        // Add job to Redis list
        $redis->lpush('email_queue', json_encode($job));
        return new JsonResponse([
            'status' => 'success',
            'message' => 'Email job queued successfully'
        ], 200);
    }
}