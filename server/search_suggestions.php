<?php
include('connection.php');

$search = isset($_GET['query']) ? mysqli_real_escape_string($conn, $_GET['query']) : '';

$query = "SELECT id, name FROM products WHERE name LIKE '%$search%' LIMIT 5";
$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    echo "<a href='product-detail.php?id=" . $row['id'] . "' class='list-group-item list-group-item-action'>" . htmlspecialchars($row['name']) . "</a>";
}
?>
