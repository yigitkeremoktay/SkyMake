<?php
// (C) 2016-2020 Skyfallen
// SkyMake Version 4
// Intended to run on Apache 2 with PHP 7 and above.
session_name('SkyMakeSessionStorage');
session_start();
if (isset($_GET["lang"])) {
    $locale = $_GET["lang"].".UTF-8";
    $_SESSION["locale"] = $locale;
}
else if (isset($_SESSION["locale"])) {
    $locale  = $_SESSION["locale"];
}
else {
    $locale = "en_US";
    $_SESSION["locale"] = $locale;
}

$txtd = "skymake";
textdomain($txtd);
bindtextdomain($txtd,"locale");
bind_textdomain_codeset($txtd,"UTF-8");

putenv("LANG=".$_SESSION["locale"]);
putenv("LANGUAGE=".$_SESSION["locale"]);
if(isset($_GET["redirect_to"])) {
   $_SESSION["redirect_to"] = $_GET["redirect_to"];
}
$results = setlocale(LC_ALL,$_SESSION["locale"]);
if (isset($_GET["dm"])) {
    $dm = $_GET["dm"];
    $_SESSION["dm"] = $dm;
}
else if (isset($_SESSION["dm"])) {
    $dm  = $_SESSION["dm"];
}
else {
    $dm = "no";
    $_SESSION["dm"] = $dm;
}
include_once "SkyMakeDatabaseConnector/SkyMakeDBconfig.php";
include_once "SkyMakeConfiguration.php";
include_once "SkyMakeFunctionSet/Operation-Requirements/MainFunctions.php";
include "classes/user.php";
$l = getsetting($link,"link");
// Check if act is equal to signup
if($_GET["act"] == "signup"){
    //if so set optget to signup
    $optget = "signup";
}else{
    $optget = "signin";
}
$isinstall = false;
$sql = "SELECT * FROM skymake_users WHERE username= 'root'";
if($res = mysqli_query($link,$sql)){
    if(mysqli_num_rows($res) != 1){
        if($optget == "signin"){
            header("location: ?act=signup");
        } else {
            $isinstall = true;
        }
    }
}else {
    die("The was an error connecting to your database. Probably there was an error importing the SkyMake DB install file.");
}
if(file_exists("csc.php") and !file_exists(".htaccess")){
    include_once "csc.php";
    if(DEVENV == false) {
        unlink("csc.php");
        unlink("ApacheHtaccess");
    }
}
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    if(isset($_GET["redirect_to"])) {
        header("Location: " . $_GET["redirect_to"]);
    } else {
        header("Location: /home");
    }
}
// check if this is sign in
if($optget != "signup") {
// Check if the user is already logged in, if yes then redirect him to user page
    $confirm_password_err = "";
// Define variables and initialize with empty values
    $username = $password = "";
    $username_err = $password_err = "";

// Processing form data when form is submitted
// Any post request will trigger including ones that does not carry our password and username
// Will be changed in future builds
// check if this is sign in
    if ($optget != "signup") {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            // Check if username is empty
            if (empty(trim($_POST["skymake-un"]))) {
                // Add this error under username box.
                $username_err = "Please enter username.";
            } else {
                $username = trim($_POST["skymake-un"]);
            }

            // Check if password is empty
            if (empty(trim($_POST["skymake-pw"]))) {
                // Add this error under password.
                $password_err = "Please enter your password.";
            } else {
                $password = trim($_POST["skymake-pw"]);
            }

            // Validate credentials
            if (empty($username_err) && empty($password_err)) {
                // Prepare a select statement
                $sql = "SELECT id, username, password FROM skymake_users WHERE username = ?";

                if ($stmt = mysqli_prepare($link, $sql)) {
                    // Bind variables to the prepared statement as parameters
                    mysqli_stmt_bind_param($stmt, "s", $param_username);

                    // Set parameters
                    $param_username = $username;

                    // Attempt to execute the prepared statement
                    if (mysqli_stmt_execute($stmt)) {
                        // Store result
                        mysqli_stmt_store_result($stmt);

                        // Check if username exists, if yes then verify password
                        if (mysqli_stmt_num_rows($stmt) == 1) {
                            // Bind result variables
                            mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                            if (mysqli_stmt_fetch($stmt)) {
                                if (password_verify($password, $hashed_password)) {
                                    // Password is correct, so start a new session
                                    session_start();

                                    //prevent unathorized updates
                                    $_SESSION["UPDATE_AUTHORIZED"] = false;

                                    // Store data in session variables
                                    $_SESSION["loggedin"] = true;
                                    $_SESSION["id"] = $id;
                                    $_SESSION["username"] = $username;
                                    //get assigned class
                                    $_SESSION["classid"] = SMUser::getStudentClassID($link,$_SESSION["username"]);
                                    $_SESSION["dm"] = "off";
                                    // Redirect user to welcome page
                                    // Logged in successfully.
                                    if(isset($_SESSION["redirect_to"])) {
                                        $newlink = $_SESSION["redirect_to"];
                                        unset($_SESSION["redirect_to"]);
                                        header("Location: " . $newlink);
                                    } else {
                                        header("Location: /home");
                                    }
                                } else {
                                    // Display an error message if password is not valid
                                    $password_err = "The password you entered was not valid.";
                                }
                            }
                        } else {
                            // Display an error message if username doesn't exist
                            $username_err = "No account found with that username.";
                        }
                    } else {
                        // ANY OTHER ERROR - Will need an update in a future build.
                        die("Oops! Something went wrong. Please try again later.");
                    }

                    // Close statement
                    mysqli_stmt_close($stmt);
                }
            }

            // Close connection
            mysqli_close($link);
        }
    }
}
if($optget == "signup"){
    // Define variables and initialize with empty values
    $username = $password = $confirm_password = "";
    $username_err = $password_err = $confirm_password_err = "";

// Processing form data when form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Validate username
        if (empty(trim($_POST["skymake-un"]))) {
            $username_err = _("Please enter a username.");
        } else {
            // Prepare a select statement
            $sql = "SELECT id FROM skymake_users WHERE username = ?";

            if ($stmt = mysqli_prepare($link, $sql)) {
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "s", $param_username);

                // Set parameters
                $param_username = trim($_POST["skymake-un"]);

                // Attempt to execute the prepared statement
                if (mysqli_stmt_execute($stmt)) {
                    // store result
                    mysqli_stmt_store_result($stmt);

                    if (mysqli_stmt_num_rows($stmt) == 1) {
                        $username_err = _("This username is already taken.");
                    } else {
                        $username = trim($_POST["skymake-un"]);
                    }
                } else {
                    die(_("Oops! Something went wrong. Please try again later."));
                }

                // Close statement
                mysqli_stmt_close($stmt);
            }
        }

        // Validate password
        if (empty(trim($_POST["skymake-pw"]))) {
            $password_err = _("Please enter a password.");
        } elseif (strlen(trim($_POST["skymake-pw"])) < 6) {
            $password_err = _("Password must have atleast 6 characters.");
        } else {
            $password = trim($_POST["skymake-pw"]);
        }

        // Validate confirm password
        if (empty(trim($_POST["skymake-pwconfirm"]))) {
            $confirm_password_err = _("Please confirm password.");
        } else {
            $confirm_password = trim($_POST["skymake-pwconfirm"]);
            if (empty($password_err) && ($password != $confirm_password)) {
                $confirm_password_err = _("Password did not match.");
            }
        }

        // Check input errors before inserting in database
        if (empty($username_err) && empty($password_err) && empty($confirm_password_err)) {

            // Prepare an insert statement
            $sql = "INSERT INTO skymake_users (username, password) VALUES (?, ?)";

            if ($stmt = mysqli_prepare($link, $sql)) {
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "ss", $param_username, $param_password);

                // Set parameters
                $param_username = $username;
                $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash

                // Attempt to execute the prepared statement
                if (mysqli_stmt_execute($stmt)) {
                    // Redirect to login page
                    header("location: /?act=signin");
                } else {
                    die("Something went wrong. Please try again later.");
                }

                // Close statement
                mysqli_stmt_close($stmt);
            }
        }

        // Close connection
        mysqli_close($link);
    }
}
$bg = rand(0,2);
?>
<?php if($_SESSION["dm"] == "on"){ $clr = "#373535"; } else { $clr = "white"; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
    <link rel="stylesheet" href="/nps/widgets/assets/fonts/font-awesome.min.css">
    <title>Skyfallen:SkyMake <?php if($optget == "signin"){
            echo _("Sign in");
        } else {
            echo _("Sign up");
        } ?></title>
    <style type="text/css">
        <?php
          if($bg == 0){
              echo "body{ font: 14px sans-serif; background :radial-gradient(#000 2px, transparent 3px); background-size : 20px 20px;}       .content {
                background-color:".$clr.";
                border-radius:.25em;
                box-shadow:0 0 .25em rgba(0,0,0,.25);
                box-sizing:border-box;
                left:50%;
                padding:20px;
                position:fixed;
                text-align:center;
                top:50%;
                transform:translate(-50%, -50%);
                width: 350px;
              }";}
              elseif($bg == 1) {
          echo "body {
        background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
        background-size: 400% 400%;
        animation: gradient 15s ease infinite;
    }
    
    @keyframes gradient {
        0% {
            background-position: 0% 50%;
        }
        50% {
            background-position: 100% 50%;
        }
        100% {
            background-position: 0% 50%;
        }
    }
    .content{ margin-top:100px;background: ".$clr.";padding:20px; border:2px solid Black; border-radius:30px 1px 30px 1px; margin-right:auto; margin-left: auto; width: 350px;}";
    }
    elseif($bg == 2){
    echo "html {
        height:100%;
      }
      
      body {
        margin:0;
      }
      
      .bg {
        animation:slide 3s ease-in-out infinite alternate;
        background-image: linear-gradient(-60deg, #6c3 50%, #09f 50%);
        bottom:0;
        left:-50%;
        opacity:.5;
        position:fixed;
        right:-50%;
        top:0;
        z-index:-1;
      }
      
      .bg2 {
        animation-direction:alternate-reverse;
        animation-duration:4s;
      }
      
      .bg3 {
        animation-duration:5s;
      }
      
      
      h1 {
        font-family:monospace;
      }
      
      @keyframes slide {
        0% {
          transform:translateX(-25%);
        }
        100% {
          transform:translateX(25%);
        }
      }
      .content {
        background-color:".$clr.";
        border-radius:.25em;
        box-shadow:0 0 .25em rgba(0,0,0,.25);
        box-sizing:border-box;
        left:50%;
        padding:20px;
        position:fixed;
        text-align:center;
        top:50%;
        transform:translate(-50%, -50%);
        width: 350px;
      }";
    }
        ?>

        .footer {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            background-color: <?= $clr ?>;
            color: black;
            text-align: center;
        }
        .top-bar {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            background-color: <?= $clr ?>;
            color: black;
            height:40px;
        }
        .sflogo1{
            margin-top:3px;
            margin-bottom:5px;
            margin-left:20px;
            margin-right:10px;
            text-align: left;
        }
        @keyframes sweep {
            0%    {opacity: 0; margin-top: -100px;}
            100%  {opacity: 1; margin-top: 0px;}
        }
        <?php if($_SESSION["dm"] == "on"){ ?>
        h2 {
            color: white;
        }
        label {
            color: white;
        }
        <?php } ?>
    </style>
