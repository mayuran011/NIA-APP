<?php
/** Redirect /add-video to share (video tab). */
if (!defined('in_nia_app')) exit;
if (!is_logged()) { redirect(url('login')); }
redirect(url('share?page=share-video'));
