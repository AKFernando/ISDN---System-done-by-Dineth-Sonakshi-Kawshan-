<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$role = $_SESSION['role'];
$message = '';
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($role == 'admin') {
        if (isset($_POST['add_product'])) {
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $category = mysqli_real_escape_string($conn, $_POST['category']);
            $price = floatval($_POST['price']);
            $stock = intval($_POST['stock']);
            $rdc_location = mysqli_real_escape_string($conn, $_POST['rdc_location']);
            
            $image = NULL;
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $target_dir = "uploads/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $image = basename($_FILES["image"]["name"]);
                $target_file = $target_dir . time() . "_" . $image;
                move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
                $image = $target_file;
            }
            
            $query = "INSERT INTO products (name, category, price, stock, rdc_location, image) VALUES ('$name', '$category', $price, $stock, '$rdc_location', " . ($image ? "'$image'" : "NULL") . ")";
            if (mysqli_query($conn, $query)) {
                $message = 'Product added successfully';
            }
        }
        
        if (isset($_POST['edit_product'])) {
            $id = intval($_POST['product_id']);
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $category = mysqli_real_escape_string($conn, $_POST['category']);
            $price = floatval($_POST['price']);
            $stock = intval($_POST['stock']);
            $rdc_location = mysqli_real_escape_string($conn, $_POST['rdc_location']);
            
            $image_update = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $target_dir = "uploads/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $image = basename($_FILES["image"]["name"]);
                $target_file = $target_dir . time() . "_" . $image;
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image_update = ", image='$target_file'";
                }
            }
            
            $query = "UPDATE products SET name='$name', category='$category', price=$price, stock=$stock, rdc_location='$rdc_location'$image_update WHERE id=$id";
            if (mysqli_query($conn, $query)) {
                $message = 'Product updated successfully';
            }
        }
        
        if (isset($_POST['delete_product'])) {
            $id = intval($_POST['product_id']);
            $query = "DELETE FROM products WHERE id=$id";
            if (mysqli_query($conn, $query)) {
                $message = 'Product deleted successfully';
            }
        }
    }
    
    if ($role == 'customer' && isset($_POST['add_to_cart'])) {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        if (!isset($cart[$product_id])) {
            $cart[$product_id] = 0;
        }
        $cart[$product_id] += $quantity;
        $_SESSION['cart'] = $cart;
        header('Location: cart.php');
        exit();
    }
}

