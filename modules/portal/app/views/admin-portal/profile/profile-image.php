<div class="modal fade" id="profileImageModal" tabindex="-1" aria-labelledby="profileImageModalLabel" aria-hidden="true"
    style="z-index:99999;">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content"
            style="border:0;border-radius:16px;overflow:hidden;box-shadow:0 20px 50px rgba(0,0,0,.2);">
            <form action="index.php?url=update-profile-image" method="POST" enctype="multipart/form-data">

                <div
                    style="display:flex;justify-content:space-between;align-items:flex-start;padding:20px 22px;background:linear-gradient(135deg,#eff6ff,#fff);border-bottom:1px solid #e5e7eb;">
                    <div>
                        <small
                            style="display:block;margin-bottom:3px;color:#2563eb;font-size:10px;font-weight:700;letter-spacing:.1em;">PROFILE
                            PHOTO</small>
                        <h5 id="profileImageModalLabel" style="margin:0;color:#111827;font-size:19px;font-weight:700;">
                            Update Profile Photo</h5>
                        <p style="margin:4px 0 0;color:#6b7280;font-size:12px;">Choose an image for your profile.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        style="margin-top:2px;"></button>
                </div>

                <div style="padding:25px 22px;text-align:center;background:#fff;">
                    <div id="imagePreview"
                        style="width:120px;height:120px;margin:0 auto 18px;border-radius:50%;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#eff6ff;color:#2563eb;font-size:38px;border:3px solid #fff;box-shadow:0 0 0 1px #dbeafe,0 6px 20px rgba(0,0,0,.12);">
                        <i class="fas fa-user"></i>
                    </div>

                    <label for="profileImageInput"
                        style="display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:9px 17px;background:#2563eb;color:#fff;border:0;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 4px 10px rgba(37,99,235,.2);transition:.2s;">
                        <i class="fas fa-image"></i> Choose Image
                    </label>

                    <input type="file" id="profileImageInput" name="profile_image"
                        accept="image/jpeg,image/png,image/webp" hidden required>

                    <p style="margin:9px 0 0;color:#9ca3af;font-size:11px;">JPG, PNG or WEBP • Max 5MB</p>

                    <div id="selectedImageName" style="display:none;margin:10px auto 0;color:#166534;font-size:11px;">
                        <i class="fas fa-check-circle"></i> <span></span>
                    </div>
                </div>

                <div
                    style="display:flex;justify-content:flex-end;gap:8px;padding:15px 22px;background:#f8fafc;border-top:1px solid #e5e7eb;">
                    <button type="button" data-bs-dismiss="modal"
                        style="padding:9px 17px;background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:9px;font-size:13px;font-weight:500;cursor:pointer;">
                        Cancel
                    </button>
                    <button type="submit"
                        style="padding:9px 17px;background:#2563eb;color:#fff;border:0;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 4px 10px rgba(37,99,235,.2);">
                        <i class="fas fa-upload" style="margin-right:5px;"></i> Update Photo
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>