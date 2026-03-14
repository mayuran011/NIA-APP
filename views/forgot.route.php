<?php
if (!defined('in_nia_app')) exit;
if (is_logged()) {
    redirect(url('dashboard'));
}
$message = '';
$error = '';
$page_title = 'Forgot password';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'forgot') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $res = auth_forgot_password($email);
    if ($res['ok']) {
        $message = 'If that email is registered, we sent reset instructions.';
    } else {
        $error = $res['error'] === 'email_not_found' ? 'No account found with that email.' : $res['error'];
    }
}
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card bg-dark border-secondary shadow">
                <div class="card-body p-4">
                    <h1 class="h4 mb-4 d-flex align-items-center gap-2">
                        <span class="material-icons text-primary">lock_reset</span>
                        Forgot password
                    </h1>
                    <?php if ($message) { ?>
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
                        <span class="material-icons">check_circle_outline</span>
                        <?php echo _e($message); ?>
                    </div>
                    <p class="small text-muted mb-0">
                        <a href="<?php echo url('login'); ?>" class="d-inline-flex align-items-center gap-1 text-decoration-none">
                            <span class="material-icons" style="font-size:1rem;">arrow_back</span> Back to sign in
                        </a>
                    </p>
                    <?php } elseif ($error) { ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
                        <span class="material-icons">error_outline</span>
                        <?php echo _e($error); ?>
                    </div>
                    <?php } ?>
                    <?php if (!$message) { ?>
                    <p class="text-muted small mb-3">Enter your email and we’ll send you a link to reset your password.</p>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="forgot">
                        <div class="mb-3">
                            <label for="forgot-email" class="form-label small text-muted d-flex align-items-center gap-1">
                                <span class="material-icons" style="font-size:1rem;">email</span> Email
                            </label>
                            <input type="email" class="form-control bg-dark border-secondary text-light" id="forgot-email" name="email" placeholder="you@example.com" required autocomplete="email">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 py-2">
                            <span class="material-icons" style="font-size:1.25rem;">send</span>
                            Send reset link
                        </button>
                    </form>
                    <?php } ?>
                </div>
            </div>
            <div class="card bg-dark border-secondary mt-3">
                <div class="card-body py-3">
                    <p class="mb-0 small text-muted d-flex align-items-center justify-content-center gap-2">
                        <span class="material-icons" style="font-size:1rem;">login</span>
                        <a href="<?php echo url('login'); ?>" class="text-primary text-decoration-none">Back to sign in</a>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-3 mt-4 mt-md-0">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase mb-3 d-flex align-items-center gap-2">
                        <span class="material-icons">help_outline</span> Need help?
                    </h2>
                    <ul class="list-unstyled small mb-0">
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <span class="material-icons text-primary" style="font-size:1.1rem;">mail</span>
                            <span>Use the email you signed up with.</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <span class="material-icons text-primary" style="font-size:1.1rem;">schedule</span>
                            <span>Reset links expire after a short time.</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <span class="material-icons text-primary" style="font-size:1.1rem;">person_add</span>
                            <span><a href="<?php echo url('register'); ?>" class="text-decoration-none">Create an account</a> if you don’t have one.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