</head>
<body>
<div class="top-bar">
    <a href="https://theskyfallen.company"><img src="SkyMakeVersionAssets/logo/SkyfallenLogoRB.png" height=30 class="sflogo1"></a>
    <?php if($_SESSION["dm"] == "off") { ?>
        <a class="nav-link" href="?dm=on" style="margin-right: 20px;right:0; float:right;"><div class="fa fa-moon-o"></div></a>
    <?php } else { ?>
        <a class="nav-link" href="?dm=off" style="margin-right: 20px;right:0; float:right;"><div class="fa fa-sun-o"></div></a>
    <?php } ?>
    <select class="custom-select nav-link" style="height:30px; font-size: xx-small; margin-top: 5px; margin-right: 20px;right:0; float:right; background-color: <?php if($_SESSION["dm"] == "on") { echo "black"; } else { echo "white"; } ?>; width: 100px;" onchange="this.options[this.selectedIndex].value && (window.location = '?lang='.concat(this.options[this.selectedIndex].value));">
        <?php if($_SESSION["locale"] == "tr_TR.UTF-8"){ ?>
            <option value="tr_TR">Türkçe (Türkiye)</option>
            <option value="en_US">English (US)</option>
        <?php } else { ?>
            <option value="en_US">English (US)</option>
            <option value="tr_TR">Türkçe (Türkiye)</option>
        <?php } ?>
    </select>
