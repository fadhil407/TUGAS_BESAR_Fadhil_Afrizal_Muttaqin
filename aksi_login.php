<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameInput = $_POST['login_identity'] ?? '';
    $password = $_POST['login_passphrase'] ?? '';
    $username = is_string($usernameInput) ? trim($usernameInput) : '';

    if ($username !== '' && is_string($password) && $password !== '') {
        $statement = mysqli_prepare($koneksi, 'SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1');

        if ($statement) {
            mysqli_stmt_bind_param($statement, 's', $username);
            if (mysqli_stmt_execute($statement)) {
                $result = mysqli_stmt_get_result($statement);
                $user = $result ? mysqli_fetch_assoc($result) : null;

                if ($user && in_array($user['role'], ['admin', 'guru', 'siswa'], true)) {
                    $passwordOk = password_verify($password, $user['password'])
                        || $password === $user['password'];

                    if ($passwordOk) {
                        session_regenerate_id(true);
                        $_SESSION['user'] = [
                            'id' => $user['id'],
                            'username' => $user['username'],
                            'role' => $user['role'],
                        ];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['login'] = true;
                        unset($_SESSION['login_error']);
                        redirect_to_role_dashboard();
                    }
                }
            } else {
                error_log('Login query failed: ' . mysqli_stmt_error($statement));
                $_SESSION['login_error'] = 'Terjadi kesalahan saat memproses login. Silakan coba lagi.';
            }

            mysqli_stmt_close($statement);
        } else {
            error_log('Could not prepare login query: ' . mysqli_error($koneksi));
            $_SESSION['login_error'] = 'Terjadi kesalahan saat memproses login. Silakan coba lagi.';
        }
    }

    if (!isset($_SESSION['login_error'])) {
        $_SESSION['login_error'] = 'Login gagal. Username atau password salah.';
    }
    header('Location: ' . app_url('login.php'));
    exit();
}

header('Location: ' . app_url('login.php'));
exit();
?>