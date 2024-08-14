-- Create the database if it does not exist
CREATE DATABASE IF NOT EXISTS levart_db;

-- Switch to the new database
\c levart_db;

-- Create the table if it does not exist
CREATE TABLE IF NOT EXISTS public.emails (
    id BIGSERIAL PRIMARY KEY,
    email character varying NOT NULL,
    message text,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    status character varying,
    subject character varying NOT NULL,
    details text
);
