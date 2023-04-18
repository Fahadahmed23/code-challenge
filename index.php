<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer = isset($_POST["customer"]) ? $_POST["customer"] : "";
    $product = isset($_POST["product"]) ? $_POST["product"] : "";
    $min_price = isset($_POST["min_price"]) ? $_POST["min_price"] : "";
    $max_price = isset($_POST["max_price"]) ? $_POST["max_price"] : "";

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "book_shop";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $query = "SELECT s.id, c.name as customer_name, p.name as product_name, pp.price, s.sale_date 
          FROM sales s
          INNER JOIN customers c ON s.customer_id = c.id
          INNER JOIN products p ON s.product_id = p.id
          INNER JOIN product_prices pp ON s.product_price_id = pp.id
          WHERE c.name LIKE ? AND p.name LIKE ?";

    if ($min_price !== "") {
        $query .= " AND pp.price >= ?";
    }

    if ($max_price !== "") {
        $query .= " AND pp.price <= ?";
    }

    $stmt = $conn->prepare($query);
    $customer = "%$customer%";
    $product = "%$product%";

    if ($min_price !== "" && $max_price !== "") {
        $stmt->bind_param("ssdd", $customer, $product, $min_price, $max_price);
    } elseif ($min_price !== "") {
        $stmt->bind_param("ssd", $customer, $product, $min_price);
    } elseif ($max_price !== "") {
        $stmt->bind_param("ssd", $customer, $product, $max_price);
    } else {
        $stmt->bind_param("ss", $customer, $product);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $total_price = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Filter</title>
  
  </head>
  <body>
    <h1>Sales Filter</h1>
    <form action="
					<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
      <label for="customer">Customer:</label>
      <input type="text" name="customer" id="customer">
      <label for="product">Product:</label>
      <input type="text" name="product" id="product">
      <label for="min_price">Min Price:</label>
      <input type="number" step="0.01" name="min_price" id="min_price">
      <label for="max_price">Max Price:</label>
      <input type="number" step="0.01" name="max_price" id="max_price">
      <input type="submit" value="Filter">
    </form> <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?> <h2>Filtered Sales</h2>
    <table border="1">
      <thead>
        <tr>
          <th>ID</th>
          <th>Customer</th>
          <th>Product</th>
          <th>Price</th>
          <th>Sale Date</th>
        </tr>
      </thead>
      <tbody> <?php while ($row = $result->fetch_assoc()): ?> <tr>
          <td> <?= $row["id"] ?> </td>
          <td> <?= $row["customer_name"] ?> </td>
          <td> <?= $row["product_name"] ?> </td>
          <td> <?= $row["price"] ?> </td>
          <td> <?= $row["sale_date"] ?> </td>
        </tr> <?php $total_price += $row["price"]; ?> <?php endwhile; ?> </tbody>
      <tfoot>
        <tr>
          <td colspan="3">Total Price:</td>
          <td> <?= $total_price ?> </td>
          <td></td>
        </tr>
      </tfoot>
    </table> <?php endif; ?>
  </body>