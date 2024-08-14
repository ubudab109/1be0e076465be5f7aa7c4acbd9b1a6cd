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

    public function getRedis()
    {
        if (!$this->redis) {
            throw new \Exception('Redis client is not initialized.');
        }
        return $this->redis;
    }

    public function queuePendingEmails()
    {
        $pendingEmails = $this->repository->findManyBy('status', ['pending', 'procecssing']);
        $redis = $this->getRedis(); // Mengakses Redis client

        foreach ($pendingEmails as $email) {
            // Buat payload job
            $job = [
                'email' => $email['email'],
                'message' => $email['message'],
                'mail_id' => $email['id'],
                'subject' => $email['subject'],
            ];

            // Tambahkan job ke Redis list
            $redis->lpush('email_queue', json_encode($job));

            // Update status menjadi 'processing'
            $this->repository->saveEmail([
                'id' => $email['id'],
                'status' => 'processing',
            ]);
        }
    }

    public function sendEmail()
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
