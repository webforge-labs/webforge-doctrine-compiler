CREATE USER 'doctrine'@'localhost' IDENTIFIED BY '0ry8xd1fz9ubr5';

GRANT USAGE ON * . * TO 'doctrine'@'localhost' IDENTIFIED BY '0ry8xd1fz9ubr5' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;

CREATE DATABASE IF NOT EXISTS `doctrine` ;
GRANT ALL PRIVILEGES ON `doctrine` . * TO 'doctrine'@'localhost';

CREATE DATABASE IF NOT EXISTS `doctrine_tests`;
GRANT ALL PRIVILEGES ON `doctrine_tests` . * TO 'doctrine'@'localhost';
