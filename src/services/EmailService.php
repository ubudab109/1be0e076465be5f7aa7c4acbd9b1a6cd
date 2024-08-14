<?php

namespace Src\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Predis\Client as PredisClient;
use Src\Repositories\EmailRepository;


class EmailService
{
    private $redis, $repository;

    public function __construct(EmailRepository $repository)
    {
        $this->redis = new PredisClient([
            'schema' => 'tcp',
            'host' => $_ENV['REDIS_HOST'],
            'port' => $_ENV['REDIS_PORT']
        ]);

        $this->repository = $repository;
    }

    /**
     * The function getRedis() returns the Redis client if initialized, otherwise throws an exception.
     * 
     * @return The `getRedis()` function is returning the Redis client instance (`->redis`) if it
     * is initialized. If the Redis client is not initialized, it will throw an Exception with the
     * message 'Redis client is not initialized.'
     */
    public function getRedis()
    {
        if (!$this->redis) {
            throw new \Exception('Redis client is not initialized.');
        }
        return $this->redis;
    }

    /**
     * The function `queuePendingEmails` retrieves pending emails from a repository, pushes them to a
     * Redis queue, and updates their status to 'processing'.
     */
    public function queuePendingEmails(): void
    {
        $pendingEmails = $this->repository->findManyBy('status', ['pending', 'procecssing']);
        $redis = $this->getRedis();

        foreach ($pendingEmails as $email) {
            $job = [
                'email' => $email['email'],
                'message' => $email['message'],
                'mail_id' => $email['id'],
                'subject' => $email['subject'],
            ];

            $redis->lpush('email_queue', json_encode($job));

            $this->repository->saveEmail([
                'id' => $email['id'],
                'status' => 'processing',
            ]);
        }
    }

    /**
     * The function `sendEmail` processes email jobs from a queue using PHPMailer and Redis, sending
     * emails and updating their status accordingly.
     */
    public function sendEmail(): void
    {
        $this->getRedis();
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['MAIL_USERNAME'];
        $mail->Password = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['MAIL_PORT'];

        while (true) {
            $job = $this->redis->rpop('email_queue');

            if ($job) {
                $jobData = json_decode($job, true);
                $emails = $jobData['email'];
                $message = $jobData['message'];
                $subject = $jobData['subject'];
                $mailId = $jobData['mail_id'];

                try {
                    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
                    $mail->addAddress($emails);
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body = $message;
                    $mail->AltBody = strip_tags($message);
                    $mail->send();
                    echo "Message sent to " . $emails . "\n";

                    $this->repository->saveEmail([
                        'id' => $mailId,
                        'status' => 'sent',
                    ]);
                } catch (Exception $e) {
                    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
                    $this->repository->saveEmail([
                        'id' => $mailId,
                        'status' => 'failed',
                        'details' => $e->getMessage(),
                    ]);
                }
            } else {
                // Sleep FOR 5 SECONDS BEFORE CHECKING THE QUEUE AGAIN
                sleep(5);
            }
        }
    }
}
