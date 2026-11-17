<?php
// Shared profile setup for Admin, Registrar, and Student dashboards.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sharedProfileRole = strtolower((string) ($_SESSION['role'] ?? ''));
$sharedProfileRoleLabels = [
    'admin' => 'Administrator',
    'registrar' => 'Registrar',
    'student' => 'Student',
];
$sharedProfileRoleLabel = $sharedProfileRoleLabels[$sharedProfileRole] ?? 'User';
$sharedProfileUserId = (int) ($_SESSION['user_id'] ?? 0);
$sharedProfileName = trim((string) ($_SESSION['full_name'] ?? $sharedProfileRoleLabel));
$sharedProfileEmail = trim((string) ($_SESSION['email'] ?? ''));
$sharedProfileUploadMessage = '';
$sharedProfileUploadIsError = false;

$sharedProfileUploadDir = dirname(__DIR__) . '/uploads/' . $sharedProfileRole . '_profiles/';
$sharedProfileUploadUrl = '../uploads/' . $sharedProfileRole . '_profiles/';

if ($sharedProfileUserId > 0 && in_array($sharedProfileRole, ['admin', 'registrar', 'student'], true)) {
    if (!is_dir($sharedProfileUploadDir)) {
        @mkdir($sharedProfileUploadDir, 0755, true);
    }

    if (empty($_SESSION['shared_profile_upload_token'])) {
        $_SESSION['shared_profile_upload_token'] = bin2hex(random_bytes(32));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shared_profile_upload'])) {
        $message = '';
        $isError = true;
        $token = (string) ($_POST['shared_profile_upload_token'] ?? '');
        $sessionToken = (string) ($_SESSION['shared_profile_upload_token'] ?? '');

        if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
            $message = 'Your profile upload session expired. Please refresh and try again.';
        } elseif (!isset($_FILES['shared_profile_picture']) || !is_array($_FILES['shared_profile_picture'])) {
            $message = 'Please choose a profile picture.';
        } elseif ($_FILES['shared_profile_picture']['error'] !== UPLOAD_ERR_OK) {
            $message = 'The image could not be uploaded. Please choose another file.';
        } elseif ((int) $_FILES['shared_profile_picture']['size'] > 2 * 1024 * 1024) {
            $message = 'The image must be 2 MB or smaller.';
        } else {
            $file = $_FILES['shared_profile_picture'];
            $imageInfo = @getimagesize($file['tmp_name']);
            $mimeType = $imageInfo['mime'] ?? '';

            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $detectedMime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                    if (is_string($detectedMime) && $detectedMime !== '') {
                        $mimeType = $detectedMime;
                    }
                }
            }

            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];

            if (!$imageInfo || !isset($allowedTypes[$mimeType])) {
                $message = 'Please upload a valid JPG, PNG, or WEBP image.';
            } elseif (!is_dir($sharedProfileUploadDir) || !is_writable($sharedProfileUploadDir)) {
                $message = 'The profile upload folder is not writable.';
            } else {
                $baseName = $sharedProfileRole . '_' . $sharedProfileUserId;
                $extension = $allowedTypes[$mimeType];
                $destination = $sharedProfileUploadDir . $baseName . '.' . $extension;
                $saved = move_uploaded_file($file['tmp_name'], $destination);

                if ($saved) {
                    foreach (['jpg', 'png', 'webp'] as $oldExtension) {
                        $oldFile = $sharedProfileUploadDir . $baseName . '.' . $oldExtension;
                        if ($oldExtension !== $extension && is_file($oldFile)) {
                            @unlink($oldFile);
                        }
                    }
                    $message = 'Profile picture updated successfully.';
                    $isError = false;
                } else {
                    $message = 'Unable to save the image. Please try again.';
                }
            }
        }

        $_SESSION['shared_profile_flash'] = [
            'message' => $message,
            'error' => $isError,
        ];
        $redirectUrl = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
        header('Location: ' . ($redirectUrl !== false && $redirectUrl !== '' ? $redirectUrl : ''));
        exit();
    }
}

$sharedProfileFlash = $_SESSION['shared_profile_flash'] ?? null;
unset($_SESSION['shared_profile_flash']);
if (is_array($sharedProfileFlash)) {
    $sharedProfileUploadMessage = (string) ($sharedProfileFlash['message'] ?? '');
    $sharedProfileUploadIsError = (bool) ($sharedProfileFlash['error'] ?? false);
}

$sharedProfileImageUrl = '';
if ($sharedProfileUserId > 0 && is_dir($sharedProfileUploadDir)) {
    $baseName = $sharedProfileRole . '_' . $sharedProfileUserId;
    foreach (['jpg', 'png', 'webp'] as $extension) {
        $candidate = $sharedProfileUploadDir . $baseName . '.' . $extension;
        if (is_file($candidate)) {
            $sharedProfileImageUrl = $sharedProfileUploadUrl . $baseName . '.' . $extension . '?v=' . filemtime($candidate);
            break;
        }
    }
}

$nameParts = preg_split('/\s+/', trim($sharedProfileName));
$sharedProfileInitials = '';
foreach (array_slice($nameParts ?: [], 0, 2) as $part) {
    if ($part !== '') {
        $sharedProfileInitials .= strtoupper(substr($part, 0, 1));
    }
}
if ($sharedProfileInitials === '') {
    $sharedProfileInitials = strtoupper(substr($sharedProfileRoleLabel, 0, 1));
}
$sharedProfileToken = (string) ($_SESSION['shared_profile_upload_token'] ?? '');
?>
