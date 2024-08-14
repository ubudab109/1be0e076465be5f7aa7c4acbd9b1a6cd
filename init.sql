-- Create the database if it does not exist
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_database WHERE datname = 'emails_db') THEN
        EXECUTE 'CREATE DATABASE emails_db';
    END IF;
END
$$;

-- Connect to the newly created database
\connect emails_db;

-- Create the table if it does not exist
CREATE TABLE IF NOT EXISTS public.emails (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR NOT NULL,
    message TEXT,
    created_at TIMESTAMPTZ DEFAULT now(),
    updated_at TIMESTAMPTZ DEFAULT now(),
    status VARCHAR,
    subject VARCHAR NOT NULL,
    details TEXT
);
