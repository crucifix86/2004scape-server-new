<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns:IE>

<!-- RuneScape 2006 Website Template Registration -->
<meta http-equiv="content-type" content="text/html;charset=ISO-8859-1">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<head>
<meta http-equiv="Expires" content="0">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Cache-Control" content="no-cache">
<meta name="MSSmartTagsPreventParsing" content="TRUE">
<title>BrainScape - Create Account</title>
<link rel="shortcut icon" href='img/favicon.ico' />
<link href="css/basic-3.css" rel="stylesheet" type="text/css" media="all">
<link href="css/main/title-5.css" rel="stylesheet" type="text/css" media="all">
<style>
.registration-form {
    background: linear-gradient(135deg, #1e3c72, #2a5298);
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.3);
    max-width: 500px;
    margin: 20px auto;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    color: #ffd700;
    margin-bottom: 5px;
    font-weight: bold;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.form-group input {
    width: 100%;
    padding: 8px;
    border: 2px solid #333;
    background: rgba(255,255,255,0.9);
    border-radius: 5px;
    font-size: 14px;
}

.form-group input:focus {
    outline: none;
    border-color: #ffd700;
    background: #fff;
}

.form-help {
    color: #ccc;
    font-size: 12px;
    margin-top: 5px;
}

.register-button {
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    color: #333;
    border: none;
    padding: 12px 30px;
    font-size: 16px;
    font-weight: bold;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 10px;
    width: 100%;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}

.register-button:hover {
    background: linear-gradient(135deg, #ffed4e, #ffd700);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

.register-button:disabled {
    background: #666;
    cursor: not-allowed;
    transform: none;
}

.error-message {
    background: rgba(255,0,0,0.2);
    border: 1px solid #ff0000;
    color: #ff9999;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
    display: none;
}

.success-message {
    background: rgba(0,255,0,0.2);
    border: 1px solid #00ff00;
    color: #99ff99;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
    display: none;
}

.requirements {
    background: rgba(0,0,0,0.3);
    padding: 10px;
    border-radius: 5px;
    margin-top: 15px;
}

.requirements h4 {
    color: #ffd700;
    margin-top: 0;
}

.requirements ul {
    color: #fff;
    font-size: 13px;
    margin: 5px 0;
    padding-left: 20px;
}

.requirements li {
    margin: 3px 0;
}
</style>
</head>
<body>

<div id="body">
<div>
<div style="text-align: center; margin-bottom: 10px; position:relative;">
<img src="img/title2/logo.png" alt="BrainScape"><br></div>
</div>
<div class="left">
<fieldset class="menu rs">
<legend>BrainScape</legend>
<ul>
<li class="i-create"><a href="index.php">Home</a></li>
<li class="i-create"><a href="forums">Forums</a></li>
<li class="i-create"><a href="client/index.php=option1.php">Play Now</a></li>
<li class="i-create"><a href="http://www.facebook.com/pages/BrainScapeRS/464826750195293">Our Facebook Page</a></li>
</ul>
</fieldset>

<fieldset class="menu help">
<legend>Registration Help</legend>
<ul>
<li class="i-started"><a href="ex/geting-started.php">Getting Started Guide</a></li>
<li class="i-rules"><a href="ex/rules.php">Game Rules</a></li>
<li class="i-safety"><a href="ex/your-safety.php">Account Security</a></li>
</ul>
</fieldset>

<fieldset class="menu rs">
<legend>Already Registered?</legend>
<ul>
<li class="i-play"><a href="client/index.php=option1.php">Play Game</a></li>
<li class="i-pw"><a href="#">Forgot Password</a></li>
</ul>
</fieldset>
</div>

<div class="newscontainer">
<img class="narrowscroll-top" src="img/scroll/scroll457_top.gif" alt="" width="466" height="50">
<div class="narrowscroll-bg">
<div class="narrowscroll-bgimg">
<div class="narrowscroll-content">

<div class="registration-form">
    <h2 style="color: #ffd700; text-align: center; margin-top: 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">Create Your Account</h2>
    
    <div id="error-message" class="error-message"></div>
    <div id="success-message" class="success-message"></div>
    
    <form id="registration-form" onsubmit="return false;">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" maxlength="12" required>
            <div class="form-help">3-12 characters, letters, numbers and underscores only</div>
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" maxlength="20" required>
            <div class="form-help">5-20 characters</div>
        </div>
        
        <div class="form-group">
            <label for="confirm-password">Confirm Password:</label>
            <input type="password" id="confirm-password" name="confirm-password" maxlength="20" required>
            <div class="form-help">Re-enter your password</div>
        </div>
        
        <div class="form-group">
            <label for="email">Email (Optional):</label>
            <input type="email" id="email" name="email" maxlength="100">
            <div class="form-help">For account recovery only</div>
        </div>
        
        <button type="submit" class="register-button" id="register-btn">Create Account</button>
    </form>
    
    <div class="requirements">
        <h4>Account Requirements:</h4>
        <ul>
            <li>Username must be 3-12 characters</li>
            <li>Username can only contain letters, numbers, and underscores</li>
            <li>Password must be 5-20 characters</li>
            <li>Each account is unique - no duplicate usernames</li>
            <li>Remember your login details!</li>
        </ul>
    </div>
</div>

</div>
</div>
</div>
<img class="narrowscroll-bottom" src="img/scroll/scroll457_bottom.gif" alt="" width="466" height="50">
</div>
<div class="tandc">
  <p>Copyright &copy; 2012 BrainScape - All Rights Reserved</p>
</div>
</div>

<script>
$(document).ready(function() {
    $('#registration-form').on('submit', function(e) {
        e.preventDefault();
        
        // Clear previous messages
        $('#error-message').hide().text('');
        $('#success-message').hide().text('');
        
        // Get form values
        const username = $('#username').val().trim();
        const password = $('#password').val();
        const confirmPassword = $('#confirm-password').val();
        const email = $('#email').val().trim();
        
        // Validation
        if (username.length < 3 || username.length > 12) {
            $('#error-message').text('Username must be between 3 and 12 characters').show();
            return;
        }
        
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            $('#error-message').text('Username can only contain letters, numbers, and underscores').show();
            return;
        }
        
        if (password.length < 5 || password.length > 20) {
            $('#error-message').text('Password must be between 5 and 20 characters').show();
            return;
        }
        
        if (password !== confirmPassword) {
            $('#error-message').text('Passwords do not match').show();
            return;
        }
        
        // Disable button during request
        $('#register-btn').prop('disabled', true).text('Creating Account...');
        
        // Send registration request to the game's database
        $.ajax({
            url: 'api/register_game.php',
            method: 'POST',
            data: {
                username: username,
                password: password,
                email: email
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#success-message').html(
                        'Account created successfully!<br>' +
                        'Username: <strong>' + username + '</strong><br>' +
                        'You can now <a href="client/index.php=option1.php" style="color: #ffd700;">play the game</a>!'
                    ).show();
                    $('#registration-form')[0].reset();
                    
                    // Redirect to game after 3 seconds
                    setTimeout(function() {
                        window.location.href = 'client/index.php=option1.php';
                    }, 3000);
                } else {
                    $('#error-message').text(response.message || 'Registration failed').show();
                    $('#register-btn').prop('disabled', false).text('Create Account');
                }
            },
            error: function(xhr, status, error) {
                $('#error-message').text('Connection error. Please try again.').show();
                $('#register-btn').prop('disabled', false).text('Create Account');
            }
        });
    });
    
    // Real-time validation feedback
    $('#username').on('input', function() {
        const val = $(this).val();
        if (val.length > 0) {
            if (!/^[a-zA-Z0-9_]+$/.test(val)) {
                $(this).css('border-color', '#ff0000');
            } else if (val.length < 3) {
                $(this).css('border-color', '#ffaa00');
            } else {
                $(this).css('border-color', '#00ff00');
            }
        } else {
            $(this).css('border-color', '#333');
        }
    });
    
    $('#confirm-password').on('input', function() {
        if ($(this).val() !== $('#password').val()) {
            $(this).css('border-color', '#ff0000');
        } else {
            $(this).css('border-color', '#00ff00');
        }
    });
});
</script>

</body>
</html>