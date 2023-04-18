<?php
// Read the JSON file and convert it to a PHP array
$json = file_get_contents('Code Challenge (Sales).json');
$data = json_decode($json, true);

// Connect to the MySQL database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "book_shop";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Loop through the data and insert it into the database
foreach ($data as $row) {
    // Insert/update customer
    $customer_query = "INSERT INTO customers (name, email) VALUES (?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)";
    $stmt = $conn->prepare($customer_query);
    $stmt->bind_param("ss", $row["customer_name"], $row["customer_mail"]);
    $stmt->execute();
    $customer_id = $stmt->insert_id;

    // Insert/update product
    $product_query = "INSERT INTO products (name) VALUES (?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)";
    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("s", $row["product_name"]);
    $stmt->execute();
    $product_id = $stmt->insert_id;

    // Insert/update product price
    $product_price_query = "INSERT INTO product_prices (product_id, price, start_date, end_date) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE end_date = IFNULL(?, end_date), id = LAST_INSERT_ID(id)";
    $stmt = $conn->prepare($product_price_query);
    $stmt->bind_param("idsis", $product_id, $row["product_price"], $row["sale_date"], $end_date, $end_date);
    $end_date = null; // set default value for end_date
    $stmt->execute();
    $product_price_id = $stmt->insert_id;

    // Insert sale
    $sale_query = "INSERT INTO sales (customer_id, product_id, product_price_id, sale_date) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sale_query);
    $stmt->bind_param("iiis", $customer_id, $product_id, $product_price_id, $row["sale_date"]);
    $stmt->execute();
}

// Update end_date in product_prices table
$update_query = "UPDATE product_prices SET end_date = (SELECT MIN(start_date) FROM product_prices p2 WHERE p2.product_id = product_prices.product_id AND p2.start_date > product_prices.start_date) WHERE end_date IS NULL";
$conn->query($update_query);

// Close the connection
$conn->close();
?>
