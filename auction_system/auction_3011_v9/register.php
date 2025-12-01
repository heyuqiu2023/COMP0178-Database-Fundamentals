<?php include_once("header.php")?>

<div class="container">
<h2 class="my-3">Register new account</h2>

<?php
if (!empty($_SESSION['register_errors'])) {
    echo '<div class="alert alert-danger">';
    foreach ($_SESSION['register_errors'] as $error) {
        echo "<div>$error</div>";
    }
    echo '</div>';
}
?>

<!-- Create auction form -->
<form method="POST" action="/auction_system/process_registration.php">

 <div class="form-group row">
   <label for="accountType" class="col-sm-2 col-form-label text-right">Registering as a:</label>
	  <div class="col-sm-10">
	   <div class="form-check form-check-inline">
       <input class="form-check-input" type="radio" name="accountType" id="accountBuyer" value="buyer" 
              <?= (($_SESSION['register_old']['accountType'] ?? '') === 'buyer') ? 'checked' : '' ?>>
       <label class="form-check-label" for="accountBuyer">Buyer</label>
     </div>

     <div class="form-check form-check-inline">
       <input class="form-check-input" type="radio" name="accountType" id="accountSeller" value="seller"
              <?= (($_SESSION['register_old']['accountType'] ?? '') === 'seller') ? 'checked' : '' ?>>
       <label class="form-check-label" for="accountSeller">Seller</label>
     </div>
     <small id="accountTypeHelp" class="form-text-inline text-muted"><span class="text-danger">* Required.</span></small>
	 </div>
 </div>

 <div class="form-group row">
    <label for="email" class="col-sm-2 col-form-label text-right">Email</label>
	 <div class="col-sm-10">
     <input type="text" class="form-control" name="email" id="email" placeholder="Email"
            value="<?= $_SESSION['register_old']['email'] ?? '' ?>">
     <small id="emailHelp" class="form-text text-muted"><span class="text-danger">* Required.</span></small>
	 </div>
 </div>

 <div class="form-group row">
    <label for="username" class="col-sm-2 col-form-label text-right">Username</label>
   <div class="col-sm-10">
     <input type="text" class="form-control" id="username" name="username" placeholder="Username"
            value="<?= $_SESSION['register_old']['username'] ?? '' ?>">
     <small class="form-text text-muted"><span class="text-danger">* Required.</span></small>
   </div>
 </div>

 <div class="form-group row">
    <label for="password" class="col-sm-2 col-form-label text-right">Password</label>
   <div class="col-sm-10">
     <input type="password" class="form-control" name="password" id="password" placeholder="Password">
     <small id="passwordHelp" class="form-text text-muted"><span class="text-danger">* Required.</span></small>
   </div>
 </div>

 <div class="form-group row">
    <label for="passwordConfirmation" class="col-sm-2 col-form-label text-right">Repeat password</label>
   <div class="col-sm-10">
     <input type="password" class="form-control" name="passwordConfirmation" id="passwordConfirmation" placeholder="Enter password again">
     <small id="passwordConfirmationHelp" class="form-text text-muted"><span class="text-danger">* Required.</span></small>
   </div>
 </div>

 <div class="form-group row">
   <button type="submit" class="btn btn-primary form-control">Register</button>
 </div>
</form>

<div class="text-center">Already have an account? <a href="" data-toggle="modal" data-target="#loginModal">Login</a>

</div>

<?php 
unset($_SESSION['register_errors']);
unset($_SESSION['register_old']);

include_once("footer.php");
?>