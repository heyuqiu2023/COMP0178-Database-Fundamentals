<?php include_once('header.php'); ?>

<div class="container">
  <h2 class="my-3">Create an account</h2>
  <div class="card" style="max-width: 600px; margin: auto;">
    <div class="card-body">
      <form method="post" action="process_registration.php">
        <div class="form-group">
          <label for="regEmail">Email address</label>
          <input type="email" class="form-control" id="regEmail" name="email" required>
        </div>
        <div class="form-group">
          <label for="regUsername">Username</label>
          <input type="text" class="form-control" id="regUsername" name="username" required>
        </div>
        <div class="form-group">
          <label for="regPassword">Password</label>
          <input type="password" class="form-control" id="regPassword" name="password" required>
        </div>
        <div class="form-group">
          <label for="regConfirm">Confirm password</label>
          <input type="password" class="form-control" id="regConfirm" name="confirm" required>
        </div>
        <div class="form-group">
          <label for="regRole">Role</label>
          <select class="form-control" id="regRole" name="role" required>
            <option value="buyer" selected>Buyer</option>
            <option value="seller">Seller</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary form-control">Register</button>
      </form>
    </div>
  </div>
</div>

<?php include_once('footer.php'); ?>