// Get search and category filter parameters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// Build the WHERE clause for filtering
$where_clauses = array();
if (!empty($search_query)) {
    $search_escaped = mysqli_real_escape_string($conn, $search_query);
    $where_clauses[] = "(name LIKE '%$search_escaped%' OR category LIKE '%$search_escaped%')";
}
if (!empty($category_filter)) {
    $category_escaped = mysqli_real_escape_string($conn, $category_filter);
    $where_clauses[] = "category = '$category_escaped'";
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Get filtered products
$products = mysqli_query($conn, "SELECT * FROM products $where_sql ORDER BY id DESC");

// Get all unique categories for the filter dropdown
$categories_query = mysqli_query($conn, "SELECT DISTINCT category FROM products ORDER BY category ASC");
$categories = array();
while ($cat_row = mysqli_fetch_assoc($categories_query)) {
    $categories[] = $cat_row['category'];
}

$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$edit_id"));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - ISDN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="ecommerce-styles.css">
    <script src="theme-currency.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <?php if($message): ?>
            <div class="alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if($role == 'admin'): ?>
            <div class="page-header">
                <h1><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h1>
            </div>
            
            <div class="section-card">
                <form method="POST" enctype="multipart/form-data">
                    <?php if($edit_product): ?>
                        <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" name="name" value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" name="category" value="<?php echo $edit_product ? htmlspecialchars($edit_product['category']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Stock Quantity</label>
                            <input type="number" name="stock" value="<?php echo $edit_product ? $edit_product['stock'] : ''; ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>RDC Location</label>
                            <select name="rdc_location" required>
                                <option value="">Select RDC</option>
                                <option value="North RDC" <?php echo ($edit_product && $edit_product['rdc_location'] == 'North RDC') ? 'selected' : ''; ?>>North RDC</option>
                                <option value="South RDC" <?php echo ($edit_product && $edit_product['rdc_location'] == 'South RDC') ? 'selected' : ''; ?>>South RDC</option>
                                <option value="East RDC" <?php echo ($edit_product && $edit_product['rdc_location'] == 'East RDC') ? 'selected' : ''; ?>>East RDC</option>
                                <option value="West RDC" <?php echo ($edit_product && $edit_product['rdc_location'] == 'West RDC') ? 'selected' : ''; ?>>West RDC</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Product Image</label>
                            <?php if($edit_product && $edit_product['image'] && file_exists($edit_product['image'])): ?>
                                <div style="margin-bottom: 10px;">
                                    <img src="<?php echo $edit_product['image']; ?>" alt="Current Image" style="max-width: 150px; max-height: 150px; border-radius: 5px; border: 2px solid #e0e0e0;">
                                    <p style="font-size: 0.85rem; color: #666; margin-top: 5px;">Current Image</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="image" accept="image/*">
                            <small style="color: #666; font-size: 0.85rem;">
                                <?php echo $edit_product ? 'Upload new image to replace current one' : 'Upload product image (JPG, PNG)'; ?>
                            </small>
                        </div>
                    </div>
                    <?php if($edit_product): ?>
                        <button type="submit" name="edit_product" class="btn-primary">Update Product</button>
                        <a href="products.php" class="btn-secondary">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_product" class="btn-primary">Add Product</button>
                    <?php endif; ?>
                </form>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h1><?php echo $role == 'admin' ? 'All Products' : ($role == 'rdc' ? 'Stock View' : 'Browse Products'); ?></h1>
        </div>

        <!-- Search and Filter Section -->
        <div class="section-card search-filter-section">
            <?php 
            mysqli_data_seek($products, 0);
            $result_count = mysqli_num_rows($products);
            mysqli_data_seek($products, 0);
            ?>
            <?php if(!empty($search_query) || !empty($category_filter)): ?>
                <div class="search-results-info">
                    <div class="results-badge">
                        <i class="fas fa-check-circle"></i>
                        <span>Found <strong><?php echo $result_count; ?></strong> product<?php echo $result_count != 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="results-details">
                        <?php if(!empty($search_query)): ?>
                            <span class="search-term">
                                <i class="fas fa-search"></i>
                                "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
                            </span>
                        <?php endif; ?>
                        <?php if(!empty($category_filter)): ?>
                            <span class="category-badge">
                                <i class="fas fa-tag"></i>
                                <?php echo htmlspecialchars($category_filter); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            <form method="GET" action="products.php" class="search-filter-form">
                <?php if(isset($_GET['edit'])): ?>
                    <input type="hidden" name="edit" value="<?php echo htmlspecialchars($_GET['edit']); ?>">
                <?php endif; ?>
                <div class="amazon-search-bar">
                    <div class="category-dropdown-wrapper">
                        <select name="category" class="category-dropdown">
                            <option value="">All</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category_filter == $cat) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="text" name="search" placeholder="Search ISDN" value="<?php echo htmlspecialchars($search_query); ?>" class="amazon-search-input">
                    <button type="submit" class="amazon-search-button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <?php if(!empty($search_query) || !empty($category_filter)): ?>
                    <div style="margin-top: 1rem; text-align: right;">
                        <a href="products.php<?php echo isset($_GET['edit']) ? '?edit=' . intval($_GET['edit']) : ''; ?>" class="clear-filter-link">
                            <i class="fas fa-times"></i> Clear filters
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <?php if($role == 'customer'): ?>
            <?php 
            mysqli_data_seek($products, 0);
            $product_count = mysqli_num_rows($products);
            ?>
            <?php if($product_count == 0): ?>
                <div class="section-card no-results">
                    <div style="text-align: center; padding: 3rem 2rem;">
                        <i class="fas fa-search" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                        <h3>No products found</h3>
                        <p>Try adjusting your search or filter criteria.</p>
                        <?php if(!empty($search_query) || !empty($category_filter)): ?>
                            <a href="products.php" class="btn-primary" style="margin-top: 1rem; display: inline-block;">Show All Products</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php while($product = mysqli_fetch_assoc($products)): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if($product['image'] && file_exists($product['image'])): ?>
                                <img src="<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <div class="product-placeholder"><i class="fas fa-box"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-category"><?php echo htmlspecialchars($product['category']); ?></p>
                            <p class="product-price"><span class="price-display" data-price="<?php echo $product['price']; ?>">$<?php echo number_format($product['price'], 2); ?></span></p>
                            <p class="product-stock">Stock: <?php echo $product['stock']; ?> | <?php echo htmlspecialchars($product['rdc_location']); ?></p>
                            <?php if($product['stock'] > 0): ?>
                                <form method="POST" class="product-form">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="quantity-input">
                                    <button type="submit" name="add_to_cart" class="btn-primary btn-small">Add to Cart</button>
                                </form>
                            <?php else: ?>
                                <p class="out-of-stock">Out of Stock</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <?php 
            mysqli_data_seek($products, 0);
            $product_count = mysqli_num_rows($products);
            ?>
            <?php if($product_count == 0): ?>
                <div class="section-card no-results">
                    <div style="text-align: center; padding: 3rem 2rem;">
                        <i class="fas fa-search" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                        <h3>No products found</h3>
                        <p>Try adjusting your search or filter criteria.</p>
                        <?php if(!empty($search_query) || !empty($category_filter)): ?>
                            <a href="products.php" class="btn-primary" style="margin-top: 1rem; display: inline-block;">Show All Products</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
            <div class="section-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>RDC Location</th>
                            <?php if($role == 'admin'): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php mysqli_data_seek($products, 0); ?>
                        <?php while($product = mysqli_fetch_assoc($products)): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td><span class="price-display" data-price="<?php echo $product['price']; ?>">$<?php echo number_format($product['price'], 2); ?></span></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td><?php echo htmlspecialchars($product['rdc_location']); ?></td>
                                <?php if($role == 'admin'): ?>
                                    <td>
                                        <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn-small btn-secondary">Edit</a>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" name="delete_product" class="btn-small btn-danger" onclick="return confirm('Delete this product?')">Delete</button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
