<?php 
  ob_start(); 
  session_start(); 
  require "../koneksi.php"; 
  $username = "";
  $password = ""; 
  if (isset($_POST['loginbtn'])) {
      $username = htmlspecialchars($_POST['username']);
      $password = htmlspecialchars($_POST['password']);
  }
  
     $query = mysqli_query($con, "SELECT * FROM users WHERE username = '$username'");
     $countdata = mysqli_num_rows($query);
     $data = mysqli_fetch_array($query);
 
     if ($countdata > 0) {
         if (password_verify($password, $data['password'])) {
             $_SESSION['username'] = $data['username'];
             $_SESSION['login'] = true;
             header('Location: ./index.php');
             exit; 
         } else {
             $error = "Password salah!!";
         }
     } else {
         $error = "Username dan password salah!!";
        }
?>
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Database Login System</title> 
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./style/login.css">
</head>
<body>
    <!-- Background Animation -->
    <div class="background-animation">
        <!-- Database related floating icons -->
        <i class="fas fa-database floating-icons"></i>
        <i class="fas fa-server floating-icons"></i>
        <i class="fas fa-chart-line floating-icons"></i>
        <i class="fas fa-code floating-icons"></i>
        <i class="fas fa-network-wired floating-icons"></i>
        <i class="fas fa-table floating-icons"></i>
        <i class="fas fa-cloud-download-alt floating-icons"></i>
        <i class="fas fa-cogs floating-icons"></i>
        
        <!-- Data stream effects -->
        <div class="data-flow"></div>
        <div class="data-flow"></div>
        <div class="data-flow"></div>
    </div>

    <div class="main d-flex flex-column justify-content-center align-items-center">
        <div class="login-box shadow">
            <div class="login-header">
                <i class="fas fa-database database-icon"></i>
                <h1 class="login-title">Database</h1>
                <p class="login-subtitle">Selamat Datang di Database ,<br>Kadai Uniang Abak</p>
            </div>
            
            <div class="login-form">
                <form action="" method="post">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" class="form-control" name="username" id="username" placeholder="Masukkan username Anda">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-control" name="password" id="password" placeholder="Masukkan password Anda">
                        </div>
                    </div>
                    
                    <div>
                        <button class="btn btn-success form-control mt-4" type="submit" name="loginbtn">
                            <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>Login
                        </button>
                    </div>
                </form>
                    <?php if (!empty($error)): ?>
                    <div class="alert alert-warning mt-3" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
        </div>
    <?php endif; ?>
            </div>
        </div>
        

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-control');
            const button = document.querySelector('.btn-success');
            
            // Enhanced input focus effects
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.closest('.form-group').style.transform = 'translateY(-2px) scale(1.02)';
                    this.closest('.form-group').style.transition = 'all 0.3s ease';
                });
                
                input.addEventListener('blur', function() {
                    this.closest('.form-group').style.transform = 'translateY(0) scale(1)';
                });

                // Add typing effect
                input.addEventListener('input', function() {
                    if(this.value.length > 0) {
                        this.style.borderColor = '#667eea';
                        this.style.backgroundColor = 'rgba(102, 126, 234, 0.05)';
                    } else {
                        this.style.borderColor = '#e1e5e9';
                        this.style.backgroundColor = 'rgba(255, 255, 255, 0.9)';
                    }
                });
            });

            // Enhanced button ripple effect
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(255, 255, 255, 0.4);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: rippleEffect 0.6s linear;
                    pointer-events: none;
                `;
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });

            // Form validation visual feedback
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;
                
                if(!username || !password) {
                    e.preventDefault();
                    
                    if(!username) {
                        document.getElementById('username').style.borderColor = '#dc3545';
                        document.getElementById('username').style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
                    }
                    if(!password) {
                        document.getElementById('password').style.borderColor = '#dc3545';
                        document.getElementById('password').style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
                    }
                }
            });
        });

        // Add CSS for ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes rippleEffect {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body> 
</html>