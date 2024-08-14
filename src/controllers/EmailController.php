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

    /**
     * The getEmails function retrieves all emails from the email repository and returns a JSON
     * response with success status, message, and the email data.
     * 
     * @return JsonResponse The getEmails function returns a JsonResponse object with a status of
     * 'success', a message indicating that the email data was fetched successfully, and the actual
     * email data retrieved from the emailRepository. The HTTP status code returned is 200 (OK).
     */
    public function getEmails(): JsonResponse
    {
        $emails = $this->emailRepository->getAllEmails();
        return new JsonResponse([
            'status' => 'success',
            'message' => 'Email data fetched successfully',
            'data' => $emails
        ], 200);
    }

    /**
     * The function `sendEmail` processes and queues email jobs for sending.
     * 
     * @param ServerRequestInterface $request The `sendEmail` function takes a `ServerRequestInterface`
     * object named `` as a parameter. This object represents an HTTP request received by the
     * server.
     * 
     * @return JsonResponse A `JsonResponse` object is being returned. If the validation for the email,
     * message, and subject fields passes, a success response with status code 200 and a message 'Email
     * job queued successfully' is returned. If the validation fails, an error response with status
     * code 400 and a message 'Validation failed: "email", "message" and "subject" fields are required'
     * is returned
     */
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

    public function getEmail(ServerRequestInterface $request, array $vars): JsonResponse
    {
        $id = $vars['id'] ?? null;

        if ($id === null) {
            return new JsonResponse(['status' => 'error', 'message' => 'Email ID is required'], 400);
        }

        // Fetch the email from the repository
        $email = $this->emailRepository->findOneById($id);
        if (!$email->getAttributes()) {
            return new JsonResponse(['status' => 'error', 'message' => 'Email not found'], 404);
        }

        return new JsonResponse(['status' => 'success', 'message' => 'Email retrieved successfully', 'data' => $email->getAttributes()]);
    }

    public function deleteEmail(ServerRequestInterface $request, array $vars): JsonResponse
    {
        $id = $vars['id'] ?? null;

        if ($id === null) {
            return new JsonResponse(['status' => 'error', 'message' => 'Email ID is required'], 400);
        }

        $email = $this->emailRepository->findOneById($id);
        if (!$email->getAttributes()) {
            return new JsonResponse(['status' => 'error', 'message' => 'Email not found'], 404);
        }

        $this->emailRepository->deleteEmail($id);
        return new JsonResponse(['status' => 'success', 'message' => 'Email deleted successfully', 'data' => null]);
    }
}