</div>
<?php
if($bg==2){
    echo "<div class=\"bg\"></div>
<div class=\"bg bg2\"></div>
<div class=\"bg bg3\"></div>";
} ?>
<div class="content">
    <h2>SkyMake - <?php if($optget == "signin"){
            echo _("Sign in");
        } elseif($isinstall == true) {
            echo _("Installation");
        } else {
            echo _("Sign Up");
        } ?></h2>
    <?php
    //if(isset($_SESSION["redirect_to"])){ echo "<p>You will be redirected to: ".$_SESSION["redirect_to"]."</p>"; }
    ?>
    <form method="post">
        <div class="form-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
            <label><?= _("Username") ?></label>
            <input type="text" name="skymake-un" class="form-control" value="<?php if($isinstall == false){ echo $username."\"";} else { echo "root\" readonly";}?>>
                <span class="help-block"><?php echo $username_err; ?></span>
        </div>
        <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
            <label><?= _("Password") ?></label>
            <input type="password" name="skymake-pw" class="form-control">
            <span class="help-block"><?php echo $password_err; ?></span>
        </div>
        <?php if($optget == "signup"){
            ?>
            <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                <label><?= _("Confirm Password") ?></label>
                <input type="password" name="skymake-pwconfirm" class="form-control">
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <?php
        } echo $confirm_password_err; ?><br>
        <div class="form-group">
            <input class="btn btn-dark" style="margin-bottom: 20px;" type="submit" value="<?php if($optget == "signin"){
                echo _("Sign in");
            } else {
                echo _("Sign up");
            } ?>">
        </div>
        <?php if($optget == "signin"){
            echo "<a href='/?act=signup' class='actswitch'>"._("Don't have an account?")."</a>";
        } else {
            echo "<a href='/?act=signin' class='actswitch'>"._("Have an account?")."</a>";
        } ?>
    </form>
</div>
</body>
<div class="footer">
    <p>SkyMake Version 4 : &copy; Copyright 2016-2020 | The Skyfallen Company<br><a href="/legacy/" style="padding-top: 5px;">Use Legacy Login</div>
</html>
