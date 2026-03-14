<?php
if (!defined('in_nia_app')) exit;
if (is_logged()) {
    $goto = isset($_GET['redirect']) ? trim($_GET['redirect']) : '';
    if ($goto !== '' && strpos($goto, 'http') !== 0 && preg_match('#^[a-z0-9\/\-_.?=&%]+$#i', $goto)) {
        redirect(url($goto));
    }
    redirect(url('dashboard'));
}
$error = '';
$page_title = 'Create account';
$redirect = isset($_GET['redirect']) ? trim($_GET['redirect']) : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $pass = isset($_POST['password']) ? $_POST['password'] : '';
    $res = auth_register($username, $name, $email, $pass);
    if ($res['ok']) {
        $goto = isset($_POST['redirect']) ? trim($_POST['redirect']) : '';
        if ($goto !== '' && strpos($goto, 'http') !== 0 && preg_match('#^[a-z0-9\/\-_.?=&%]+$#i', $goto)) {
            redirect(url($goto));
        }
        redirect(url('dashboard'));
    }
    $errMap = ['username_invalid' => 'Username too short or invalid.', 'username_taken' => 'Username taken.', 'email_taken' => 'Email already registered.', 'password_short' => 'Password must be at least 6 characters.'];
    $error = $errMap[$res['error']] ?? $res['error'];
}
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card bg-dark border-secondary shadow">
                <div class="card-body p-4">
                    <h1 class="h4 mb-4 d-flex align-items-center gap-2">
                        <span class="material-icons text-primary">person_add</span>
                        Create account
                    </h1>
                    <?php if ($error) { ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
                        <span class="material-icons">error_outline</span>
                        <?php echo _e($error); ?>
                    </div>
                    <?php } ?>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="register">
                        <?php if ($redirect !== '') { ?><input type="hidden" name="redirect" value="<?php echo _e($redirect); ?>"><?php } ?>
                        <div class="mb-3">
                            <label for="reg-username" class="form-label small text-muted d-flex align-items-center gap-1">
                                <span class="material-icons" style="font-size:1rem;">badge</span> Username
                            </label>
                            <input type="text" class="form-control bg-dark border-secondary text-light" id="reg-username" name="username" placeholder="johndoe" required minlength="2" autocomplete="username" pattern="[a-zA-Z0-9_\-]+" title="Letters, numbers, underscore, hyphen only">
                        </div>
                        <div class="mb-3">
                            <label for="reg-name" class="form-label small text-muted d-flex align-items-center gap-1">
                                <span class="material-icons" style="font-size:1rem;">person</span> Display name
                            </label>
                            <input type="text" class="form-control bg-dark border-secondary text-light" id="reg-name" name="name" placeholder="John Doe" required autocomplete="name">
                        </div>
                        <div class="mb-3">
                            <label for="reg-email" class="form-label small text-muted d-flex align-items-center gap-1">
                                <span class="material-icons" style="font-size:1rem;">email</span> Email
                            </label>
                            <input type="email" class="form-control bg-dark border-secondary text-light" id="reg-email" name="email" placeholder="you@example.com" required autocomplete="email">
                        </div>
                        <div class="mb-3">
                            <label for="reg-password" class="form-label small text-muted d-flex align-items-center gap-1">
                                <span class="material-icons" style="font-size:1rem;">lock</span> Password
                            </label>
                            <input type="password" class="form-control bg-dark border-secondary text-light" id="reg-password" name="password" required minlength="6" autocomplete="new-password" placeholder="At least 6 characters">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 py-2">
                            <span class="material-icons" style="font-size:1.25rem;">person_add</span>
                            Create account
                        </button>
                    </form>
                </div>
            </div>
            <div class="card bg-dark border-secondary mt-3">
                <div class="card-body py-3">
                    <p class="mb-0 small text-muted d-flex align-items-center justify-content-center gap-2 flex-wrap">
                        <span class="material-icons" style="font-size:1rem;">login</span>
                        Already have an account?
                        <a href="<?php echo url('login'); ?><?php echo $redirect !== '' ? '?redirect=' . rawurlencode($redirect) : ''; ?>" class="text-primary text-decoration-none fw-semibold">Sign in</a>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-3 mt-4 mt-md-0">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase mb-3 d-flex align-items-center gap-2">
                        <span class="material-icons">verified_user</span> You get
                    </h2>
                    <ul class="list-unstyled small mb-0">
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <span class="material-icons text-primary" style="font-size:1.1rem;">videocam</span>
                            <span>Upload videos &amp; music</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <span class="material-icons text-primary" style="font-size:1.1rem;">playlist_play</span>
                            <span>Watch later &amp; playlists</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <span class="material-icons text-primary" style="font-size:1.1rem;">thumb_up</span>
                            <span>Likes &amp; history</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <span class="material-icons text-primary" style="font-size:1.1rem;">person</span>
                            <span>Your channel &amp; profile</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <span class="material-icons text-primary" style="font-size:1.1rem;">notifications</span>
                            <span>Activity &amp; notifications</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
