<div class="shared-profile-overlay" id="sharedProfileModal" aria-hidden="true" data-open="<?= $sharedProfileUploadMessage !== '' ? '1' : '0' ?>">
    <button class="shared-profile-backdrop" type="button" aria-label="Close profile" onclick="closeSharedProfile()"></button>
    <section class="shared-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="sharedProfileTitle">
        <div class="shared-profile-modal-top">
            <div>
                <p class="shared-profile-eyebrow">ACCOUNT SETTINGS</p>
                <h2 id="sharedProfileTitle">My <?= htmlspecialchars($sharedProfileRoleLabel, ENT_QUOTES, 'UTF-8') ?> Profile</h2>
            </div>
            <button class="shared-profile-close" type="button" onclick="closeSharedProfile()" aria-label="Close profile">×</button>
        </div>

        <div class="shared-profile-identity">
            <?php if ($sharedProfileImageUrl !== ''): ?>
                <img id="sharedProfilePhotoPreview" class="shared-profile-avatar large" src="<?= htmlspecialchars($sharedProfileImageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Profile picture">
                <span id="sharedProfilePhotoFallback" class="shared-profile-avatar large" hidden><?= htmlspecialchars($sharedProfileInitials, ENT_QUOTES, 'UTF-8') ?></span>
            <?php else: ?>
                <img id="sharedProfilePhotoPreview" class="shared-profile-avatar large" src="" alt="Profile picture" hidden>
                <span id="sharedProfilePhotoFallback" class="shared-profile-avatar large"><?= htmlspecialchars($sharedProfileInitials, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
            <div class="shared-profile-identity-text">
                <h3><?= htmlspecialchars($sharedProfileName, ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars($sharedProfileEmail !== '' ? $sharedProfileEmail : 'No email address on file', ENT_QUOTES, 'UTF-8') ?></p>
                <span><?= htmlspecialchars($sharedProfileRoleLabel, ENT_QUOTES, 'UTF-8') ?> Account</span>
            </div>
        </div>

        <?php if ($sharedProfileUploadMessage !== ''): ?>
            <div class="shared-profile-message <?= $sharedProfileUploadIsError ? 'is-error' : 'is-success' ?>" role="status">
                <?= htmlspecialchars($sharedProfileUploadMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="shared-profile-details">
            <div class="shared-profile-detail"><small>Full Name</small><strong><?= htmlspecialchars($sharedProfileName, ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div class="shared-profile-detail"><small>Email Address</small><strong><?= htmlspecialchars($sharedProfileEmail !== '' ? $sharedProfileEmail : 'Not provided', ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div class="shared-profile-detail"><small>Account Role</small><strong><?= htmlspecialchars($sharedProfileRoleLabel, ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div class="shared-profile-detail"><small>Account ID</small><strong>#<?= $sharedProfileUserId ?></strong></div>
        </div>

        <form class="shared-profile-upload" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="shared_profile_upload_token" value="<?= htmlspecialchars($sharedProfileToken, ENT_QUOTES, 'UTF-8') ?>">
            <label for="sharedProfilePictureInput">Change Profile Picture</label>
            <input type="file" id="sharedProfilePictureInput" name="shared_profile_picture" accept="image/jpeg,image/png,image/webp" required>
            <button class="shared-profile-save" type="submit" name="shared_profile_upload" value="1">Save Profile Picture</button>
            <p>Accepted formats: JPG, PNG, or WEBP. Maximum file size: 2 MB.</p>
        </form>

        <div class="shared-profile-actions">
            <button class="shared-profile-cancel" type="button" onclick="closeSharedProfile()">Close</button>
        </div>
    </section>
</div>
