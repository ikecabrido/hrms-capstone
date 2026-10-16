<?php
/**
 * Editable contact details. Writes to the employee record so other modules see
 * the update. HR-owned fields (email, position, department) stay read-only.
 * Expects $profileData.
 */
$contact = $profileData['contact'];
?>
<div class="mode-card">
    <h3 class="pfl-section-title"><i class="fas fa-address-book" aria-hidden="true"></i> Contact Information</h3>
    <form id="profile-contact-form" autocomplete="off" novalidate>
        <label class="pfl-field" for="profile-contact-mobile">
            <span>Mobile number</span>
            <input type="tel" inputmode="tel" class="pfl-input" id="profile-contact-mobile" name="mobile_no"
                   maxlength="20" placeholder="e.g. 0917 000 0000"
                   value="<?= htmlspecialchars($contact['mobile_no'], ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="pfl-field" for="profile-contact-phone">
            <span>Alternate phone</span>
            <input type="tel" inputmode="tel" class="pfl-input" id="profile-contact-phone" name="phone_no"
                   maxlength="20" placeholder="Optional landline"
                   value="<?= htmlspecialchars($contact['phone_no'], ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="pfl-field" for="profile-contact-address">
            <span>Current address</span>
            <textarea class="pfl-input" id="profile-contact-address" name="current_address" maxlength="500"
                      rows="3" placeholder="Where can we reach you?"><?= htmlspecialchars($contact['current_address'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>
        <div class="pfl-form-actions">
            <button type="submit" id="profile-contact-save" class="pfl-btn pfl-btn--primary">
                <i class="fas fa-floppy-disk" aria-hidden="true"></i> Save contact details
            </button>
        </div>
        <small class="pfl-hint">Email, position and department are maintained by HR and can't be edited here.</small>
    </form>
</div>
