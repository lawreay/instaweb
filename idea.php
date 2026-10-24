<?php
/**
 * Portfolio Admin Dashboard - Fully Customizable
 */

require_once '../config.php';
require_login();

$user_id = get_user_id();
$settings = get_user_settings($user_id);
$projects = get_user_projects($user_id);
$goals = get_goals($user_id);
$interests = get_interests($user_id);
$experience = get_experience($user_id);
$skills = get_skills($user_id);
$education = get_education($user_id);
$news_items = get_news($user_id);
$popups = get_popups($user_id);
$bookings = get_engagement_rows('portfolio_bookings', $user_id);
$requests = get_engagement_rows('portfolio_requests', $user_id);
$comments = get_engagement_rows('portfolio_comments', $user_id);
$visits = get_visits($user_id, 120);
$section_backgrounds = get_section_backgrounds($user_id);
$background_sections = [
    'hero' => 'Hero',
    'about' => 'About',
    'goals' => 'Goals',
    'interests' => 'Interests',
    'challenges' => 'Challenges',
    'experience' => 'Experience',
    'education' => 'Education',
    'projects' => 'Projects',
    'skills' => 'Skills',
    'news' => 'News',
    'partnerships' => 'Partnerships',
    'cv' => 'CV',
    'contact' => 'Contact',
    'engage' => 'Bookings / Requests / Comments',
];
$system_icons = [
    'target', 'star', 'zap', 'rocket', 'briefcase', 'graduation-cap', 'award', 'code',
    'book-open', 'globe', 'users', 'heart', 'lightbulb', 'cpu', 'database', 'server',
    'monitor', 'smartphone', 'palette', 'music', 'mic', 'camera', 'image', 'newspaper',
    'megaphone', 'message-circle', 'mail', 'map-pin', 'calendar', 'check-circle',
    'shield', 'settings', 'wrench', 'layers', 'network', 'cloud', 'bot', 'sparkles',
];
$uploaded_images = list_uploaded_images($user_id);

$tab = $_GET['tab'] ?? 'overview';
$success_message = '';
$error_message = '';

function dashboard_file_input($field, $key = null) {
    if (!isset($_FILES[$field])) return null;

    if ($key === null) {
        return $_FILES[$field];
    }

    if (!isset($_FILES[$field]['name'][$key])) return null;

    return [
        'name' => $_FILES[$field]['name'][$key],
        'type' => $_FILES[$field]['type'][$key] ?? '',
        'tmp_name' => $_FILES[$field]['tmp_name'][$key] ?? '',
        'error' => $_FILES[$field]['error'][$key] ?? UPLOAD_ERR_NO_FILE,
        'size' => $_FILES[$field]['size'][$key] ?? 0,
    ];
}

function dashboard_has_file($file) {
    return is_array($file) && isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE;
}

function dashboard_upload_image($file, $prefix, &$error_message, $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico']) {
    if (!dashboard_has_file($file)) return '';

    $upload = store_uploaded_file($file, $prefix, $allowed);
    if ($upload['success']) {
        return $upload['url'];
    }

    $error_message = $upload['error'];
    return '';
}

function dashboard_resolve_icon($field, $default, $prefix, &$error_message) {
    $icon = clean_icon_value($_POST[$field] ?? $default, $default);
    $existing = clean_optional_url($_POST[$field . '_existing'] ?? '');
    if ($existing !== '') {
        $icon = $existing;
    }

    $uploaded = dashboard_upload_image(dashboard_file_input($field . '_file'), $prefix, $error_message);
    if ($uploaded !== '') {
        $icon = $uploaded;
    }

    return $icon;
}

function dashboard_tech_stack_text($tech_stack) {
    if (!$tech_stack) return '';
    $decoded = json_decode($tech_stack, true);
    if (is_array($decoded)) return implode(', ', $decoded);
    return (string)$tech_stack;
}

function render_uploaded_image_select($name, $current_value, $uploaded_images, $label = 'Choose Uploaded Image', $select_current = true) {
    $current_value = (string)$current_value;
    $matched = false;
    echo '<label class="block text-sm font-medium mb-2">' . htmlspecialchars($label) . '</label>';
    echo '<div class="choice-grid">';
    echo '<label class="choice-option"><input class="choice-input" type="radio" name="' . htmlspecialchars($name) . '" value="" ' . ((!$select_current || $current_value === '') ? 'checked' : '') . '><span class="choice-card choice-empty"><span>No image</span></span></label>';
    foreach ($uploaded_images as $image) {
        $selected = $select_current && $current_value !== '' && $current_value === $image['url'];
        if ($selected) $matched = true;
        echo '<label class="choice-option"><input class="choice-input" type="radio" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($image['url']) . '" ' . ($selected ? 'checked' : '') . '>';
        echo '<span class="choice-card"><img src="' . htmlspecialchars($image['url']) . '" alt=""><span>' . htmlspecialchars($image['name']) . '</span></span></label>';
    }
    if ($select_current && $current_value !== '' && !$matched && preg_match('/^(https?:\/\/|\/|uploads\/)/i', $current_value)) {
        echo '<label class="choice-option"><input class="choice-input" type="radio" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($current_value) . '" checked>';
        echo '<span class="choice-card"><img src="' . htmlspecialchars($current_value) . '" alt=""><span>Current image</span></span></label>';
    }
    echo '</div>';
}

function render_icon_controls($field_name, $current_value, $default, $uploaded_images) {
    global $system_icons;

    $current_value = (string)($current_value ?: $default);
    $is_image_value = preg_match('/^(https?:\/\/|\/|uploads\/)/i', $current_value);
    $icon_value = $is_image_value ? $default : $current_value;
    echo '<div class="space-y-4">';
    echo '<div><label class="block text-sm font-medium mb-2">System Icon</label>';
    echo '<div class="choice-grid icon-choice-grid">';
    foreach ($system_icons as $icon) {
        $selected = !$is_image_value && $icon_value === $icon;
        echo '<label class="choice-option"><input class="choice-input" type="radio" name="' . htmlspecialchars($field_name) . '" value="' . htmlspecialchars($icon) . '" ' . ($selected ? 'checked' : '') . '>';
        echo '<span class="choice-card icon-choice-card"><i data-lucide="' . htmlspecialchars($icon) . '"></i><span>' . htmlspecialchars($icon) . '</span></span></label>';
    }
    if (!in_array($icon_value, $system_icons, true)) {
        echo '<label class="choice-option"><input class="choice-input" type="radio" name="' . htmlspecialchars($field_name) . '" value="' . htmlspecialchars($icon_value) . '" checked>';
        echo '<span class="choice-card icon-choice-card"><i data-lucide="' . htmlspecialchars($icon_value) . '"></i><span>' . htmlspecialchars($icon_value) . '</span></span></label>';
    }
    echo '</div></div>';
    echo '<div>';
    render_uploaded_image_select($field_name . '_existing', $current_value, $uploaded_images, 'Uploaded Icon/Image');
    echo '</div>';
    echo '<div><label class="block text-sm font-medium mb-2">Upload New Icon</label>';
    echo '<input type="file" name="' . htmlspecialchars($field_name) . '_file" accept=".svg,.ico,image/*" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white"></div>';
    echo '</div>';
}

