<?php
session_start();
include 'koneksi.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Query untuk mengecek kecocokan data
    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username' AND password='$password'");
    $cek = mysqli_num_rows($query);

    if ($cek > 0) {
        $data = mysqli_fetch_assoc($query);
        
        // Simpan sesi
        $_SESSION['id_user'] = $data['id_user'];
        $_SESSION['username'] = $data['username'];
        $_SESSION['role'] = $data['role'];
        $_SESSION['status'] = "login";
        

        // Arahkan sesuai role
        if ($data['role'] == 'admin') {
            header("location:admin/index.php");
        } else if ($data['role'] == 'user') {
            header("location:user/kuesioner.php");
        }
    } else {
        echo "<script>alert('Username atau Password salah!'); window.location='login.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Login - SPK TOPSIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-primary d-flex align-items-center" style="height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow-lg border-0 rounded-lg mt-5">
                    <div class="card-header text-center bg-white pt-4 pb-2">
                        <h3 class="font-weight-light text-primary fw-bold">Login Sistem</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="text-muted">Username</label>
                                <input class="form-control py-3" name="username" type="text" placeholder="Masukkan username" required />
                            </div>
                            <div class="mb-4">
                                <label class="text-muted">Password</label>
                                <input class="form-control py-3" name="password" type="password" placeholder="Masukkan password" required />
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" name="login" class="btn btn-primary py-2 fw-bold">LOGIN</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>