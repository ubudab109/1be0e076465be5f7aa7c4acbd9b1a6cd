<?php

require __DIR__. '/../vendor/autoload.php'; // Ensure Composer autoload is included
require __DIR__ . '/./config/database.php';

use Src\Repositories\EmailRepository;
use Src\Services\EmailService;

$mailService = new EmailService(new EmailRepository(getPgConnection()));
$mailService->queuePendingEmails();
$mailService->sendEmail();
