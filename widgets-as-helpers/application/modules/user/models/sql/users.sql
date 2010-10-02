CREATE TABLE IF NOT EXISTS users (
    username VARCHAR(32) PRIMARY KEY NOT NULL,
    password CHAR(64) NOT NULL,
    first_name VARCHAR(64),
    last_name VARCHAR(64),
    email VARCHAR(256),
    url VARCHAR(256)
);
