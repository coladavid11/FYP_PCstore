<?php
// Get the current page filename (e.g. index.php, about.php)
// This is used to highlight the active menu item
$currentPage = basename($_SERVER['PHP_SELF']);

// Function to return 'active' class if the page matches current page
// This helps show which menu item the user is currently viewing
function navActive($page, $currentPage) {
  return ($page === $currentPage) ? 'active' : '';
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top"
     style="box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
  <div class="container">

    <!-- Website brand / logo -->
    <a class="navbar-brand fw-bold" href="index.php"
       style="font-size: 1.5rem; color: #f1c40f;">
      <i class="fa fa-car"></i> Buat Kerja Betul2 Car Rental
    </a>

    <!-- Mobile menu toggle button -->
    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse"
            data-bs-target="#userNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Navigation links -->
    <div class="collapse navbar-collapse" id="userNav">
      <ul class="navbar-nav ms-auto align-items-center">

        <!-- Home page link -->
        <li class="nav-item">
          <a class="nav-link <?php echo navActive('index.php', $currentPage); ?>"
             href="index.php">Home</a>
        </li>

        <!-- Car listing page link -->
        <li class="nav-item">
          <a class="nav-link <?php echo navActive('car-listing.php', $currentPage); ?>"
             href="car-listing.php">Find a Car</a>
        </li>

        <!-- About page link -->
        <li class="nav-item">
          <a class="nav-link <?php echo navActive('about.php', $currentPage); ?>"
             href="about.php">About Us</a>
        </li>

        <!-- Contact page link -->
        <li class="nav-item">
          <a class="nav-link <?php echo navActive('contact.php', $currentPage); ?>"
             href="contact.php">Contact Us</a>
        </li>

        <!-- Show Login/Register button if user is NOT logged in -->
        <?php if(strlen($_SESSION['login'])==0) { ?>
          <li class="nav-item ms-3">
            <a class="btn btn-outline-warning btn-sm" href="login.php">
              Login / Register
            </a>
          </li>

        <!-- Show user dropdown menu if user IS logged in -->
        <?php } else { ?>
          <li class="nav-item ms-3 dropdown">

            <!-- Display logged-in user's name -->
            <a class="nav-link dropdown-toggle text-warning
               <?php echo (in_array($currentPage, ['my-booking.php','profile.php'])) ? 'active' : ''; ?>"
               href="#" role="button" data-bs-toggle="dropdown">
              <i class="fa fa-user-circle"></i>
              <?php echo htmlentities($_SESSION['fname']); ?>
            </a>

            <!-- User dropdown menu -->
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item <?php echo navActive('my-booking.php', $currentPage); ?>"
                   href="my-booking.php">
                  My Bookings
                </a>
              </li>
              <li>
                <a class="dropdown-item <?php echo navActive('profile.php', $currentPage); ?>"
                   href="profile.php">
                  Profile Settings
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>

              <!-- Logout link -->
              <li>
                <a class="dropdown-item text-danger" href="logout.php">
                  Logout
                </a>
              </li>
            </ul>
          </li>
        <?php } ?>

      </ul>
    </div>
  </div>
</nav>

<style>
  /* Add top spacing because navbar is fixed */
  body { padding-top: 60px; }

  /* Highlight active navigation item */
  .navbar .nav-link.active {
    color: #f1c40f !important;
    font-weight: 700;
  }
</style>
