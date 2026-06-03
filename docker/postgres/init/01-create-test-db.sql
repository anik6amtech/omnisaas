-- Runs once on first initialization of the Postgres data volume.
-- Creates the dedicated test database used by the Pest suite (phpunit.xml
-- forces DB_DATABASE=omnireply_testing). The pgvector extension itself is
-- enabled per-database by the enable_pgvector_extension migration.
CREATE DATABASE omnireply_testing;
