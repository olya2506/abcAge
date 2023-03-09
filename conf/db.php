<?php

const hostname = 'localhost';
const username = 'root';
const password = '';
const database = 'abcAge';


function create_db(): void
{
    $connection = new mysqli(hostname, username, password, database);

    if ($connection->connect_error)
        exit('Error create_db(): ' . $connection->connect_error);

    $connection->query('CREATE DATABASE `abcAge`');

    $connection->close();
}


function create_tables(): void
{
    $connection = new mysqli(hostname, username, password, database);

    if ($connection->connect_error)
        exit('Error create_tables(): ' . $connection->connect_error);

    $connection->multi_query('

    CREATE TABLE IF NOT EXISTS `warehouse` (
                                    `product` VARCHAR(50) NOT NULL UNIQUE,
                                    `balance` INT(11) NOT NULL,
                                    PRIMARY KEY (`product`),
                                    CHECK (`balance` >= 0)
                                    );

    CREATE TABLE IF NOT EXISTS `received` (
                                    `id` VARCHAR(20) NOT NULL UNIQUE,
                                    `product` VARCHAR(50) NOT NULL,
                                    `received_quantity` INT(11) NOT NULL,
                                    `cost` FLOAT(13) NOT NULL,
                                    `receive_date` DATE NOT NULL,
                                    FOREIGN KEY (`product`) REFERENCES `warehouse`(`product`),
                                    INDEX (`receive_date`)
                                    );

    CREATE TABLE IF NOT EXISTS `dispatch` (
                                    `id` INT(11) NOT NULL UNIQUE AUTO_INCREMENT,
                                    `product` VARCHAR(50) NOT NULL,
                                    `preorder` INT(11) NOT NULL,
                                    `dispatch_quantity` INT(11) NOT NULL,
                                    `unit_price` FLOAT(13) NOT NULL,
                                    `dispatch_date` DATE NOT NULL,
                                    FOREIGN KEY (`product`) REFERENCES `warehouse`(`product`),
                                    INDEX (`dispatch_date`)
                                    );

    INSERT INTO `warehouse` (`product`, `balance`)
                VALUES ("Левый носок", 0), 
                       ("Колбаса", 0), 
                       ("Пармезан", 0)
                       ;

    CREATE TRIGGER `after_insert_received`
                AFTER INSERT ON `received`
                FOR EACH ROW
                UPDATE `warehouse` SET `balance` = (balance + NEW.received_quantity)
                WHERE `product` = NEW.product
                ;

    CREATE TRIGGER `after_insert_dispatch`
                AFTER INSERT ON `dispatch`
                FOR EACH ROW
                UPDATE `warehouse` SET `balance` = IF (balance >= NEW.dispatch_quantity, 
                                                        balance - NEW.dispatch_quantity, 
                                                        balance)
                WHERE `product` = NEW.product
                ;
');

    $connection->close();
}


function execute($query)
{
    $connection = new mysqli(hostname, username, password, database);

    if ($connection->connect_error)
        exit('Error: ' . $connection->connect_error);

    $result = $connection->query($query);

    $connection->close();

    if (!empty($result->num_rows) && $result->num_rows > 0)
        return $result->fetch_assoc();

    return null;
}


function truncate (): void
{
    execute('TRUNCATE `received`');
    execute('TRUNCATE `dispatch`');
    execute('UPDATE `warehouse` SET `balance` = 0');
}
