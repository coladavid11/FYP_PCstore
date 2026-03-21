<?php
$currentPage = basename($_SERVER['PHP_SELF']); // e.g. dashboard.php

function navActive($page, $currentPage) {
    return ($page === $currentPage) ? 'active' : '';
}

function navActiveMulti($pages, $currentPage) {
    return in_array($currentPage, $pages) ? 'active' : '';
}
?>

<style>
    body { padding-top: 0 !important; }
    .container { margin-top: 30px; }
</style>

<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #2c3e50; padding: 15px 0; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
  <div class="container">
    <a class="navbar-brand fw-bold" href="dashboard.php" style="font-size: 1.5rem; color: #fff;">
      <i class="fa fa-car"></i> Buat Kerja Betul2 Car Rental Admin
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle=\"collapse\" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('dashboard.php', $currentPage); ?>" href="dashboard.php">Dashboard</a>
        </li>

        <?php $brandsPages = ['create-brand.php','manage-brands.php']; ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?php echo navActiveMulti($brandsPages, $currentPage); ?>" href="#" id="brandsDrop" role="button" data-bs-toggle="dropdown">
            Brands
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item <?php echo navActive('create-brand.php', $currentPage); ?>" href="create-brand.php">Create Brand</a></li>
            <li><a class="dropdown-item <?php echo navActive('manage-brands.php', $currentPage); ?>" href="manage-brands.php">Manage Brands</a></li>
          </ul>
        </li>

        <?php $vehiclesPages = ['post-avehicle.php','manage-vehicles.php']; ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?php echo navActiveMulti($vehiclesPages, $currentPage); ?>" href="#" id="vehiclesDrop" role="button" data-bs-toggle="dropdown">
            Vehicles
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item <?php echo navActive('post-avehicle.php', $currentPage); ?>" href="post-avehicle.php">Post a Vehicle</a></li>
            <li><a class="dropdown-item <?php echo navActive('manage-vehicles.php', $currentPage); ?>" href="manage-vehicles.php">Manage Vehicles</a></li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('manage-bookings.php', $currentPage); ?>" href="manage-bookings.php">Bookings</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('manage-reviews.php', $currentPage); ?>" href="manage-reviews.php">Reviews</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('manage-payments.php', $currentPage); ?> <?php echo ($currentPage==='manage-payments.php') ? 'text-warning fw-bold' : ''; ?>" href="manage-payments.php">
            <i class="fa fa-dollar-sign"></i> Payments
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('reg-users.php', $currentPage); ?>" href="reg-users.php">Users</a>
        </li>

        <li class="nav-item ms-3">
          <div class="dropdown">
            <a class="btn btn-outline-light btn-sm dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
              <i class="fa fa-user-circle"></i> Admin
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item <?php echo navActive('change-password.php', $currentPage); ?>" href="change-password.php">Change Password</a></li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item text-danger" href="#" onclick="confirmLogout(event)">
                  Logout
                </a>
              </li>
            </ul>
          </div>
        </li>

      </ul>
    </div>
  </div>
</nav>

<style>
  .navbar-nav .nav-link { font-size: 0.95rem; margin-left: 10px; transition: 0.3s; color: rgba(255,255,255,0.8); }
  .navbar-nav .nav-link:hover, .navbar-nav .nav-link.active { color: #f1c40f !important; transform: translateY(-2px); }

  /* Make dropdown "active" look same as nav-link active */
  .navbar-nav .nav-link.dropdown-toggle.active { color: #f1c40f !important; }

  .dropdown-menu { border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border-radius: 8px; }
  .dropdown-item:hover { background-color: #f8f9fa; color: #2c3e50; }
  .dropdown-item.active { background-color: rgba(241,196,15,0.18); color: #2c3e50; font-weight: 700; }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  function confirmLogout(e) {
    e.preventDefault();
    Swal.fire({
      title: 'Ready to Leave?',
      text: "You will be logged out of the system.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#2c3e50',
      confirmButtonText: 'Yes, Logout',
      cancelButtonText: 'Cancel',
      heightAuto: false
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = 'logout.php';
      }
    });
  }
</script>