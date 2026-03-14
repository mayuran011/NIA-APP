<?php
if (!defined('in_nia_app')) exit;
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    auth_logout();
    redirect(url());
}
if (is_logged()) {
    $goto = isset($_GET['redirect']) ? trim($_GET['redirect']) : '';
    if ($goto !== '' && strpos($goto, 'http') !== 0 && preg_match('#^[a-z0-9\/\-_.?=&%]+$#i', $goto)) {
        redirect(url($goto));
    }
    redirect(url('dashboard'));
}
$error = isset($_GET['error']) ? trim($_GET['error']) : '';
$page_title = 'Sign in';
$redirect = isset($_GET['redirect']) ? trim($_GET['redirect']) : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $pass = isset($_POST['password']) ? $_POST['password'] : '';
    $res = auth_login($email, $pass);
    if ($res['ok']) {
        $goto = isset($_POST['redirect']) ? trim($_POST['redirect']) : '';
        if ($goto !== '' && strpos($goto, 'http') !== 0 && preg_match('#^[a-z0-9\/\-_.?=&%]+$#i', $goto)) {
            redirect(url($goto));
        }
        redirect(url('dashboard'));
    }
    $error = $res['error'] === 'invalid_credentials' ? 'Invalid email or password.' : $res['error'];
}
$fb_id = defined('FB_APP_ID') ? FB_APP_ID : get_option('fb_app_id', '');
$go_id = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : get_option('google_client_id', '');
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card bg-dark border-secondary shadow">
                <div class="card-body p-4">
                    <h1 class="h4 mb-4 d-flex align-items-center gap-2">
                        <span class="material-icons text-primary">login</span>
                        Sign in
                    </h1>
                    <?php if ($error) { ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
                        <span class="material-icons">error_outline</span>
                        <?php echo _e($error); ?>
                    </div>
                    <?php } ?>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="login">
                        <?php if ($redirect !== '') { ?><input type="hidden" name="redirect" value="<?php echo _e($redirect); ?>"><?php } ?>
                        <div class="mb-3">
                            <label for="login-email" class="form-label small text-muted d-flex align-items-center gap-1">
                                <span class="material-icons" style="font-size:1rem;">email</span> Email
                            </label>
                            <input type="email" class="form-control bg-dark border-secondary text-light" id="login-email" name="email" placeholder="you@example.com" required autocomplete="email">
                        </div>
                        <div class="mb-3">
                            <label for="login-password" class="form-label small text-muted d-flex align-items-center gap-1">
                                <span class="material-icons" style="font-size:1rem;">lock</span> Password
                            </label>
                            <input type="password" class="form-control bg-dark border-secondary text-light" id="login-password" name="password" required autocomplete="current-password">
                        </div>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <a href="<?php echo url('forgot'); ?>" class="small text-muted text-decoration-none d-flex align-items-center gap-1">
                                <span class="material-icons" style="font-size:1rem;">help_outline</span> Forgot password?
                            </a>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 py-2">
                            <span class="material-icons" style="font-size:1.25rem;">login</span>
                            Sign in
                        </button>
                    </form>

                    <?php if ($fb_id !== '' || $go_id !== '') { ?>
                    <hr class="my-4 border-secondary">
                    <p class="small text-muted mb-2 d-flex align-items-center gap-1">
                        <span class="material-icons" style="font-size:1rem;">alternate_email</span> Or sign in with
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($fb_id !== '') { ?>
                        <a href="https://www.facebook.com/v18.0/dialog/oauth?client_id=<?php echo _e($fb_id); ?>&redirect_uri=<?php echo urlencode(url('callback.php?provider=facebook')); ?>&scope=email,public_profile" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            <span class="material-icons" style="font-size:1.1rem;">facebook</span> Facebook
                        </a>
                        <?php } ?>
                        <?php if ($go_id !== '') { ?>
                        <a href="https://accounts.google.com/o/oauth2/v2/auth?client_id=<?php echo _e($go_id); ?>&redirect_uri=<?php echo urlencode(url('callback.php?provider=google')); ?>&response_type=code&scope=email%20profile" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            <span class="material-icons" style="font-size:1.1rem;">language</span> Google
                        </a>
                        <?php } ?>
                    </div>
                    <?php } ?>
                </div>
            </div>

            <div class="card bg-dark border-secondary mt-3">
                <div class="card-body py-3">
                    <p class="mb-0 small text-muted d-flex align-items-center justify-content-center gap-2 flex-wrap">
                        <span class="material-icons" style="font-size:1rem;">person_add</span>
                        New here?
                        <a href="<?php echo url('register'); ?><?php echo $redirect !== '' ? '?redirect=' . rawurlencode($redirect) : ''; ?>" class="text-primary text-decoration-none fw-semibold">Create an account</a>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4 col-lg-3 mt-4 mt-md-0">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase mb-3 d-flex align-items-center gap-2">
                        <span class="material-icons">info</span> Why sign in?
                    </h2>
                    <ul class="list-unstyled small mb-0">
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <span class="material-icons text-primary" style="font-size:1.1rem;">videocam</span>
                            <span>Upload videos and music</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <span class="material-icons text-primary" style="font-size:1.1rem;">playlist_play</span>
                            <span>Save to Watch later &amp; playlists</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <span class="material-icons text-primary" style="font-size:1.1rem;">thumb_up</span>
                            <span>Like and track your history</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <span class="material-icons text-primary" style="font-size:1.1rem;">person</span>
                            <span>Your channel and profile</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <span class="material-icons text-primary" style="font-size:1.1rem;">notifications</span>
                            <span>Activity and notifications</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
