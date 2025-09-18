   <?php
   $current_page = basename($_SERVER['PHP_SELF']);
   ?>
  
  <!-- Sidebar -->
  <div class="sidebar p-3" id="sidebar">
    <h4 class="text-white mb-4 text-center"><i class="fas fa-mug-hot me-2"></i>Izana Admin</h4>
    <ul class="nav nav-pills flex-column">
      <li>
        <a href="<?php echo BASE_URL; ?>admin/admin.php" class="nav-link <?php echo ($active_page == 'admin') ? 'active' : '' ?>">
          <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
      </li>
      <li>
        <a href="<?php echo BASE_URL; ?>admin/view_customers.php" class="nav-link <?php echo ($active_page == 'view_customers') ? 'active' : '' ?>">
          <i class="fas fa-users me-2"></i>View Customers
        </a>
      </li>
      <li>
        <a href="<?php echo BASE_URL; ?>admin/view_orders.php" class="nav-link <?php echo ($active_page == 'view_orders') ? 'active' : '' ?>">
          <i class="fas fa-receipt me-2"></i>View Orders
        </a>
      </li>
      <li>
        <a href="<?php echo BASE_URL; ?>admin/product-categories.php" class="nav-link <?php if ($current_page === 'product-categories.php') {echo 'active'; } ?>"">
          <i class="fas fa-mug-hot me-2"></i>Products Categories
        </a>
      </li>
      <li>
        <a href="<?php echo BASE_URL; ?>admin/manage_products.php" class="nav-link <?php if ($current_page === 'manage_products.php') {echo 'active'; } ?>">
          <i class="fas fa-mug-hot me-2"></i>Manage Products
        </a>
      </li>
      <li>
        <a href="<?php echo BASE_URL; ?>admin/cashier.php" class="nav-link <?php if ($current_page === 'cashier.php') {echo 'active'; } ?>">
          <i class="fas fa-cash-register me-2"></i>Online Cashier
        </a>
      </li>
      <li>
        <a href="<?php echo BASE_URL; ?>admin/manage_cashier.php" class="nav-link <?php if ($current_page === 'manage_cashier.php') {echo 'active'; } ?>">
          <i class="fas fa-users-cog me-2"></i>POS
        </a>
      </li>
      <li>
        <a href="<?php echo BASE_URL; ?>admin/sales_report.php" class="nav-link <?php if ($current_page === 'sales_report.php') {echo 'active'; } ?>">
          <i class="fas fa-chart-line me-2"></i>Sales Report
        </a>
      </li>
      <li>
        <a href="<?php echo BASE_URL; ?>admin/salesHistory.php" class="nav-link <?php if ($current_page === 'salesHistory.php') {echo 'active'; } ?>">
          <i class="fas fa-history me-2"></i>Sales History
        </a>
      </li>
      <li>
        <a href="<?php echo BASE_URL; ?>admin/edit_profile.php" class="nav-link <?php if ($current_page === 'edit_profile.php') {echo 'active'; } ?>">
          <i class="fas fa-user-edit me-2"></i>Edit Profile
        </a>
      </li>
      <li>
        <a href="<?php echo BASE_URL; ?>admin/Logout_A.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
      </li>
    </ul>
  </div>