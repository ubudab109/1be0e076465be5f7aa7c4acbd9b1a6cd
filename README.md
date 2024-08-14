## Send Email
RESTful API using PHP to send email with queue worker. 

## Requirements
- Docker : https://www.docker.com/
- POSTMAN

## Additional
If you prefer not to use docker. Make sure You have this on Your local:
- PHP 8.2.1^
- Composer version 2.5.8
- Redis
- PostgreeSQL
- PHP Extension: PDO, Postgree, Redis
- POSTMAN

## FIRST SETUP
- Create `.env` file on root project
- Copy all value from `.env.example`
- Please fill only on empty value from `.env.example`

## SECOND SETUP
On this setup, you have to setup Your Google Oauth credentials. From this, you can get the `CLIENT_ID`, `CLIENT_SECRET` and make sure You already setup the callback using this `url/api/auth-callback`. Example: `http://localhost:8000/api/auth-callback`. Otherwise, You can use this existing <a href="https://drive.google.com/file/d/12w30568CEYz2Z3tOTCmUa4dhOHoa61aQ/view?usp=sharing" target="_blank">.env</a> and put it into project.

## Installation using Docker
- After you setup the `.env` and installed Docker you can simply run `docker-compose up --build`
- After docker build is finish. Open others terminal, run this command as well `docker-compose exec php php src/worker.php`. This command is to run the queue process after sending email.

## Installation without Docker
- After you setup the `.env` and have the additional requirements, you have to run `composer install` and `composer dump-autoload`
- Setup Your Postgree Database then change the `POSTGRES_` prefix variable on `.env` with Yours.

## PROJECT STRUCTURE
```plaintext
project/
├── src/
│   ├── config/             # Configuration files
│   ├── controllers/        # Request handlers
│   ├── handlers/           # Event or request handlers
│   ├── middleware/         # Middleware components
│   ├── models/             # Data models
│   ├── repositories/       # Data access layers
│   ├── routes/             # Route definitions
│   ├── services/           # Business logic
│   ├── index.php           # Entry point for the application
│   └── worker.php          # Worker script for background tasks
├── vendor/                 # Composer dependencies
├── .gitignore              # Git ignore rules
├── README.md               # Project documentation
├── composer.json           # Composer dependencies and configuration
├── composer.lock           # Locked dependencies
├── Dockerfile              # Docker configuration
├── docker-compose.yml      # Docker Compose configuration
├── entrypoint.sh           # Shell script for container entry
├── index.php               # Alternative entry point (if used separately)
├── init.sql                # Database initialization script
└── phpcs.xml               # PHP CodeSniffer configuration
```
## HOW TO USE
- First time, You need to open `url/api/login` or `http://localhost:8000/api/login`. You need login using Your google account to get the JWT Token.
- You need to import these 2 files on Your postman to test endpoint.
- [send-email.postman_environment.json](https://github.com/user-attachments/files/16618343/send-email.postman_environment.json)
- [api-docs.postman_collection.json](https://github.com/user-attachments/files/16618344/api-docs.postman_collection.json)
- This files contains environment and collection for endpoint. You need to import this files to test the endpoint.

## API SCHEMA
```plaintext
API Documentation
Email Sending: Implemented using PHP Native.
Status Codes:
200 OK: Data fetched successfully for GET requests.
201 Created: Successfully created or updated for POST requests.
404 Not Found: Data not found.
500 Internal Server Error: An error occurred on the server.
401 Unauthorized: Authentication required.
Required Headers:
Authorization: Bearer {token}
How to Obtain Token:
Log in via browser at /api/login to receive your token.
Save it into enviroment send-email
Response Schema:
{
    "status": "string ('success' or 'error')",
    "message": "string",
    "data": "any (Object, Array, Null)"
}





