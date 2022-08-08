CREATE DATABASE IF NOT EXISTS api_rest_laravel;
USE api_rest_laravel;

CREATE TABLE users(
    id                  INT(255) AUTO_INCREMENT NOT NULL,
    name                VARCHAR(255) NOT NULL,
    surname             VARCHAR(255) NOT NULL,
    role                VARCHAR(255) NOT NULL,
    email               VARCHAR(20) NOT NULL,
    password            VARCHAR(255) NOT NULL,    
    description         TEXT NULL,
    image               VARCHAR(255) NULL,
    crate_at            DATETIME DEFAULT NULL,
    update_at           DATETIME DEFAULT NULL,
    remember_token      VARCHAR(255) NULL,
    CONSTRAINT pk_users PRIMARY KEY (id)
)ENGINE=InnoDb;

CREATE TABLE categories(
    id                  INT(255) AUTO_INCREMENT NOT NULL,
    name                VARCHAR(100) NOT NULL,
    crate_at            DATETIME DEFAULT NULL,
    update_at           DATETIME DEFAULT NULL,
    CONSTRAINT pk_categories PRIMARY KEY (id)
)ENGINE=InnoDb;

CREATE TABLE posts(
    id                  INT(255) AUTO_INCREMENT NOT NULL,
    user_id             INT(255) NULL,
    category_id         INT(255) NULL,
    title               VARCHAR(255) NOT NULL,
    content             LONGTEXT NOT NULL,
    image               VARCHAR(255) NOT NULL,
    crate_at            DATETIME DEFAULT NULL,
    update_at           DATETIME DEFAULT NULL,
    CONSTRAINT pk_posts PRIMARY KEY (id),
    CONSTRAINT fk_posts_users FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_posts_categories FOREIGN KEY (category_id) REFERENCES categories(id)
)ENGINE=InnoDb;