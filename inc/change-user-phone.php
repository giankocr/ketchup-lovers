<?php

function change_user_phone() {
    $user_id = get_current_user_id();
    $user_phone = get_user_meta($user_id, 'meta_xeerpa_phone', true);
    return $user_phone;
}

add_shortcode('change_user_phone', 'change_user_phone');