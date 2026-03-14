<?php
/** Redirect /add-image to share (picture tab). */
if (!defined('in_nia_app')) exit;
if (!is_logged()) { redirect(url('login')); }
redirect(url('share?page=share-picture'));
