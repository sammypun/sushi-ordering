-- Remove the old users if they exist
DROP USER IF EXISTS 'sushi_admin'@'localhost';
DROP USER IF EXISTS 'sushi_customer'@'localhost';

-- Create new sushi users
CREATE USER 'sushi_admin'@'localhost' IDENTIFIED BY 'sushiroisthebest';
CREATE USER 'sushi_customer'@'localhost' IDENTIFIED BY 'customerpass';

-- Grant admin full access to sushi database
GRANT ALL PRIVILEGES ON sushi.* TO 'sushi_admin'@'localhost';

-- Grant customer limited access
GRANT SELECT, INSERT, UPDATE ON sushi.Customer TO 'sushi_customer'@'localhost';
GRANT SELECT ON sushi.MenuItem TO 'sushi_customer'@'localhost';
GRANT SELECT, INSERT ON sushi.Order_ TO 'sushi_customer'@'localhost';
GRANT SELECT, INSERT ON sushi.OrderItem TO 'sushi_customer'@'localhost';
GRANT SELECT, INSERT ON sushi.Payment TO 'sushi_customer'@'localhost';
GRANT SELECT ON sushi.Seat TO 'sushi_customer'@'localhost';
GRANT UPDATE ON sushi.OrderItem TO 'sushi_customer'@'localhost';

-- Apply privileges
FLUSH PRIVILEGES;