// Handle form submissions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // ===== HERO SECTION =====
        if ($action === 'save_hero') {
            $hero_settings = [
                'hero_title' => escape_input($_POST['hero_title'] ?? ''),
                'hero_subtitle' => escape_input($_POST['hero_subtitle'] ?? ''),
                'hero_cta' => escape_input($_POST['hero_cta'] ?? ''),
            ];
            
            if (update_user_settings($user_id, $hero_settings)) {
                $success_message = 'Hero section updated';
                $settings = get_user_settings($user_id);
            } else {
                $error_message = 'Error updating hero section';
            }
        }
        
        // ===== ABOUT SECTION =====
        if ($action === 'save_about') {
            $about_settings = [
                'about_title' => escape_input($_POST['about_title'] ?? 'About Me'),
                'about_text' => escape_input($_POST['about_text'] ?? ''),
                'full_name' => escape_input($_POST['full_name'] ?? ''),
                'home_origin' => escape_input($_POST['home_origin'] ?? ''),
                'nationality' => escape_input($_POST['nationality'] ?? ''),
                'religion' => escape_input($_POST['religion'] ?? ''),
                'status' => escape_input($_POST['status'] ?? ''),
                'location' => escape_input($_POST['location'] ?? ''),
                'vision' => escape_input($_POST['vision'] ?? ''),
            ];
            
            if (update_user_settings($user_id, $about_settings)) {
                $success_message = 'About section updated';
                $settings = get_user_settings($user_id);
            } else {
                $error_message = 'Error updating about section';
            }
        }
        
        // ===== GOALS =====
        if ($action === 'add_goal') {
            $goal_icon = dashboard_resolve_icon('goal_icon', 'target', 'goal_icon_' . $user_id, $error_message);
            if (!$error_message && add_goal($user_id, $_POST['goal_title'], $_POST['goal_description'], $goal_icon)) {
                $success_message = 'Goal added successfully';
                $goals = get_goals($user_id);
            } else {
                $error_message = $error_message ?: 'Error adding goal';
            }
        }
        
        if ($action === 'update_goal') {
            $goal_icon = dashboard_resolve_icon('goal_icon', 'target', 'goal_icon_' . $user_id . '_' . (int)$_POST['goal_id'], $error_message);
            if (!$error_message && update_goal($_POST['goal_id'], $user_id, $_POST['goal_title'], $_POST['goal_description'], $goal_icon)) {
                $success_message = 'Goal updated';
                $goals = get_goals($user_id);
            } else {
                $error_message = $error_message ?: 'Error updating goal';
            }
        }
        
        if ($action === 'delete_goal') {
            if (delete_goal($_POST['goal_id'], $user_id)) {
                $success_message = 'Goal deleted';
                $goals = get_goals($user_id);
            } else {
                $error_message = 'Error deleting goal';
            }
        }
        
        // ===== INTERESTS =====
        if ($action === 'add_interest') {
            $interest_icon = dashboard_resolve_icon('interest_icon', 'star', 'interest_icon_' . $user_id, $error_message);
            if (!$error_message && add_interest($user_id, $_POST['interest_title'], $_POST['interest_description'], $interest_icon)) {
                $success_message = 'Interest added successfully';
                $interests = get_interests($user_id);
            } else {
                $error_message = $error_message ?: 'Error adding interest';
            }
        }
        
        if ($action === 'update_interest') {
            $interest_icon = dashboard_resolve_icon('interest_icon', 'star', 'interest_icon_' . $user_id . '_' . (int)$_POST['interest_id'], $error_message);
            if (!$error_message && update_interest($_POST['interest_id'], $user_id, $_POST['interest_title'], $_POST['interest_description'], $interest_icon)) {
                $success_message = 'Interest updated';
                $interests = get_interests($user_id);
            } else {
                $error_message = $error_message ?: 'Error updating interest';
            }
        }
        
        if ($action === 'delete_interest') {
            if (delete_interest($_POST['interest_id'], $user_id)) {
                $success_message = 'Interest deleted';
                $interests = get_interests($user_id);
            } else {
                $error_message = 'Error deleting interest';
            }
        }
        
        // ===== EXPERIENCE =====
        if ($action === 'add_experience') {
            $is_current = isset($_POST['is_current']) ? 1 : 0;
            $exp_start = !empty($_POST['exp_start']) ? $_POST['exp_start'] : null;
            $exp_end = !empty($_POST['exp_end']) ? $_POST['exp_end'] : null;
            $exp_color = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['exp_color'] ?? '') ? $_POST['exp_color'] : '#6366f1';
            if (add_experience($user_id, $_POST['exp_title'], $_POST['exp_company'], $_POST['exp_description'], $exp_start, $exp_end, $is_current, $exp_color)) {
                $success_message = 'Experience added successfully';
                $experience = get_experience($user_id);
            } else {
                $error_message = 'Error adding experience';
            }
        }
        
        if ($action === 'update_experience') {
            $is_current = isset($_POST['is_current']) ? 1 : 0;
            $exp_start = !empty($_POST['exp_start']) ? $_POST['exp_start'] : null;
            $exp_end = !empty($_POST['exp_end']) ? $_POST['exp_end'] : null;
            $exp_color = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['exp_color'] ?? '') ? $_POST['exp_color'] : '#6366f1';
            if (update_experience($_POST['exp_id'], $user_id, $_POST['exp_title'], $_POST['exp_company'], $_POST['exp_description'], $exp_start, $exp_end, $is_current, $exp_color)) {
                $success_message = 'Experience updated';
                $experience = get_experience($user_id);
            } else {
                $error_message = 'Error updating experience';
            }
        }
        
        if ($action === 'delete_experience') {
            if (delete_experience($_POST['exp_id'], $user_id)) {
                $success_message = 'Experience deleted';
                $experience = get_experience($user_id);
            } else {
                $error_message = 'Error deleting experience';
            }
        }
        
        // ===== SKILLS =====
        if ($action === 'add_skill') {
            if (add_skill($user_id, $_POST['skill_category'], $_POST['skill_name'], $_POST['skill_proficiency'] ?? 'intermediate', $_POST['skill_color'] ?? '#6366f1')) {
                $success_message = 'Skill added successfully';
                $skills = get_skills($user_id);
            } else {
                $error_message = 'Error adding skill';
            }
        }
        
        if ($action === 'update_skill') {
            if (update_skill($_POST['skill_id'], $user_id, $_POST['skill_category'], $_POST['skill_name'], $_POST['skill_proficiency'] ?? 'intermediate', $_POST['skill_color'] ?? '#6366f1')) {
                $success_message = 'Skill updated';
                $skills = get_skills($user_id);
            } else {
                $error_message = 'Error updating skill';
            }
        }
        
        if ($action === 'delete_skill') {
            if (delete_skill($_POST['skill_id'], $user_id)) {
                $success_message = 'Skill deleted';
                $skills = get_skills($user_id);
            } else {
                $error_message = 'Error deleting skill';
            }
        }

        // ===== PROJECTS =====
        if ($action === 'add_project') {
            $project_image = clean_optional_url($_POST['project_image'] ?? '');
            $existing_project_image = clean_optional_url($_POST['project_existing_image'] ?? '');
            if ($existing_project_image !== '') {
                $project_image = $existing_project_image;
            }
            if (isset($_FILES['project_image_file']) && $_FILES['project_image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = store_uploaded_file($_FILES['project_image_file'], 'project_' . $user_id, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                if ($upload['success']) {
                    $project_image = $upload['url'];
                } else {
                    $error_message = $upload['error'];
                }
            }
            $project_icon = dashboard_resolve_icon('project_icon', 'zap', 'project_icon_' . $user_id, $error_message);

            if (!$error_message) {
                $project_data = [
                    'title' => escape_input($_POST['project_title'] ?? ''),
                    'description' => escape_input($_POST['project_description'] ?? ''),
                    'short_description' => escape_input($_POST['project_short'] ?? ''),
                    'category' => escape_input($_POST['project_category'] ?? ''),
                    'icon_type' => $project_icon,
                    'icon_color' => clean_hex_color($_POST['project_color'] ?? '#6366f1'),
                    'background_color' => '#18181b',
                    'tech_stack' => $_POST['project_tech'] ?? '',
                    'demo_url' => clean_optional_url($_POST['project_demo_url'] ?? ''),
                    'github_url' => clean_optional_url($_POST['project_github_url'] ?? ''),
                    'image_path' => $project_image,
                    'order_position' => count($projects) + 1,
                    'is_featured' => 1,
                ];

                if (add_project($user_id, $project_data)) {
                    $success_message = 'Project added successfully';
                    $projects = get_user_projects($user_id);
                } else {
                    $error_message = 'Error adding project';
                }
            }
        }

        if ($action === 'update_project') {
            $project_id = (int)($_POST['project_id'] ?? 0);
            $project_image = clean_optional_url($_POST['project_image'] ?? '');
            $existing_project_image = clean_optional_url($_POST['project_existing_image'] ?? '');
            if ($existing_project_image !== '') {
                $project_image = $existing_project_image;
            }
            if (isset($_FILES['project_image_file']) && $_FILES['project_image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = store_uploaded_file($_FILES['project_image_file'], 'project_' . $user_id . '_' . $project_id, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                if ($upload['success']) {
                    $project_image = $upload['url'];
                } else {
                    $error_message = $upload['error'];
                }
            }
            $project_icon = dashboard_resolve_icon('project_icon', 'zap', 'project_icon_' . $user_id . '_' . $project_id, $error_message);

            if (!$error_message) {
                $project_data = [
                    'title' => escape_input($_POST['project_title'] ?? ''),
                    'description' => escape_input($_POST['project_description'] ?? ''),
                    'short_description' => escape_input($_POST['project_short'] ?? ''),
                    'category' => escape_input($_POST['project_category'] ?? ''),
                    'icon_type' => $project_icon,
                    'icon_color' => clean_hex_color($_POST['project_color'] ?? '#6366f1'),
                    'background_color' => '#18181b',
                    'tech_stack' => $_POST['project_tech'] ?? '',
                    'demo_url' => clean_optional_url($_POST['project_demo_url'] ?? ''),
                    'github_url' => clean_optional_url($_POST['project_github_url'] ?? ''),
                    'image_path' => $project_image,
                    'order_position' => max(0, (int)($_POST['project_order'] ?? 0)),
                    'is_featured' => isset($_POST['project_featured']) ? 1 : 0,
                ];

                if (update_project($project_id, $user_id, $project_data)) {
                    $success_message = 'Project updated';
                    $projects = get_user_projects($user_id);
                } else {
                    $error_message = 'Error updating project';
                }
            }
        }

        if ($action === 'delete_project') {
            if (delete_project($_POST['project_id'], $user_id)) {
                $success_message = 'Project deleted';
                $projects = get_user_projects($user_id);
            } else {
                $error_message = 'Error deleting project';
            }
        }

        // ===== EDUCATION =====
        if ($action === 'add_education') {
            $is_current = isset($_POST['edu_current']) ? 1 : 0;
            $edu_start = !empty($_POST['edu_start']) ? $_POST['edu_start'] : null;
            $edu_end = !empty($_POST['edu_end']) ? $_POST['edu_end'] : null;
            $edu_color = clean_hex_color($_POST['edu_color'] ?? '#6366f1');
            if (add_education($user_id, $_POST['edu_title'], $_POST['edu_institution'], $_POST['edu_description'], $edu_start, $edu_end, $is_current, $edu_color)) {
                $success_message = 'Education added successfully';
                $education = get_education($user_id);
            } else {
                $error_message = 'Error adding education';
            }
        }

        if ($action === 'update_education') {
            $is_current = isset($_POST['edu_current']) ? 1 : 0;
            $edu_start = !empty($_POST['edu_start']) ? $_POST['edu_start'] : null;
            $edu_end = !empty($_POST['edu_end']) ? $_POST['edu_end'] : null;
            $edu_color = clean_hex_color($_POST['edu_color'] ?? '#6366f1');
            if (update_education($_POST['education_id'], $user_id, $_POST['edu_title'], $_POST['edu_institution'], $_POST['edu_description'], $edu_start, $edu_end, $is_current, $edu_color)) {
                $success_message = 'Education updated';
                $education = get_education($user_id);
            } else {
                $error_message = 'Error updating education';
            }
        }

        if ($action === 'delete_education') {
            if (delete_education($_POST['education_id'], $user_id)) {
                $success_message = 'Education deleted';
                $education = get_education($user_id);
            } else {
                $error_message = 'Error deleting education';
            }
        }

        // ===== MEDIA, FAVICON, AND SECTION BACKGROUNDS =====
        if ($action === 'save_brand_assets') {
            $asset_settings = [
                'profile_image' => clean_optional_url($_POST['profile_image'] ?? ''),
                'favicon_path' => clean_optional_url($_POST['favicon_path'] ?? ''),
            ];
            $profile_existing = clean_optional_url($_POST['profile_image_existing'] ?? '');
            $favicon_existing = clean_optional_url($_POST['favicon_existing'] ?? '');
            if ($profile_existing !== '') {
                $asset_settings['profile_image'] = $profile_existing;
            }
            if ($favicon_existing !== '') {
                $asset_settings['favicon_path'] = $favicon_existing;
            }

            if (isset($_FILES['profile_image_file']) && $_FILES['profile_image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = store_uploaded_file($_FILES['profile_image_file'], 'profile_' . $user_id, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                if ($upload['success']) {
                    $asset_settings['profile_image'] = $upload['url'];
                } else {
                    $error_message = $upload['error'];
                }
            }

            if (!$error_message && isset($_FILES['favicon_file']) && $_FILES['favicon_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = store_uploaded_file($_FILES['favicon_file'], 'favicon_' . $user_id, ['ico', 'png', 'svg', 'jpg', 'jpeg', 'webp']);
                if ($upload['success']) {
                    $asset_settings['favicon_path'] = $upload['url'];
                } else {
                    $error_message = $upload['error'];
                }
            }

            if (!$error_message && update_user_settings($user_id, $asset_settings)) {
                $success_message = 'Profile image and favicon updated';
                $settings = get_user_settings($user_id);
            } elseif (!$error_message) {
                $error_message = 'Error updating media assets';
            }
        }

        if ($action === 'save_section_backgrounds') {
            foreach ($background_sections as $key => $label) {
                $image = clean_optional_url($_POST['section_background'][$key] ?? '');
                $existing = clean_optional_url($_POST['section_background_existing'][$key] ?? '');
                if ($existing !== '') {
                    $image = $existing;
                }
                $uploaded = dashboard_upload_image(dashboard_file_input('section_background_file', $key), 'section_' . $key . '_' . $user_id, $error_message, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                if ($uploaded !== '') {
                    $image = $uploaded;
                }
                $opacity = isset($_POST['section_overlay'][$key]) ? (float)$_POST['section_overlay'][$key] : 0.80;
                $opacity = max(0, min(0.95, $opacity));
                if (!$error_message) {
                    upsert_section_background($user_id, $key, $image, $opacity);
                }
            }
            if (!$error_message) {
                $success_message = 'Section backgrounds updated';
                $section_backgrounds = get_section_backgrounds($user_id);
            }
        }

        // ===== NEWS =====
        if ($action === 'save_news_settings') {
            if (update_user_settings($user_id, ['enable_news' => isset($_POST['enable_news']) ? 1 : 0])) {
                $success_message = 'News setting updated';
                $settings = get_user_settings($user_id);
            } else {
                $error_message = 'Error updating news setting';
            }
        }

        if ($action === 'add_news') {
            $title = escape_input($_POST['news_title'] ?? '');
            $body = escape_input($_POST['news_body'] ?? '');
            $link = clean_optional_url($_POST['news_link'] ?? '');
            $published = isset($_POST['news_published']) ? 1 : 0;
            $position = count($news_items) + 1;
            $stmt = $mysqli->prepare("INSERT INTO portfolio_news (user_id, title, body, link_url, is_published, order_position) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssii", $user_id, $title, $body, $link, $published, $position);
            if ($stmt->execute()) {
                $success_message = 'News item added';
                $news_items = get_news($user_id);
            } else {
                $error_message = 'Error adding news';
            }
        }

        if ($action === 'update_news') {
            $title = escape_input($_POST['news_title'] ?? '');
            $body = escape_input($_POST['news_body'] ?? '');
            $link = clean_optional_url($_POST['news_link'] ?? '');
            $published = isset($_POST['news_published']) ? 1 : 0;
            if (update_news($_POST['news_id'], $user_id, $title, $body, $link, $published)) {
                $success_message = 'News item updated';
                $news_items = get_news($user_id);
            } else {
                $error_message = 'Error updating news';
            }
        }

        if ($action === 'delete_news') {
            $stmt = $mysqli->prepare("DELETE FROM portfolio_news WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $_POST['news_id'], $user_id);
            if ($stmt->execute()) {
                $success_message = 'News item deleted';
                $news_items = get_news($user_id);
            } else {
                $error_message = 'Error deleting news';
            }
        }

        if ($action === 'toggle_news') {
            $stmt = $mysqli->prepare("UPDATE portfolio_news SET is_published = 1 - is_published WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $_POST['news_id'], $user_id);
            if ($stmt->execute()) {
                $success_message = 'News publish state updated';
                $news_items = get_news($user_id);
            } else {
                $error_message = 'Error updating news';
            }
        }

        // ===== ENGAGEMENT REVIEW =====
        if ($action === 'review_engagement' || $action === 'delete_engagement') {
            $engagement_tables = [
                'booking' => 'portfolio_bookings',
                'request' => 'portfolio_requests',
                'comment' => 'portfolio_comments',
            ];
            $kind = $_POST['engagement_kind'] ?? '';
            $table = $engagement_tables[$kind] ?? '';
            $id = (int)($_POST['engagement_id'] ?? 0);

            if ($table && $id) {
                if ($action === 'review_engagement') {
                    $stmt = $mysqli->prepare("UPDATE `$table` SET is_reviewed = 1 WHERE id = ? AND user_id = ?");
                    $stmt->bind_param("ii", $id, $user_id);
                    $success_message = $stmt->execute() ? 'Item marked reviewed' : '';
                } else {
                    $stmt = $mysqli->prepare("DELETE FROM `$table` WHERE id = ? AND user_id = ?");
                    $stmt->bind_param("ii", $id, $user_id);
                    $success_message = $stmt->execute() ? 'Item deleted' : '';
                }
                $bookings = get_engagement_rows('portfolio_bookings', $user_id);
                $requests = get_engagement_rows('portfolio_requests', $user_id);
                $comments = get_engagement_rows('portfolio_comments', $user_id);
            } else {
                $error_message = 'Invalid engagement item';
            }
        }

        // ===== POPUP ADS =====
        if ($action === 'add_popup') {
            $title = escape_input($_POST['popup_title'] ?? '');
            $body = escape_input($_POST['popup_body'] ?? '');
            $link = clean_optional_url($_POST['popup_link'] ?? '');
            $button = escape_input($_POST['popup_button'] ?? 'Learn More');
            $image = clean_optional_url($_POST['popup_image'] ?? '');
            $existing_popup_image = clean_optional_url($_POST['popup_existing_image'] ?? '');
            if ($existing_popup_image !== '') {
                $image = $existing_popup_image;
            }
            $uploaded_popup_image = dashboard_upload_image(dashboard_file_input('popup_image_file'), 'popup_' . $user_id, $error_message, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
            if ($uploaded_popup_image !== '') {
                $image = $uploaded_popup_image;
            }
            $active = isset($_POST['popup_active']) ? 1 : 0;
            $trigger_timer = isset($_POST['popup_trigger_timer']) ? 1 : 0;
            $timer_delay = (int)($_POST['popup_timer_delay'] ?? 900);
            $trigger_scroll = isset($_POST['popup_trigger_scroll']) ? 1 : 0;
            $scroll_percentage = max(0, min(100, (int)($_POST['popup_scroll_percentage'] ?? 50)));
            $trigger_esc = isset($_POST['popup_trigger_esc']) ? 1 : 0;
            $trigger_back_button = isset($_POST['popup_trigger_back_button']) ? 1 : 0;
            
            if (!$error_message && add_popup($user_id, $title, $body, $link, $button, $image, $active, $trigger_timer, $timer_delay, $trigger_scroll, $scroll_percentage, $trigger_esc, $trigger_back_button)) {
                $success_message = 'Popup added';
                $popups = get_popups($user_id);
            } else {
                $error_message = $error_message ?: 'Error adding popup';
            }
        }

        if ($action === 'update_popup') {
            $popup_id = (int)($_POST['popup_id'] ?? 0);
            $title = escape_input($_POST['popup_title'] ?? '');
            $body = escape_input($_POST['popup_body'] ?? '');
            $link = clean_optional_url($_POST['popup_link'] ?? '');
            $button = escape_input($_POST['popup_button'] ?? 'Learn More');
            $image = clean_optional_url($_POST['popup_image'] ?? '');
            $existing_popup_image = clean_optional_url($_POST['popup_existing_image'] ?? '');
            if ($existing_popup_image !== '') {
                $image = $existing_popup_image;
            }
            $uploaded_popup_image = dashboard_upload_image(dashboard_file_input('popup_image_file'), 'popup_' . $user_id . '_' . $popup_id, $error_message, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
            if ($uploaded_popup_image !== '') {
                $image = $uploaded_popup_image;
            }
            $active = isset($_POST['popup_active']) ? 1 : 0;
            $trigger_timer = isset($_POST['popup_trigger_timer']) ? 1 : 0;
            $timer_delay = (int)($_POST['popup_timer_delay'] ?? 900);
            $trigger_scroll = isset($_POST['popup_trigger_scroll']) ? 1 : 0;
            $scroll_percentage = max(0, min(100, (int)($_POST['popup_scroll_percentage'] ?? 50)));
            $trigger_esc = isset($_POST['popup_trigger_esc']) ? 1 : 0;
            $trigger_back_button = isset($_POST['popup_trigger_back_button']) ? 1 : 0;

            if (!$error_message && update_popup($popup_id, $user_id, $title, $body, $link, $button, $image, $active, $trigger_timer, $timer_delay, $trigger_scroll, $scroll_percentage, $trigger_esc, $trigger_back_button)) {
                $success_message = 'Popup updated';
                $popups = get_popups($user_id);
            } else {
                $error_message = $error_message ?: 'Error updating popup';
            }
        }

        if ($action === 'delete_popup') {
            $stmt = $mysqli->prepare("DELETE FROM portfolio_popups WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $_POST['popup_id'], $user_id);
            if ($stmt->execute()) {
                $success_message = 'Popup deleted';
                $popups = get_popups($user_id);
            } else {
                $error_message = 'Error deleting popup';
            }
        }

        if ($action === 'toggle_popup') {
            $stmt = $mysqli->prepare("UPDATE portfolio_popups SET is_active = 1 - is_active WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $_POST['popup_id'], $user_id);
            if ($stmt->execute()) {
                $success_message = 'Popup active state updated';
                $popups = get_popups($user_id);
            } else {
                $error_message = 'Error updating popup';
            }
        }

        // ===== VISITS =====
        if ($action === 'clear_visits') {
            if (clear_visits($user_id)) {
                $success_message = 'Visit history cleared';
                $visits = [];
            } else {
                $error_message = 'Error clearing visits';
            }
        }
        
        // ===== EXISTING HANDLERS =====
        
        // Save Theme Settings
        if ($action === 'save_theme') {
            $theme_settings = [
                'background_color' => escape_input($_POST['background_color'] ?? ''),
                'surface_color' => escape_input($_POST['surface_color'] ?? ''),
                'text_color' => escape_input($_POST['text_color'] ?? ''),
                'primary_action_color' => escape_input($_POST['primary_action_color'] ?? ''),
                'secondary_action_color' => escape_input($_POST['secondary_action_color'] ?? ''),
                'accent_color' => escape_input($_POST['accent_color'] ?? ''),
            ];
            
            if (update_user_settings($user_id, $theme_settings)) {
                $success_message = 'Theme colors updated';
                $settings = get_user_settings($user_id);
            } else {
                $error_message = 'Error updating theme';
            }
        }
        
        // Save Typography Settings
        if ($action === 'save_typography') {
            $allowed_fonts = ['Syne', 'Inter', 'Poppins', 'Roboto', 'Playfair Display', 'DM Sans'];
            $font_family = in_array($_POST['font_family'] ?? 'Syne', $allowed_fonts, true) ? $_POST['font_family'] : 'Syne';
            $font_size = max(12, min(24, (int)($_POST['font_size'] ?? 16)));
            $typo_settings = [
                'font_family' => escape_input($font_family),
                'font_size' => $font_size,
            ];
            
            if (update_user_settings($user_id, $typo_settings)) {
                $success_message = 'Typography settings updated';
                $settings = get_user_settings($user_id);
            } else {
                $error_message = 'Error updating typography';
            }
        }
        
        // Save Animation Settings
        if ($action === 'save_animations') {
            $allowed_cursors = ['default', 'glow', 'trail', 'bounce'];
            $cursor_animation = in_array($_POST['cursor_animation'] ?? 'default', $allowed_cursors, true) ? $_POST['cursor_animation'] : 'default';
            $anim_settings = [
                'enable_animations' => isset($_POST['enable_animations']) ? 1 : 0,
                'cursor_animation' => escape_input($cursor_animation),
            ];
            
            if (update_user_settings($user_id, $anim_settings)) {
                $success_message = 'Animation settings updated';
                $settings = get_user_settings($user_id);
            } else {
                $error_message = 'Error updating animations';
            }
        }
        
        // Save Profile Info
        if ($action === 'save_profile') {
            $profile_settings = [
                'bio' => escape_input($_POST['bio'] ?? ''),
                'location' => escape_input($_POST['location'] ?? ''),
                'email' => escape_input($_POST['email'] ?? ''),
                'whatsapp' => escape_input($_POST['whatsapp'] ?? ''),
            ];
            
            if (update_user_settings($user_id, $profile_settings)) {
                $success_message = 'Profile updated';
                $settings = get_user_settings($user_id);
            } else {
                $error_message = 'Error updating profile';
            }
        }
        
        // Manage CV
        if ($action === 'save_cv' || $action === 'upload_cv') {
            $cv_path = clean_optional_url($_POST['cv_path'] ?? ($settings['cv_path'] ?? ''));
            $cv_file = dashboard_file_input('cv_file');

            if (dashboard_has_file($cv_file)) {
                $upload = store_uploaded_file($cv_file, 'cv_' . $user_id, ['pdf', 'doc', 'docx'], 10485760);
                if ($upload['success']) {
                    $cv_path = $upload['url'];
                } else {
                    $error_message = $upload['error'];
                }
            }

            if (!$error_message && update_user_settings($user_id, [
                'cv_path' => $cv_path,
                'enable_cv_download' => isset($_POST['enable_cv_download']) ? 1 : 0,
            ])) {
                $success_message = 'CV settings updated';
                $settings = get_user_settings($user_id);
            } elseif (!$error_message) {
                $error_message = 'Error saving CV settings';
            }
        }

        if ($action === 'delete_cv') {
            if (update_user_settings($user_id, ['cv_path' => ''])) {
                $success_message = 'CV removed from the portfolio';
                $settings = get_user_settings($user_id);
            } else {
                $error_message = 'Error removing CV';
            }
        }
        
        // Change Password
        if ($action === 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($new_password !== $confirm_password) {
                $error_message = 'Passwords do not match';
            } elseif (strlen($new_password) < 6) {
                $error_message = 'Password must be at least 6 characters';
            } else {
                $query = "SELECT password FROM portfolio_users WHERE id = ?";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                if (verify_password($current_password, $result['password'])) {
                    $new_hash = hash_password($new_password);
                    $update_query = "UPDATE portfolio_users SET password = ? WHERE id = ?";
                    $update_stmt = $mysqli->prepare($update_query);
                    $update_stmt->bind_param("si", $new_hash, $user_id);
                    
                    if ($update_stmt->execute()) {
                        $success_message = 'Password changed successfully';
                    } else {
                        $error_message = 'Error updating password';
                    }
                } else {
                    $error_message = 'Current password is incorrect';
                }
            }
        }
    }
}

$uploaded_images = list_uploaded_images($user_id);
$cv_path = $settings['cv_path'] ?? '';
$cv_download_enabled = !isset($settings['enable_cv_download']) || (int)$settings['enable_cv_download'] === 1;
$cv_is_pdf = $cv_path !== '' && (bool)preg_match('/\.pdf(\?.*)?$/i', $cv_path);

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        html, body { overflow-x: hidden; }
        [data-tab-content] { display: none; }
        [data-tab-content].active { display: block; }
        .nav-tab.active { @apply bg-indigo-600 text-white; }
        .color-picker { width: 80px; height: 40px; }
        .timeline-color-picker { width: 100%; height: 42px; }
        .tabs-scroll {
            scrollbar-width: thin;
            scrollbar-color: #475569 transparent;
            -webkit-overflow-scrolling: touch;
        }
        .tabs-scroll::-webkit-scrollbar { height: 6px; }
        .tabs-scroll::-webkit-scrollbar-thumb { background: #475569; border-radius: 999px; }
        input, textarea, select, button { font: inherit; }
        textarea { resize: vertical; }
        .choice-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(118px, 1fr));
            gap: 0.75rem;
            max-height: 18rem;
            overflow-y: auto;
            padding: 0.25rem;
            border: 1px solid #334155;
            border-radius: 0.75rem;
            background: rgba(15, 23, 42, 0.55);
        }
        .icon-choice-grid { grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); }
        .choice-option { display: block; cursor: pointer; min-width: 0; }
        .choice-input { position: absolute; opacity: 0; pointer-events: none; }
        .choice-card {
            min-height: 6.75rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem;
            border: 1px solid #475569;
            border-radius: 0.75rem;
            background: #1e293b;
            color: #cbd5e1;
            text-align: center;
            transition: border-color 0.2s ease, background 0.2s ease, transform 0.2s ease;
        }
        .choice-card img { width: 100%; height: 4.25rem; object-fit: contain; border-radius: 0.5rem; background: #0f172a; }
        .choice-card span { width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.75rem; }
        .choice-empty span { white-space: normal; }
        .icon-choice-card { min-height: 5.75rem; }
        .icon-choice-card svg { width: 1.5rem; height: 1.5rem; color: #a5b4fc; }
        .choice-input:checked + .choice-card { border-color: #818cf8; background: rgba(79, 70, 229, 0.28); box-shadow: 0 0 0 2px rgba(129, 140, 248, 0.28); }
        .choice-option:hover .choice-card { border-color: #64748b; transform: translateY(-1px); }
        .cv-frame { width: 100%; height: min(75vh, 760px); border: 1px solid #334155; border-radius: 0.75rem; background: #0f172a; }
        @media (max-width: 640px) {
            .admin-card { padding: 1rem !important; }
            .nav-tab { padding: 0.75rem 0.9rem !important; font-size: 0.875rem; }
            .stack-mobile { flex-direction: column; align-items: stretch !important; gap: 0.75rem !important; }
            .stack-mobile > form, .stack-mobile > a, .stack-mobile > button { width: 100%; }
            .stack-mobile button, .stack-mobile a { text-align: center; justify-content: center; }
            .choice-grid { grid-template-columns: repeat(auto-fill, minmax(92px, 1fr)); max-height: 14rem; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 to-slate-800 text-slate-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-slate-800 border-b border-slate-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-pink-500 flex items-center justify-center">
                    <span class="font-bold text-white">L</span>
                </div>
                <h1 class="text-lg sm:text-xl font-bold">Portfolio Admin</h1>
            </div>
            <div class="flex flex-col xs:flex-row sm:flex-row sm:items-center gap-3 w-full sm:w-auto">
                <span class="text-sm text-slate-400 break-words">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="?logout=1" class="px-4 py-2 bg-red-600/20 hover:bg-red-600/30 rounded-lg text-red-300 transition text-sm text-center">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        <!-- Messages -->
        <?php if ($success_message): ?>
            <div class="mb-6 p-4 rounded-lg bg-green-900/20 border border-green-700/50 text-green-200">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="mb-6 p-4 rounded-lg bg-red-900/20 border border-red-700/50 text-red-200">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Tabs Navigation -->
        <div class="tabs-scroll flex gap-2 mb-8 border-b border-slate-700 overflow-x-auto pb-2">
            <button class="nav-tab <?php echo $tab === 'overview' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('overview')">Overview</button>
            <button class="nav-tab <?php echo $tab === 'hero' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('hero')"> Hero</button>
            <button class="nav-tab <?php echo $tab === 'about' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('about')"> About</button>
            <button class="nav-tab <?php echo $tab === 'goals' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('goals')"> Goals</button>
            <button class="nav-tab <?php echo $tab === 'interests' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('interests')"> Interests</button>
            <button class="nav-tab <?php echo $tab === 'experience' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('experience')"> Experience</button>
            <button class="nav-tab <?php echo $tab === 'education' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('education')">Education</button>
            <button class="nav-tab <?php echo $tab === 'skills' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('skills')"> Skills</button>
            <button class="nav-tab <?php echo $tab === 'projects' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('projects')">Projects</button>
            <button class="nav-tab <?php echo $tab === 'news' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('news')">News</button>
            <button class="nav-tab <?php echo $tab === 'media' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('media')">Media</button>
            <button class="nav-tab <?php echo $tab === 'cv' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('cv')">CV</button>
            <button class="nav-tab <?php echo $tab === 'engagement' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('engagement')">Engagement</button>
            <button class="nav-tab <?php echo $tab === 'popups' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('popups')">Popups</button>
            <button class="nav-tab <?php echo $tab === 'visits' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('visits')">Visits</button>
            <button class="nav-tab <?php echo $tab === 'theme' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('theme')">Theme</button>
            <button class="nav-tab <?php echo $tab === 'typography' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('typography')">Typography</button>
            <button class="nav-tab <?php echo $tab === 'animations' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('animations')">Animations</button>
            <button class="nav-tab <?php echo $tab === 'profile' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('profile')">Profile</button>
            <button class="nav-tab <?php echo $tab === 'settings' ? 'active' : ''; ?> px-4 py-3 font-medium transition whitespace-nowrap" onclick="switchTab('settings')"> Settings</button>
        </div>
        <datalist id="system-icons">
            <?php foreach ($system_icons as $icon): ?><option value="<?php echo htmlspecialchars($icon); ?>"></option><?php endforeach; ?>
        </datalist>

        <!-- Tab Contents -->

        <!-- Overview Tab -->
        <div data-tab-content="overview" class="<?php echo $tab === 'overview' ? 'active' : ''; ?>">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div><p class="text-slate-400 text-sm">Total Projects</p><p class="text-3xl font-bold"><?php echo count($projects); ?></p></div>
                        <svg class="w-12 h-12 text-indigo-500 opacity-20" data-lucide="zap"></svg>
                    </div>
                </div>
                <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div><p class="text-slate-400 text-sm">Goals</p><p class="text-3xl font-bold"><?php echo count($goals); ?></p></div>
                        <svg class="w-12 h-12 text-pink-500 opacity-20" data-lucide="target"></svg>
                    </div>
                </div>
                <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div><p class="text-slate-400 text-sm">Experience</p><p class="text-3xl font-bold"><?php echo count($experience); ?></p></div>
                        <svg class="w-12 h-12 text-green-500 opacity-20" data-lucide="briefcase"></svg>
                    </div>
                </div>
                <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div><p class="text-slate-400 text-sm">Skills</p><p class="text-3xl font-bold"><?php echo count($skills); ?></p></div>
                        <svg class="w-12 h-12 text-yellow-500 opacity-20" data-lucide="award"></svg>
                    </div>
                </div>
            </div>
            <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4"> All Portfolio Sections are Customizable!</h3>
                <div class="space-y-2 text-sm">
                    <p><span class="text-indigo-400"></span> Hero - Customize title, subtitle, and CTA button</p>
                    <p><span class="text-indigo-400"></span> About Me - Full details (bio, location, religion, status, vision)</p>
                    <p><span class="text-indigo-400"></span> Goals & Vision - Add/edit/delete unlimited goals</p>
                    <p><span class="text-indigo-400"></span> Interests & Passions - Add/edit/delete unlimited interests</p>
                    <p><span class="text-indigo-400"></span> Experience - Manage work history with dates</p>
                    <p><span class="text-indigo-400"></span> Skills - Organize by category with proficiency levels</p>
                    <p><span class="text-indigo-400"></span> Theme - 6 color customizations</p>
                    <p><span class="text-indigo-400"></span> Contact - Email, WhatsApp, Location</p>
                </div>
            </div>
        </div>

        <!-- Hero Section Tab -->
        <div data-tab-content="hero" class="<?php echo $tab === 'hero' ? 'active' : ''; ?>">
            <form method="POST" class="bg-slate-800 border border-slate-700 rounded-lg p-6 space-y-6 max-w-2xl">
                <input type="hidden" name="action" value="save_hero">
                <h3 class="text-xl font-bold mb-6"> Customize Hero Section</h3>
                <div><label class="block text-sm font-medium mb-2">Hero Title</label>
                <input type="text" name="hero_title" value="<?php echo htmlspecialchars($settings['hero_title'] ?? 'Lawrence Phuka Chikapa'); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                <div><label class="block text-sm font-medium mb-2">Hero Subtitle/Tagline</label>
                <input type="text" name="hero_subtitle" value="<?php echo htmlspecialchars($settings['hero_subtitle'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                <div><label class="block text-sm font-medium mb-2">CTA Button Text</label>
                <input type="text" name="hero_cta" value="<?php echo htmlspecialchars($settings['hero_cta'] ?? 'Learn More'); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-indigo-600 to-pink-600 text-white font-semibold rounded-lg hover:shadow-lg transition">Save Hero Section</button>
            </form>
        </div>

        <!-- About Section Tab -->
        <div data-tab-content="about" class="<?php echo $tab === 'about' ? 'active' : ''; ?>">
            <form method="POST" class="bg-slate-800 border border-slate-700 rounded-lg p-6 space-y-6">
                <input type="hidden" name="action" value="save_about">
                <h3 class="text-xl font-bold mb-6"> Customize About Section</h3>
                <div><label class="block text-sm font-medium mb-2">About Title</label>
                <input type="text" name="about_title" value="<?php echo htmlspecialchars($settings['about_title'] ?? 'About Me'); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                <div><label class="block text-sm font-medium mb-2">About Description</label>
                <textarea name="about_text" rows="6" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"><?php echo htmlspecialchars($settings['about_text'] ?? ''); ?></textarea></div>
                <div class="grid md:grid-cols-2 gap-6">
                    <div><label class="block text-sm font-medium mb-2">Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($settings['full_name'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                    <div><label class="block text-sm font-medium mb-2">Home of Origin</label>
                    <input type="text" name="home_origin" value="<?php echo htmlspecialchars($settings['home_origin'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                    <div><label class="block text-sm font-medium mb-2">Nationality</label>
                    <input type="text" name="nationality" value="<?php echo htmlspecialchars($settings['nationality'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                    <div><label class="block text-sm font-medium mb-2">Religion</label>
                    <input type="text" name="religion" value="<?php echo htmlspecialchars($settings['religion'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                    <div><label class="block text-sm font-medium mb-2">Status</label>
                    <input type="text" name="status" value="<?php echo htmlspecialchars($settings['status'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                    <div><label class="block text-sm font-medium mb-2">Location</label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($settings['location'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                </div>
                <div><label class="block text-sm font-medium mb-2">Vision & Impact</label>
                <textarea name="vision" rows="4" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"><?php echo htmlspecialchars($settings['vision'] ?? ''); ?></textarea></div>
                <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-indigo-600 to-pink-600 text-white font-semibold rounded-lg hover:shadow-lg transition">Save About Section</button>
            </form>
        </div>

        <!-- Goals Tab -->
        <div data-tab-content="goals" class="<?php echo $tab === 'goals' ? 'active' : ''; ?>">
            <form method="POST" enctype="multipart/form-data" class="bg-slate-800 border border-slate-700 rounded-lg p-6 mb-6">
                <input type="hidden" name="action" value="add_goal">
                <h3 class="text-xl font-bold mb-6"> Add New Goal</h3>
                <div class="space-y-4">
                    <div><label class="block text-sm font-medium mb-2">Goal Title</label>
                    <input type="text" name="goal_title" required class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                    <div><label class="block text-sm font-medium mb-2">Goal Description</label>
                    <textarea name="goal_description" rows="3" required class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></textarea></div>
                    <?php render_icon_controls('goal_icon', 'target', 'target', $uploaded_images); ?>
                    <button type="submit" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">Add Goal</button>
                </div>
            </form>
            <div class="space-y-4">
                <?php if (count($goals) > 0):
                    foreach ($goals as $goal): ?>
                        <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
                            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                                <input type="hidden" name="action" value="update_goal">
                                <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div><label class="block text-sm font-medium mb-2">Goal Title</label>
                                    <input type="text" name="goal_title" required value="<?php echo htmlspecialchars($goal['title']); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                    <div><label class="block text-sm font-medium mb-2">Current Icon Value</label>
                                    <input type="text" value="<?php echo htmlspecialchars($goal['icon_type'] ?? 'target'); ?>" readonly class="w-full px-4 py-2 rounded-lg bg-slate-900 border border-slate-700 text-slate-300"></div>
                                </div>
                                <div><label class="block text-sm font-medium mb-2">Goal Description</label>
                                <textarea name="goal_description" rows="3" required class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"><?php echo htmlspecialchars($goal['description']); ?></textarea></div>
                                <?php render_icon_controls('goal_icon', $goal['icon_type'] ?? 'target', 'target', $uploaded_images); ?>
                                <button type="submit" class="w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">Update Goal</button>
                            </form>
                            <form method="POST" class="mt-3" onsubmit="return confirm('Delete?');"><input type="hidden" name="action" value="delete_goal"><input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>"><button type="submit" class="w-full px-6 py-3 bg-red-600/20 hover:bg-red-600/30 text-red-200 rounded-lg font-semibold transition">Delete Goal</button></form>
                        </div>
                    <?php endforeach;
                else: ?>
                    <div class="bg-slate-800 border border-slate-700 rounded-lg p-6 text-center text-slate-400">No goals yet</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Interests Tab -->
        <div data-tab-content="interests" class="<?php echo $tab === 'interests' ? 'active' : ''; ?>">
            <form method="POST" enctype="multipart/form-data" class="bg-slate-800 border border-slate-700 rounded-lg p-6 mb-6">
                <input type="hidden" name="action" value="add_interest">
                <h3 class="text-xl font-bold mb-6"> Add New Interest</h3>
                <div class="space-y-4">
                    <div><label class="block text-sm font-medium mb-2">Interest Title</label>
                    <input type="text" name="interest_title" required class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                    <div><label class="block text-sm font-medium mb-2">Interest Description</label>
                    <textarea name="interest_description" rows="3" required class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></textarea></div>
                    <?php render_icon_controls('interest_icon', 'star', 'star', $uploaded_images); ?>
                    <button type="submit" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">Add Interest</button>
                </div>
            </form>
            <div class="space-y-4">
                <?php if (count($interests) > 0):
                    foreach ($interests as $interest): ?>
                        <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
                            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                                <input type="hidden" name="action" value="update_interest">
                                <input type="hidden" name="interest_id" value="<?php echo $interest['id']; ?>">
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div><label class="block text-sm font-medium mb-2">Interest Title</label>
                                    <input type="text" name="interest_title" required value="<?php echo htmlspecialchars($interest['title']); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                    <div><label class="block text-sm font-medium mb-2">Current Icon Value</label>
                                    <input type="text" value="<?php echo htmlspecialchars($interest['icon_type'] ?? 'star'); ?>" readonly class="w-full px-4 py-2 rounded-lg bg-slate-900 border border-slate-700 text-slate-300"></div>
                                </div>
                                <div><label class="block text-sm font-medium mb-2">Interest Description</label>
                                <textarea name="interest_description" rows="3" required class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"><?php echo htmlspecialchars($interest['description']); ?></textarea></div>
                                <?php render_icon_controls('interest_icon', $interest['icon_type'] ?? 'star', 'star', $uploaded_images); ?>
                                <button type="submit" class="w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">Update Interest</button>
                            </form>
                            <form method="POST" class="mt-3" onsubmit="return confirm('Delete?');"><input type="hidden" name="action" value="delete_interest"><input type="hidden" name="interest_id" value="<?php echo $interest['id']; ?>"><button type="submit" class="w-full px-6 py-3 bg-red-600/20 hover:bg-red-600/30 text-red-200 rounded-lg font-semibold transition">Delete Interest</button></form>
                        </div>
                    <?php endforeach;
                else: ?>
                    <div class="bg-slate-800 border border-slate-700 rounded-lg p-6 text-center text-slate-400">No interests yet</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Experience Tab -->
        <div data-tab-content="experience" class="<?php echo $tab === 'experience' ? 'active' : ''; ?>">
            <form method="POST" class="bg-slate-800 border border-slate-700 rounded-lg p-6 mb-6">
                <input type="hidden" name="action" value="add_experience">
                <h3 class="text-xl font-bold mb-6"> Add Experience</h3>
                <div class="space-y-4">
                    <div><label class="block text-sm font-medium mb-2">Job Title</label>
                    <input type="text" name="exp_title" required class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                    <div><label class="block text-sm font-medium mb-2">Company/Organization</label>
                    <input type="text" name="exp_company" required class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                    <div><label class="block text-sm font-medium mb-2">Experience Description</label>
                    <textarea name="exp_description" rows="6" placeholder="Describe the role, responsibilities, achievements, and impact..." class="w-full px-4 py-3 rounded-lg bg-slate-700 border border-slate-600 text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500"></textarea></div>
                    <div class="grid md:grid-cols-4 gap-4">
                        <div><label class="block text-sm font-medium mb-2">Start Date</label>
                        <input type="date" name="exp_start" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                        <div><label class="block text-sm font-medium mb-2">End Date</label>
                        <input type="date" name="exp_end" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                        <div><label class="block text-sm font-medium mb-2">Timeline Dot Color</label>
                        <input type="color" name="exp_color" value="#6366f1" class="timeline-color-picker px-3 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                        <div><label class="flex items-center gap-2 mt-7"><input type="checkbox" name="is_current" class="w-4 h-4 rounded"><span class="text-sm">Currently here</span></label></div>
                    </div>
                    <button type="submit" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">Add Experience</button>
                </div>
            </form>
            <div class="space-y-4">
                <?php if (count($experience) > 0):
                    foreach ($experience as $exp):
                        $exp_color = preg_match('/^#[0-9a-fA-F]{6}$/', $exp['icon_color'] ?? '') ? $exp['icon_color'] : '#6366f1';
                    ?>
                        <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
                            <div class="flex items-start gap-3 mb-4">
                                <span class="w-5 h-5 rounded-full mt-1 flex-shrink-0" style="background: <?php echo htmlspecialchars($exp_color); ?>"></span>
                                <div>
                                    <h4 class="text-lg font-bold"><?php echo htmlspecialchars($exp['title']); ?></h4>
                                    <p class="text-sm text-indigo-400"><?php echo htmlspecialchars($exp['company']); ?></p>
                                    <p class="text-xs text-slate-400"><?php echo $exp['start_date'] ? date('M Y', strtotime($exp['start_date'])) : 'N/A'; ?> - <?php echo $exp['is_current'] ? 'Present' : ($exp['end_date'] ? date('M Y', strtotime($exp['end_date'])) : 'N/A'); ?></p>
                                </div>
                            </div>
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="update_experience">
                                <input type="hidden" name="exp_id" value="<?php echo $exp['id']; ?>">
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div><label class="block text-sm font-medium mb-2">Job Title</label>
                                    <input type="text" name="exp_title" required value="<?php echo htmlspecialchars($exp['title']); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                    <div><label class="block text-sm font-medium mb-2">Company/Organization</label>
                                    <input type="text" name="exp_company" required value="<?php echo htmlspecialchars($exp['company']); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                </div>
                                <div><label class="block text-sm font-medium mb-2">Experience Description</label>
                                <textarea name="exp_description" rows="5" class="w-full px-4 py-3 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"><?php echo htmlspecialchars($exp['description']); ?></textarea></div>
                                <div class="grid md:grid-cols-4 gap-4">
                                    <div><label class="block text-sm font-medium mb-2">Start Date</label>
                                    <input type="date" name="exp_start" value="<?php echo htmlspecialchars($exp['start_date'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                    <div><label class="block text-sm font-medium mb-2">End Date</label>
                                    <input type="date" name="exp_end" value="<?php echo htmlspecialchars($exp['end_date'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                    <div><label class="block text-sm font-medium mb-2">Timeline Dot Color</label>
                                    <input type="color" name="exp_color" value="<?php echo htmlspecialchars($exp_color); ?>" class="timeline-color-picker px-3 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                    <div><label class="flex items-center gap-2 mt-7"><input type="checkbox" name="is_current" <?php echo $exp['is_current'] ? 'checked' : ''; ?> class="w-4 h-4 rounded"><span class="text-sm">Currently here</span></label></div>
                                </div>
                                <button type="submit" class="w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">Update Experience</button>
                            </form>
                            <form method="POST" class="mt-3" onsubmit="return confirm('Delete?');">
                                <input type="hidden" name="action" value="delete_experience">
                                <input type="hidden" name="exp_id" value="<?php echo $exp['id']; ?>">
                                <button type="submit" class="w-full px-6 py-3 bg-red-600/20 hover:bg-red-600/30 text-red-200 rounded-lg font-semibold transition">Delete Experience</button>
                            </form>
                        </div>
                    <?php endforeach;
                else: ?>
                    <div class="bg-slate-800 border border-slate-700 rounded-lg p-6 text-center text-slate-400">No experience yet</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Education Tab -->
        <div data-tab-content="education" class="<?php echo $tab === 'education' ? 'active' : ''; ?>">
            <form method="POST" class="bg-slate-800 border border-slate-700 rounded-lg p-6 mb-6">
                <input type="hidden" name="action" value="add_education">
                <h3 class="text-xl font-bold mb-6">Add Education</h3>
                <div class="space-y-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium mb-2">Qualification / Program</label>
                        <input type="text" name="edu_title" required class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                        <div><label class="block text-sm font-medium mb-2">Institution</label>
                        <input type="text" name="edu_institution" required class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                    </div>
                    <div><label class="block text-sm font-medium mb-2">Description</label>
                    <textarea name="edu_description" rows="5" class="w-full px-4 py-3 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></textarea></div>
                    <div class="grid md:grid-cols-4 gap-4">
                        <div><label class="block text-sm font-medium mb-2">Start Date</label>
                        <input type="date" name="edu_start" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                        <div><label class="block text-sm font-medium mb-2">End Date</label>
                        <input type="date" name="edu_end" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                        <div><label class="block text-sm font-medium mb-2">Timeline Dot Color</label>
                        <input type="color" name="edu_color" value="#6366f1" class="timeline-color-picker px-3 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                        <div><label class="flex items-center gap-2 mt-7"><input type="checkbox" name="edu_current" class="w-4 h-4 rounded"><span class="text-sm">Currently studying</span></label></div>
                    </div>
                    <button type="submit" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">Add Education</button>
                </div>
            </form>
            <div class="space-y-4">
                <?php if (count($education) > 0):
                    foreach ($education as $edu):
                        $edu_color = preg_match('/^#[0-9a-fA-F]{6}$/', $edu['icon_color'] ?? '') ? $edu['icon_color'] : '#6366f1';
                    ?>
                        <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="update_education">
                                <input type="hidden" name="education_id" value="<?php echo $edu['id']; ?>">
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div><label class="block text-sm font-medium mb-2">Qualification / Program</label>
                                    <input type="text" name="edu_title" required value="<?php echo htmlspecialchars($edu['title']); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                    <div><label class="block text-sm font-medium mb-2">Institution</label>
                                    <input type="text" name="edu_institution" required value="<?php echo htmlspecialchars($edu['institution']); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                </div>
                                <div><label class="block text-sm font-medium mb-2">Description</label>
                                <textarea name="edu_description" rows="5" class="w-full px-4 py-3 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"><?php echo htmlspecialchars($edu['description']); ?></textarea></div>
                                <div class="grid md:grid-cols-4 gap-4">
                                    <div><label class="block text-sm font-medium mb-2">Start Date</label>
                                    <input type="date" name="edu_start" value="<?php echo htmlspecialchars($edu['start_date'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                    <div><label class="block text-sm font-medium mb-2">End Date</label>
                                    <input type="date" name="edu_end" value="<?php echo htmlspecialchars($edu['end_date'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                    <div><label class="block text-sm font-medium mb-2">Timeline Dot Color</label>
                                    <input type="color" name="edu_color" value="<?php echo htmlspecialchars($edu_color); ?>" class="timeline-color-picker px-3 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                    <div><label class="flex items-center gap-2 mt-7"><input type="checkbox" name="edu_current" <?php echo $edu['is_current'] ? 'checked' : ''; ?> class="w-4 h-4 rounded"><span class="text-sm">Currently studying</span></label></div>
                                </div>
                                <button type="submit" class="w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">Update Education</button>
                            </form>
                            <form method="POST" class="mt-3" onsubmit="return confirm('Delete education entry?');">
                                <input type="hidden" name="action" value="delete_education">
                                <input type="hidden" name="education_id" value="<?php echo $edu['id']; ?>">
                                <button type="submit" class="w-full px-6 py-3 bg-red-600/20 hover:bg-red-600/30 text-red-200 rounded-lg font-semibold transition">Delete Education</button>
                            </form>
                        </div>
                    <?php endforeach;
                else: ?>
                    <div class="bg-slate-800 border border-slate-700 rounded-lg p-6 text-center text-slate-400">No education entries yet</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Skills Tab -->
        <div data-tab-content="skills" class="<?php echo $tab === 'skills' ? 'active' : ''; ?>">
            <form method="POST" class="bg-slate-800 border border-slate-700 rounded-lg p-6 mb-6">
                <input type="hidden" name="action" value="add_skill">
                <h3 class="text-xl font-bold mb-6"> Add Skill</h3>
                <div class="space-y-4">
                    <div><label class="block text-sm font-medium mb-2">Category</label>
                    <input type="text" name="skill_category" required class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                    <div><label class="block text-sm font-medium mb-2">Skill Name</label>
                    <input type="text" name="skill_name" required class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium mb-2">Proficiency</label>
                        <select name="skill_proficiency" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500">
                            <option value="beginner">Beginner</option>
                            <option value="intermediate" selected>Intermediate</option>
                            <option value="advanced">Advanced</option>
                            <option value="expert">Expert</option>
                        </select></div>
                        <div><label class="block text-sm font-medium mb-2">Color</label>
                        <input type="color" name="skill_color" value="#6366f1" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500 color-picker"></div>
                    </div>
                    <button type="submit" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">Add Skill</button>
                </div>
            </form>
            <div class="space-y-4">
                <?php if (count($skills) > 0):
                    foreach ($skills as $skill):
                        $skill_color = clean_hex_color($skill['color'] ?? '#6366f1'); ?>
                        <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="update_skill">
                                <input type="hidden" name="skill_id" value="<?php echo $skill['id']; ?>">
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div><label class="block text-sm font-medium mb-2">Category</label>
                                    <input type="text" name="skill_category" required value="<?php echo htmlspecialchars($skill['category']); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                    <div><label class="block text-sm font-medium mb-2">Skill Name</label>
                                    <input type="text" name="skill_name" required value="<?php echo htmlspecialchars($skill['skill_name']); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                </div>
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div><label class="block text-sm font-medium mb-2">Proficiency</label>
                                    <select name="skill_proficiency" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500">
                                        <?php foreach (['beginner', 'intermediate', 'advanced', 'expert'] as $level): ?>
                                            <option value="<?php echo $level; ?>" <?php echo ($skill['proficiency'] ?? '') === $level ? 'selected' : ''; ?>><?php echo ucfirst($level); ?></option>
                                        <?php endforeach; ?>
                                    </select></div>
                                    <div><label class="block text-sm font-medium mb-2">Color</label>
                                    <input type="color" name="skill_color" value="<?php echo htmlspecialchars($skill_color); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500 color-picker"></div>
                                </div>
                                <button type="submit" class="w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">Update Skill</button>
                            </form>
                            <form method="POST" class="mt-3" onsubmit="return confirm('Delete?');"><input type="hidden" name="action" value="delete_skill"><input type="hidden" name="skill_id" value="<?php echo $skill['id']; ?>"><button type="submit" class="w-full px-6 py-3 bg-red-600/20 hover:bg-red-600/30 text-red-200 rounded-lg font-semibold transition">Delete Skill</button></form>
                        </div>
                    <?php endforeach;
                else: ?>
                    <div class="bg-slate-800 border border-slate-700 rounded-lg p-6 text-center text-slate-400">No skills yet</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Projects Tab -->
        <div data-tab-content="projects" class="<?php echo $tab === 'projects' ? 'active' : ''; ?>">
            <form method="POST" enctype="multipart/form-data" class="bg-slate-800 border border-slate-700 rounded-lg p-6 mb-6">
                <input type="hidden" name="action" value="add_project">
                <h3 class="text-xl font-bold mb-6">Add Project</h3>
                <div class="space-y-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium mb-2">Project Title</label>
                        <input type="text" name="project_title" required class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                        <div><label class="block text-sm font-medium mb-2">Category</label>
                        <input type="text" name="project_category" placeholder="E.g. Web App, E-Learning, Music" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500"></div>
                    </div>
                    <div><label class="block text-sm font-medium mb-2">Short Description</label>
                    <input type="text" name="project_short" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                    <div><label class="block text-sm font-medium mb-2">Full Description</label>
                    <textarea name="project_description" rows="5" required class="w-full px-4 py-3 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></textarea></div>
                    <?php render_icon_controls('project_icon', 'zap', 'zap', $uploaded_images); ?>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium mb-2">Icon Color</label>
                        <input type="color" name="project_color" value="#6366f1" class="timeline-color-picker px-3 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                        <div><label class="block text-sm font-medium mb-2">Tech Stack (comma-separated)</label>
                        <input type="text" name="project_tech" placeholder="PHP, MySQL, JavaScript" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500"></div>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium mb-2">Project Link / Demo URL</label>
                        <input type="url" name="project_demo_url" placeholder="https://example.com" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500"></div>
                        <div><label class="block text-sm font-medium mb-2">GitHub URL</label>
                        <input type="url" name="project_github_url" placeholder="https://github.com/..." class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500"></div>
                    </div>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div><label class="block text-sm font-medium mb-2">Image URL</label>
                        <input type="url" name="project_image" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                        <div><?php render_uploaded_image_select('project_existing_image', '', $uploaded_images, 'Choose Uploaded Image', false); ?></div>
                        <div><label class="block text-sm font-medium mb-2">Or Upload Image</label>
                        <input type="file" name="project_image_file" accept="image/*" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white"></div>
                    </div>
                    <button type="submit" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">Add Project</button>
                </div>
            </form>
            <div class="space-y-4">
                <?php foreach ($projects as $project): ?>
                    <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="action" value="update_project">
                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                            <div class="grid md:grid-cols-2 gap-4">
                                <div><label class="block text-sm font-medium mb-2">Project Title</label>
                                <input type="text" name="project_title" required value="<?php echo htmlspecialchars($project['title']); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                <div><label class="block text-sm font-medium mb-2">Category</label>
                                <input type="text" name="project_category" value="<?php echo htmlspecialchars($project['category'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                            </div>
                            <div><label class="block text-sm font-medium mb-2">Short Description</label>
                            <input type="text" name="project_short" value="<?php echo htmlspecialchars($project['short_description'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                            <div><label class="block text-sm font-medium mb-2">Full Description</label>
                            <textarea name="project_description" rows="5" required class="w-full px-4 py-3 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"><?php echo htmlspecialchars($project['description'] ?? ''); ?></textarea></div>
                            <?php render_icon_controls('project_icon', $project['icon_type'] ?? 'zap', 'zap', $uploaded_images); ?>
                            <div class="grid md:grid-cols-3 gap-4">
                                <div><label class="block text-sm font-medium mb-2">Icon Color</label>
                                <input type="color" name="project_color" value="<?php echo htmlspecialchars(clean_hex_color($project['icon_color'] ?? '#6366f1')); ?>" class="timeline-color-picker px-3 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                <div><label class="block text-sm font-medium mb-2">Tech Stack</label>
                                <input type="text" name="project_tech" value="<?php echo htmlspecialchars(dashboard_tech_stack_text($project['tech_stack'] ?? '')); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                <div><label class="block text-sm font-medium mb-2">Order</label>
                                <input type="number" name="project_order" value="<?php echo (int)($project['order_position'] ?? 0); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                            </div>
                            <div class="grid md:grid-cols-2 gap-4">
                                <div><label class="block text-sm font-medium mb-2">Project Link / Demo URL</label>
                                <input type="url" name="project_demo_url" value="<?php echo htmlspecialchars($project['demo_url'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                <div><label class="block text-sm font-medium mb-2">GitHub URL</label>
                                <input type="url" name="project_github_url" value="<?php echo htmlspecialchars($project['github_url'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                            </div>
                            <div class="grid md:grid-cols-3 gap-4">
                                <div><label class="block text-sm font-medium mb-2">Image URL</label>
                                <input type="url" name="project_image" value="<?php echo htmlspecialchars($project['image_path'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white focus:outline-none focus:border-indigo-500"></div>
                                <div><?php render_uploaded_image_select('project_existing_image', $project['image_path'] ?? '', $uploaded_images, 'Choose Uploaded Image', false); ?></div>
                                <div><label class="block text-sm font-medium mb-2">Upload Replacement</label>
                                <input type="file" name="project_image_file" accept="image/*" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white"></div>
                            </div>
                            <label class="flex items-center gap-2"><input type="checkbox" name="project_featured" <?php echo !empty($project['is_featured']) ? 'checked' : ''; ?> class="w-4 h-4"><span class="text-sm">Featured</span></label>
                            <button type="submit" class="w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">Update Project</button>
                        </form>
                        <form method="POST" class="mt-3" onsubmit="return confirm('Delete project?');">
                            <input type="hidden" name="action" value="delete_project">
                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                            <button type="submit" class="w-full px-6 py-3 bg-red-600/20 hover:bg-red-600/30 text-red-200 rounded-lg font-semibold transition">Delete Project</button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if (count($projects) == 0): ?>
                    <div class="bg-slate-800 border border-slate-700 rounded-lg p-6 text-center text-slate-400">No projects</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- News Tab -->
        <div data-tab-content="news" class="<?php echo $tab === 'news' ? 'active' : ''; ?>">
            <form method="POST" class="bg-slate-800 border border-slate-700 rounded-lg p-6 mb-6">
                <input type="hidden" name="action" value="save_news_settings">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="enable_news" <?php echo !empty($settings['enable_news']) ? 'checked' : ''; ?> class="w-4 h-4">
                    <span class="font-medium">Enable News section on homepage</span>
                </label>
                <button type="submit" class="mt-4 w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">Save News Setting</button>
            </form>
            <form method="POST" class="bg-slate-800 border border-slate-700 rounded-lg p-6 mb-6">
                <input type="hidden" name="action" value="add_news">
                <h3 class="text-xl font-bold mb-6">Add News</h3>
                <div class="space-y-4">
                    <input type="text" name="news_title" required placeholder="News title" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white placeholder-slate-400">
                    <textarea name="news_body" rows="4" placeholder="News body" class="w-full px-4 py-3 rounded-lg bg-slate-700 border border-slate-600 text-white placeholder-slate-400"></textarea>
                    <input type="url" name="news_link" placeholder="Optional link" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white placeholder-slate-400">
                    <label class="flex items-center gap-2"><input type="checkbox" name="news_published" checked class="w-4 h-4"><span class="text-sm">Published</span></label>
                    <button type="submit" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">Add News</button>
                </div>
            </form>
            <div class="space-y-4">
                <?php foreach ($news_items as $item): ?>
                    <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="update_news">
                            <input type="hidden" name="news_id" value="<?php echo $item['id']; ?>">
                            <div><label class="block text-sm font-medium mb-2">Title</label>
                            <input type="text" name="news_title" required value="<?php echo htmlspecialchars($item['title']); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white"></div>
                            <div><label class="block text-sm font-medium mb-2">Body</label>
                            <textarea name="news_body" rows="4" class="w-full px-4 py-3 rounded-lg bg-slate-700 border border-slate-600 text-white"><?php echo htmlspecialchars($item['body'] ?? ''); ?></textarea></div>
                            <div><label class="block text-sm font-medium mb-2">Optional Link</label>
                            <input type="url" name="news_link" value="<?php echo htmlspecialchars($item['link_url'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white"></div>
                            <label class="flex items-center gap-2"><input type="checkbox" name="news_published" <?php echo $item['is_published'] ? 'checked' : ''; ?> class="w-4 h-4"><span class="text-sm">Published</span></label>
                            <button type="submit" class="w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">Update News</button>
                        </form>
                        <div class="grid sm:grid-cols-2 gap-3 mt-3">
                            <form method="POST">
                                <input type="hidden" name="action" value="toggle_news">
                                <input type="hidden" name="news_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="w-full px-6 py-3 bg-indigo-600/30 hover:bg-indigo-600/50 rounded-lg font-semibold transition"><?php echo $item['is_published'] ? 'Unpublish' : 'Publish'; ?></button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Delete news item?');">
                                <input type="hidden" name="action" value="delete_news">
                                <input type="hidden" name="news_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="w-full px-6 py-3 bg-red-600/20 hover:bg-red-600/30 text-red-200 rounded-lg font-semibold transition">Delete News</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Media Tab -->
        <div data-tab-content="media" class="<?php echo $tab === 'media' ? 'active' : ''; ?>">
            <form method="POST" enctype="multipart/form-data" class="bg-slate-800 border border-slate-700 rounded-lg p-6 mb-6">
                <input type="hidden" name="action" value="save_brand_assets">
                <h3 class="text-xl font-bold mb-6">Profile Picture & Favicon</h3>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="block text-sm font-medium">Profile Image URL</label>
                        <input type="url" name="profile_image" value="<?php echo htmlspecialchars($settings['profile_image'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white">
                        <?php render_uploaded_image_select('profile_image_existing', $settings['profile_image'] ?? '', $uploaded_images, 'Choose Uploaded Profile Image', false); ?>
                        <input type="file" name="profile_image_file" accept="image/*" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-sm font-medium">Favicon URL</label>
                        <input type="url" name="favicon_path" value="<?php echo htmlspecialchars($settings['favicon_path'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white">
                        <?php render_uploaded_image_select('favicon_existing', $settings['favicon_path'] ?? '', $uploaded_images, 'Choose Uploaded Favicon', false); ?>
                        <input type="file" name="favicon_file" accept=".ico,image/*" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white">
                    </div>
                </div>
                <button type="submit" class="mt-6 w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">Save Media</button>
            </form>

            <form method="POST" enctype="multipart/form-data" class="bg-slate-800 border border-slate-700 rounded-lg p-6">
                <input type="hidden" name="action" value="save_section_backgrounds">
                <h3 class="text-xl font-bold mb-6">Section Background Images</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <?php foreach ($background_sections as $key => $label):
                        $bg = $section_backgrounds[$key] ?? ['background_image' => '', 'overlay_opacity' => '0.80'];
                    ?>
                        <div class="bg-slate-900/60 border border-slate-700 rounded-lg p-4">
                            <label class="block text-sm font-medium mb-2"><?php echo htmlspecialchars($label); ?> Background URL</label>
                            <input type="url" name="section_background[<?php echo htmlspecialchars($key); ?>]" value="<?php echo htmlspecialchars($bg['background_image'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white mb-3">
                            <div class="mb-3"><?php render_uploaded_image_select('section_background_existing[' . htmlspecialchars($key) . ']', $bg['background_image'] ?? '', $uploaded_images, 'Choose Uploaded Background', false); ?></div>
                            <label class="block text-sm font-medium mb-2">Upload Background</label>
                            <input type="file" name="section_background_file[<?php echo htmlspecialchars($key); ?>]" accept=".svg,image/*" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white mb-3">
                            <label class="block text-xs text-slate-400 mb-1">Overlay opacity</label>
                            <input type="number" step="0.05" min="0" max="0.95" name="section_overlay[<?php echo htmlspecialchars($key); ?>]" value="<?php echo htmlspecialchars($bg['overlay_opacity'] ?? '0.80'); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="mt-6 w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">Save Section Backgrounds</button>
            </form>
        </div>

        <!-- CV Tab -->
        <div data-tab-content="cv" class="<?php echo $tab === 'cv' ? 'active' : ''; ?>">
            <div class="grid lg:grid-cols-2 gap-6">
                <form method="POST" enctype="multipart/form-data" class="bg-slate-800 border border-slate-700 rounded-lg p-6 space-y-5">
                    <input type="hidden" name="action" value="save_cv">
                    <h3 class="text-xl font-bold">Manage CV</h3>
                    <div>
                        <label class="block text-sm font-medium mb-2">CV URL</label>
                        <input type="url" name="cv_path" value="<?php echo htmlspecialchars($cv_path); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Upload CV</label>
                        <input type="file" name="cv_file" accept=".pdf,.doc,.docx" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white">
                    </div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="enable_cv_download" <?php echo $cv_download_enabled ? 'checked' : ''; ?> class="w-4 h-4">
                        <span class="font-medium">Allow visitors to download the CV</span>
                    </label>
                    <button type="submit" class="w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">Save CV Settings</button>
                </form>

                <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
                    <div class="stack-mobile flex items-start justify-between gap-4 mb-5">
                        <div>
                            <h3 class="text-xl font-bold">CV Preview</h3>
                            <p class="text-sm text-slate-400 mt-1"><?php echo $cv_download_enabled ? 'Downloads enabled' : 'Downloads hidden from public buttons'; ?></p>
                        </div>
                        <?php if ($cv_path): ?>
                            <form method="POST" onsubmit="return confirm('Remove CV from the portfolio?');">
                                <input type="hidden" name="action" value="delete_cv">
                                <button type="submit" class="px-4 py-2 bg-red-600/20 hover:bg-red-600/30 text-red-200 rounded-lg text-sm font-semibold">Remove CV</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if ($cv_path): ?>
                        <div class="space-y-4">
                            <a href="<?php echo htmlspecialchars($cv_path); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-sm font-semibold">
                                <i data-lucide="external-link" class="w-4 h-4"></i> Open CV
                            </a>
                            <?php if ($cv_is_pdf): ?>
                                <iframe class="cv-frame" src="<?php echo htmlspecialchars($cv_path); ?>#toolbar=1"></iframe>
                            <?php else: ?>
                                <div class="border border-slate-700 rounded-lg p-6 text-slate-300 bg-slate-900/70">
                                    This CV file type may open in your browser or office app depending on system support.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="border border-slate-700 rounded-lg p-6 text-slate-400 bg-slate-900/70">No CV has been uploaded yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Engagement Tab -->
        <div data-tab-content="engagement" class="<?php echo $tab === 'engagement' ? 'active' : ''; ?>">
            <?php
                $engagement_groups = [
                    'booking' => ['Bookings', $bookings, 'service'],
                    'request' => ['Requests', $requests, 'request_type'],
                    'comment' => ['Comments', $comments, 'comment'],
                ];
            ?>
            <div class="grid lg:grid-cols-3 gap-6">
                <?php foreach ($engagement_groups as $kind => [$title, $rows, $main_field]): ?>
                    <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
                        <h3 class="text-xl font-bold mb-4"><?php echo $title; ?></h3>
                        <div class="space-y-4">
                            <?php foreach ($rows as $row): ?>
                                <div class="bg-slate-900/70 border border-slate-700 rounded-lg p-4">
                                    <div class="flex items-center justify-between gap-3 mb-3">
                                        <p class="font-semibold"><?php echo htmlspecialchars($row['visitor_name'] ?? 'Visitor'); ?></p>
                                        <span class="text-xs px-2 py-1 rounded-full <?php echo !empty($row['is_reviewed']) ? 'bg-green-600/20 text-green-200' : 'bg-yellow-600/20 text-yellow-200'; ?>"><?php echo !empty($row['is_reviewed']) ? 'Reviewed' : 'New'; ?></span>
                                    </div>
                                    <div class="space-y-2 text-sm">
                                        <p class="text-slate-400"><?php echo htmlspecialchars($row['visitor_email'] ?? ''); ?></p>
                                        <?php if ($kind === 'booking'): ?>
                                            <?php if (!empty($row['visitor_phone'])): ?><p><span class="text-slate-500">Phone:</span> <?php echo htmlspecialchars($row['visitor_phone']); ?></p><?php endif; ?>
                                            <?php if (!empty($row['service'])): ?><p><span class="text-slate-500">Service:</span> <?php echo htmlspecialchars($row['service']); ?></p><?php endif; ?>
                                            <?php if (!empty($row['preferred_date'])): ?><p><span class="text-slate-500">Preferred:</span> <?php echo htmlspecialchars($row['preferred_date']); ?></p><?php endif; ?>
                                            <?php if (!empty($row['message'])): ?><p class="text-slate-300 whitespace-pre-wrap"><?php echo htmlspecialchars($row['message']); ?></p><?php endif; ?>
                                        <?php elseif ($kind === 'request'): ?>
                                            <?php if (!empty($row['request_type'])): ?><p><span class="text-slate-500">Type:</span> <?php echo htmlspecialchars($row['request_type']); ?></p><?php endif; ?>
                                            <?php if (!empty($row['message'])): ?><p class="text-slate-300 whitespace-pre-wrap"><?php echo htmlspecialchars($row['message']); ?></p><?php endif; ?>
                                        <?php else: ?>
                                            <p class="text-slate-300 whitespace-pre-wrap"><?php echo htmlspecialchars($row['comment'] ?? ''); ?></p>
                                            <p class="text-xs text-slate-500"><?php echo !empty($row['is_approved']) ? 'Approved' : 'Not approved'; ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($row['status'])): ?><p class="text-xs text-slate-500">Status: <?php echo htmlspecialchars($row['status']); ?></p><?php endif; ?>
                                        <?php if (!empty($row['created_at'])): ?><p class="text-xs text-slate-500"><?php echo htmlspecialchars($row['created_at']); ?></p><?php endif; ?>
                                    </div>
                                    <div class="grid sm:grid-cols-2 gap-2 mt-3">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="review_engagement">
                                            <input type="hidden" name="engagement_kind" value="<?php echo $kind; ?>">
                                            <input type="hidden" name="engagement_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="w-full px-3 py-2 bg-indigo-600/30 hover:bg-indigo-600/50 rounded text-sm">Review</button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Delete item?');">
                                            <input type="hidden" name="action" value="delete_engagement">
                                            <input type="hidden" name="engagement_kind" value="<?php echo $kind; ?>">
                                            <input type="hidden" name="engagement_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="w-full px-3 py-2 bg-red-600/20 hover:bg-red-600/30 text-red-200 rounded text-sm">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (!$rows): ?><p class="text-sm text-slate-400">No <?php echo strtolower($title); ?> yet.</p><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Popups Tab -->
        <div data-tab-content="popups" class="<?php echo $tab === 'popups' ? 'active' : ''; ?>">
            <form method="POST" enctype="multipart/form-data" class="bg-slate-800 border border-slate-700 rounded-lg p-6 mb-6">
                <input type="hidden" name="action" value="add_popup">
                <h3 class="text-xl font-bold mb-6">Add Popup Ad</h3>
                <div class="space-y-4">
                    <input type="text" name="popup_title" required placeholder="Popup title" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white placeholder-slate-400">
                    <textarea name="popup_body" rows="4" placeholder="Popup message" class="w-full px-4 py-3 rounded-lg bg-slate-700 border border-slate-600 text-white placeholder-slate-400"></textarea>
                    <div class="grid md:grid-cols-2 gap-4">
                        <input type="url" name="popup_link" placeholder="Optional link" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white placeholder-slate-400">
                        <input type="text" name="popup_button" value="Learn More" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white">
                    </div>
                    <div class="grid md:grid-cols-3 gap-4">
                        <input type="url" name="popup_image" placeholder="Optional image URL" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white placeholder-slate-400">
                        <div><?php render_uploaded_image_select('popup_existing_image', '', $uploaded_images, 'Choose Uploaded Image', false); ?></div>
                        <input type="file" name="popup_image_file" accept="image/*" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white">
                    </div>
                    <label class="flex items-center gap-2"><input type="checkbox" name="popup_active" checked class="w-4 h-4"><span class="text-sm">Active</span></label>
                    <div class="border border-slate-600 rounded-lg p-4 bg-slate-700 space-y-4">
                        <h4 class="font-semibold text-sm">Popup Triggers</h4>
                        <label class="flex items-center gap-2"><input type="checkbox" name="popup_trigger_timer" checked class="w-4 h-4"><span class="text-sm">Timer - Show after delay</span></label>
                        <input type="number" name="popup_timer_delay" value="900" min="0" max="60000" step="100" placeholder="Milliseconds" class="w-full px-4 py-2 rounded-lg bg-slate-600 border border-slate-500 text-white placeholder-slate-400 text-xs">
                        <label class="flex items-center gap-2"><input type="checkbox" name="popup_trigger_scroll" class="w-4 h-4"><span class="text-sm">Scroll - Show on scroll trigger</span></label>
                        <input type="number" name="popup_scroll_percentage" value="50" min="1" max="100" placeholder="Scroll percentage" class="w-full px-4 py-2 rounded-lg bg-slate-600 border border-slate-500 text-white placeholder-slate-400 text-xs">
                        <label class="flex items-center gap-2"><input type="checkbox" name="popup_trigger_esc" checked class="w-4 h-4"><span class="text-sm">ESC key - Allow ESC to close</span></label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="popup_trigger_back_button" checked class="w-4 h-4"><span class="text-sm">Back button - Enable back button interaction</span></label>
                    </div>
                    <button type="submit" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">Add Popup</button>
                </div>
            </form>
            <div class="space-y-4">
                <?php foreach ($popups as $popup): ?>
                    <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="action" value="update_popup">
                            <input type="hidden" name="popup_id" value="<?php echo $popup['id']; ?>">
                            <input type="text" name="popup_title" required value="<?php echo htmlspecialchars($popup['title'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white">
                            <textarea name="popup_body" rows="4" class="w-full px-4 py-3 rounded-lg bg-slate-700 border border-slate-600 text-white"><?php echo htmlspecialchars($popup['body'] ?? ''); ?></textarea>
                            <div class="grid md:grid-cols-2 gap-4">
                                <input type="url" name="popup_link" value="<?php echo htmlspecialchars($popup['link_url'] ?? ''); ?>" placeholder="Optional link" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white">
                                <input type="text" name="popup_button" value="<?php echo htmlspecialchars($popup['button_text'] ?? 'Learn More'); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white">
                            </div>
                            <div class="grid md:grid-cols-3 gap-4">
                                <input type="url" name="popup_image" value="<?php echo htmlspecialchars($popup['image_url'] ?? ''); ?>" placeholder="Image URL" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white">
                                <div><?php render_uploaded_image_select('popup_existing_image', $popup['image_url'] ?? '', $uploaded_images, 'Choose Uploaded Image', false); ?></div>
                                <input type="file" name="popup_image_file" accept="image/*" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white">
                            </div>
                            <label class="flex items-center gap-2"><input type="checkbox" name="popup_active" <?php echo $popup['is_active'] ? 'checked' : ''; ?> class="w-4 h-4"><span class="text-sm">Active</span></label>
                            <div class="border border-slate-600 rounded-lg p-4 bg-slate-700 space-y-4">
                                <h4 class="font-semibold text-sm">Popup Triggers</h4>
                                <label class="flex items-center gap-2"><input type="checkbox" name="popup_trigger_timer" <?php echo ($popup['trigger_timer'] ?? 1) ? 'checked' : ''; ?> class="w-4 h-4"><span class="text-sm">Timer - Show after delay</span></label>
                                <input type="number" name="popup_timer_delay" value="<?php echo (int)($popup['timer_delay'] ?? 900); ?>" min="0" max="60000" step="100" placeholder="Milliseconds" class="w-full px-4 py-2 rounded-lg bg-slate-600 border border-slate-500 text-white placeholder-slate-400 text-xs">
                                <label class="flex items-center gap-2"><input type="checkbox" name="popup_trigger_scroll" <?php echo ($popup['trigger_scroll'] ?? 0) ? 'checked' : ''; ?> class="w-4 h-4"><span class="text-sm">Scroll - Show on scroll trigger</span></label>
                                <input type="number" name="popup_scroll_percentage" value="<?php echo (int)($popup['scroll_percentage'] ?? 50); ?>" min="1" max="100" placeholder="Scroll percentage" class="w-full px-4 py-2 rounded-lg bg-slate-600 border border-slate-500 text-white placeholder-slate-400 text-xs">
                                <label class="flex items-center gap-2"><input type="checkbox" name="popup_trigger_esc" <?php echo ($popup['trigger_esc'] ?? 1) ? 'checked' : ''; ?> class="w-4 h-4"><span class="text-sm">ESC key - Allow ESC to close</span></label>
                                <label class="flex items-center gap-2"><input type="checkbox" name="popup_trigger_back_button" <?php echo ($popup['trigger_back_button'] ?? 1) ? 'checked' : ''; ?> class="w-4 h-4"><span class="text-sm">Back button - Enable back button interaction</span></label>
                            </div>
                            <button type="submit" class="w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">Update Popup</button>
                        </form>
                        <div class="grid sm:grid-cols-2 gap-3 mt-3">
                            <form method="POST">
                                <input type="hidden" name="action" value="toggle_popup">
                                <input type="hidden" name="popup_id" value="<?php echo $popup['id']; ?>">
                                <button type="submit" class="w-full px-6 py-3 bg-indigo-600/30 hover:bg-indigo-600/50 rounded-lg font-semibold transition"><?php echo $popup['is_active'] ? 'Deactivate' : 'Activate'; ?></button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Delete popup?');">
                                <input type="hidden" name="action" value="delete_popup">
                                <input type="hidden" name="popup_id" value="<?php echo $popup['id']; ?>">
                                <button type="submit" class="w-full px-6 py-3 bg-red-600/20 hover:bg-red-600/30 text-red-200 rounded-lg font-semibold transition">Delete Popup</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Visits Tab -->
        <div data-tab-content="visits" class="<?php echo $tab === 'visits' ? 'active' : ''; ?>">
            <div class="bg-slate-800 border border-slate-700 rounded-lg p-6 overflow-x-auto">
                <h3 class="text-xl font-bold mb-4">Recent Site Visits</h3>
                <form method="POST" class="mb-4" onsubmit="return confirm('Clear all recorded visits?');">
                    <input type="hidden" name="action" value="clear_visits">
                    <button type="submit" class="px-4 py-2 bg-red-600/20 hover:bg-red-600/30 text-red-200 rounded-lg text-sm font-semibold">Clear Visits</button>
                </form>
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-slate-400"><th class="py-2">Time</th><th class="py-2">IP</th><th class="py-2">Page</th><th class="py-2">Referrer</th><th class="py-2">Browser</th></tr></thead>
                    <tbody>
                        <?php foreach ($visits as $visit): ?>
                            <tr class="border-t border-slate-700">
                                <td class="py-3 whitespace-nowrap"><?php echo htmlspecialchars($visit['visited_at']); ?></td>
                                <td class="py-3"><?php echo htmlspecialchars($visit['ip_address']); ?></td>
                                <td class="py-3"><?php echo htmlspecialchars($visit['page_url']); ?></td>
                                <td class="py-3"><?php echo htmlspecialchars($visit['referrer']); ?></td>
                                <td class="py-3 max-w-xs truncate"><?php echo htmlspecialchars($visit['user_agent']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!$visits): ?><p class="text-slate-400 text-sm">No visits logged yet.</p><?php endif; ?>
            </div>
        </div>

        <!-- Theme Tab -->
        <div data-tab-content="theme" class="<?php echo $tab === 'theme' ? 'active' : ''; ?>">
            <form method="POST" class="grid md:grid-cols-2 gap-8">
                <input type="hidden" name="action" value="save_theme">
                <div><label class="block text-sm font-medium mb-2">Background</label>
                <div class="flex gap-3"><input type="color" name="background_color" value="<?php echo htmlspecialchars($settings['background_color']); ?>" class="color-picker rounded"><code class="text-xs text-slate-400"><?php echo htmlspecialchars($settings['background_color']); ?></code></div></div>
                <div><label class="block text-sm font-medium mb-2">Surface</label>
                <div class="flex gap-3"><input type="color" name="surface_color" value="<?php echo htmlspecialchars($settings['surface_color']); ?>" class="color-picker rounded"><code class="text-xs text-slate-400"><?php echo htmlspecialchars($settings['surface_color']); ?></code></div></div>
                <div><label class="block text-sm font-medium mb-2">Text</label>
                <div class="flex gap-3"><input type="color" name="text_color" value="<?php echo htmlspecialchars($settings['text_color']); ?>" class="color-picker rounded"><code class="text-xs text-slate-400"><?php echo htmlspecialchars($settings['text_color']); ?></code></div></div>
                <div><label class="block text-sm font-medium mb-2">Primary Action</label>
                <div class="flex gap-3"><input type="color" name="primary_action_color" value="<?php echo htmlspecialchars($settings['primary_action_color']); ?>" class="color-picker rounded"><code class="text-xs text-slate-400"><?php echo htmlspecialchars($settings['primary_action_color']); ?></code></div></div>
                <div><label class="block text-sm font-medium mb-2">Secondary Action</label>
                <div class="flex gap-3"><input type="color" name="secondary_action_color" value="<?php echo htmlspecialchars($settings['secondary_action_color']); ?>" class="color-picker rounded"><code class="text-xs text-slate-400"><?php echo htmlspecialchars($settings['secondary_action_color']); ?></code></div></div>
                <div><label class="block text-sm font-medium mb-2">Accent</label>
                <div class="flex gap-3"><input type="color" name="accent_color" value="<?php echo htmlspecialchars($settings['accent_color']); ?>" class="color-picker rounded"><code class="text-xs text-slate-400"><?php echo htmlspecialchars($settings['accent_color']); ?></code></div></div>
                <div class="md:col-span-2"><button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-indigo-600 to-pink-600 text-white font-semibold rounded-lg hover:shadow-lg transition">Save Theme</button></div>
            </form>
        </div>

        <!-- Typography Tab -->
        <div data-tab-content="typography" class="<?php echo $tab === 'typography' ? 'active' : ''; ?>">
            <form method="POST" class="max-w-xl">
                <input type="hidden" name="action" value="save_typography">
                <div class="mb-6"><label class="block text-sm font-medium mb-2">Font Family</label>
                <select name="font_family" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white">
                    <option value="Syne" <?php echo $settings['font_family'] === 'Syne' ? 'selected' : ''; ?>>Syne</option>
                    <option value="Inter" <?php echo $settings['font_family'] === 'Inter' ? 'selected' : ''; ?>>Inter</option>
                    <option value="Poppins" <?php echo $settings['font_family'] === 'Poppins' ? 'selected' : ''; ?>>Poppins</option>
                    <option value="Roboto" <?php echo $settings['font_family'] === 'Roboto' ? 'selected' : ''; ?>>Roboto</option>
                    <option value="Playfair Display" <?php echo $settings['font_family'] === 'Playfair Display' ? 'selected' : ''; ?>>Playfair Display</option>
                    <option value="DM Sans" <?php echo $settings['font_family'] === 'DM Sans' ? 'selected' : ''; ?>>DM Sans</option>
                </select></div>
                <div class="mb-6"><label class="block text-sm font-medium mb-2">Font Size: <span class="font-size-display"><?php echo $settings['font_size']; ?>px</span></label>
                <input type="range" name="font_size" min="12" max="24" value="<?php echo $settings['font_size']; ?>" class="w-full" onchange="document.querySelector('.font-size-display').textContent = this.value + 'px'"></div>
                <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-indigo-600 to-pink-600 text-white font-semibold rounded-lg transition">Save Typography</button>
            </form>
        </div>

        <!-- Animations Tab -->
        <div data-tab-content="animations" class="<?php echo $tab === 'animations' ? 'active' : ''; ?>">
            <form method="POST" class="max-w-xl">
                <input type="hidden" name="action" value="save_animations">
                <div class="mb-6"><label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" name="enable_animations" <?php echo $settings['enable_animations'] ? 'checked' : ''; ?> class="w-4 h-4"><span class="font-medium">Enable Animations</span></label></div>
                <div class="mb-6"><label class="block text-sm font-medium mb-2">Cursor Style</label>
                <select name="cursor_animation" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white">
                    <option value="default" <?php echo $settings['cursor_animation'] === 'default' ? 'selected' : ''; ?>>Default</option>
                    <option value="glow" <?php echo $settings['cursor_animation'] === 'glow' ? 'selected' : ''; ?>>Glow</option>
                    <option value="trail" <?php echo $settings['cursor_animation'] === 'trail' ? 'selected' : ''; ?>>Trail</option>
                    <option value="bounce" <?php echo $settings['cursor_animation'] === 'bounce' ? 'selected' : ''; ?>>Bounce</option>
                </select></div>
                <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-indigo-600 to-pink-600 text-white font-semibold rounded-lg transition">Save Animations</button>
            </form>
        </div>

        <!-- Profile Tab -->
        <div data-tab-content="profile" class="<?php echo $tab === 'profile' ? 'active' : ''; ?>">
            <form method="POST" class="max-w-2xl mb-8">
                <input type="hidden" name="action" value="save_profile">
                <h3 class="text-xl font-bold mb-6">Profile Info</h3>
                <div class="space-y-4">
                    <div><label class="block text-sm font-medium mb-2">Bio</label>
                    <textarea name="bio" rows="4" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white"><?php echo htmlspecialchars($settings['bio'] ?? ''); ?></textarea></div>
                    <div><label class="block text-sm font-medium mb-2">Location</label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($settings['location'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white"></div>
                    <div><label class="block text-sm font-medium mb-2">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($settings['email'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white"></div>
                    <div><label class="block text-sm font-medium mb-2">WhatsApp</label>
                    <input type="tel" name="whatsapp" value="<?php echo htmlspecialchars($settings['whatsapp'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white"></div>
                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-indigo-600 to-pink-600 text-white font-semibold rounded-lg transition">Save Profile</button>
                </div>
            </form>
        </div>

        <!-- Settings Tab -->
        <div data-tab-content="settings" class="<?php echo $tab === 'settings' ? 'active' : ''; ?>">
            <form method="POST" class="max-w-xl">
                <input type="hidden" name="action" value="change_password">
                <h3 class="text-xl font-bold mb-6"> Change Password</h3>
                <div class="space-y-6">
                    <div><label class="block text-sm font-medium mb-2">Current Password</label>
                    <input type="password" name="current_password" required class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white"></div>
                    <div><label class="block text-sm font-medium mb-2">New Password</label>
                    <input type="password" name="new_password" required class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white"></div>
                    <div><label class="block text-sm font-medium mb-2">Confirm Password</label>
                    <input type="password" name="confirm_password" required class="w-full px-4 py-2 rounded-lg bg-slate-700 border border-slate-600 text-white"></div>
                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-indigo-600 to-pink-600 text-white font-semibold rounded-lg transition">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('[data-tab-content]').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-tab').forEach(el => el.classList.remove('active'));
            document.querySelector('[data-tab-content="' + tabName + '"]').classList.add('active');
            event.target.classList.add('active');
            window.history.pushState({}, '', '?tab=' + tabName);
        }
        lucide.createIcons();
    </script>
</body>
</html>
