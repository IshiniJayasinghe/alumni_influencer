<?= $this->include('layout/header') ?>

<?php $user = $user ?? []; ?>

<style>
    body { margin:0; font-family:Arial,Helvetica,sans-serif; background:linear-gradient(180deg,#0f172a,#1e293b); color:white; }
    .manage-wrap { max-width:1200px; margin:35px auto; padding:20px; }
    .manage-card { background:#1e293b; border-radius:18px; padding:28px; box-shadow:0 8px 20px rgba(0,0,0,.25); margin-bottom:24px; }
    .manage-title { color:#facc15; font-size:34px; font-weight:700; margin-bottom:20px; }
    .sub-title { color:#38bdf8; font-size:24px; font-weight:700; margin-bottom:16px; }
    .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
    .field-group { margin-bottom:16px; }
    .field-group label { display:block; font-weight:700; margin-bottom:8px; }
    .field-group input, .field-group textarea, .field-group select {
        width:100%; box-sizing:border-box; border:none; border-radius:12px; padding:14px; font-size:16px;
    }
    .field-group textarea { min-height:120px; resize:vertical; }
    .drop-zone { border:2px dashed #facc15; border-radius:16px; background:rgba(250,204,21,.08); padding:24px; text-align:center; cursor:pointer; transition:.2s ease; }
    .drop-zone.dragover { background:rgba(250,204,21,.18); transform:scale(1.01); }
    .preview-box { margin-top:14px; text-align:center; }
    .preview-box img { width:110px; height:110px; object-fit:cover; border-radius:50%; border:4px solid #facc15; background:white; }
    .btn { background:#facc15; color:black; border:none; border-radius:12px; padding:12px 20px; font-size:15px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; }
    .btn-remove { background:#ef4444; color:white; border:none; border-radius:12px; padding:12px 20px; font-size:15px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; margin-top:10px; }
    .btn-delete { background:#ef4444; color:white; text-decoration:none; padding:6px 10px; border-radius:8px; font-size:13px; font-weight:700; }
    .btn-edit   { background:#3b82f6; color:white; text-decoration:none; padding:6px 10px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; border:none; }
    .msg { border-radius:12px; padding:14px; margin-bottom:18px; }
    .msg-success { background:#166534; }
    .msg-error   { background:#991b1b; }
    .list-box { margin-top:16px; background:#0f172a; border-radius:14px; padding:16px; }
    .list-item { border-bottom:1px solid rgba(255,255,255,.12); padding:10px 0; }
    .list-item:last-child { border-bottom:none; }
    .list-item-row { display:flex; justify-content:space-between; align-items:center; gap:10px; }
    .small-text { font-size:13px; color:#cbd5e1; }
    .edit-form { display:none; background:#1e3a5f; border-radius:12px; padding:14px; margin-top:10px; }
    .edit-form.open { display:block; }
    .edit-form .field-group input { background:#f1f5f9; color:#0f172a; }
    @media (max-width:850px) { .grid-2 { grid-template-columns:1fr; } }
</style>

<div class="manage-wrap">

    <div class="manage-card">
        <div class="manage-title">Manage Profile</div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="msg msg-success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="msg msg-error"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <form action="<?= base_url('profile/update') ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="grid-2">
                <div>
                    <div class="field-group"><label>Full Name</label><input type="text" name="name" value="<?= esc($user['name'] ?? '') ?>"></div>
                    <div class="field-group"><label>Current Job Title</label><input type="text" name="job_title_now" value="<?= esc($user['job_title_now'] ?? '') ?>"></div>
                    <div class="field-group"><label>LinkedIn URL</label><input type="url" name="linkedin_url" value="<?= esc($user['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/in/yourprofile"></div>
                    <div class="field-group"><label>About / Bio</label><textarea name="bio"><?= esc($user['bio'] ?? '') ?></textarea></div>
                </div>
                <div>
                    <label>Profile Picture</label>
                    <div class="drop-zone" id="dropZone">
                        <p><strong>Drag & drop your profile picture here</strong></p>
                        <p>or click to choose an image</p>
                        <p class="small-text">Allowed: JPG, JPEG, PNG, WEBP</p>
                        <input type="file" name="profile_image" id="profileImageInput" accept=".jpg,.jpeg,.png,.webp" hidden>
                    </div>
                    <div class="preview-box">
                        <?php
                        $manageImage = (!empty($user['profile_image']) && file_exists(FCPATH . 'uploads/profile_images/' . $user['profile_image']))
                            ? base_url('uploads/profile_images/' . $user['profile_image'])
                            : base_url('uploads/profile_images/default.png');
                        ?>
                        <img id="previewImage" src="<?= esc($manageImage) ?>" alt="Profile Preview">
                    </div>
                    <?php if (!empty($user['profile_image'])): ?>
                        <a class="btn-remove" href="<?= base_url('profile/remove-image') ?>">Remove Profile Picture</a>
                    <?php endif; ?>
                </div>
            </div>
            <div style="margin-top:20px;"><button type="submit" class="btn">Save Profile</button></div>
        </form>
    </div>

    <div class="grid-2">

        <!-- ── Certifications ─────────────────────────────────────────────── -->
        <div class="manage-card">
            <div class="sub-title">Certifications</div>
            <form action="<?= base_url('profile/add-certification') ?>" method="post">
                <?= csrf_field() ?>
                <div class="field-group"><label>Certification Name *</label><input type="text" name="certification_name" required></div>
                <div class="field-group"><label>Organisation</label><input type="text" name="organisation_name"></div>
                <div class="field-group"><label>Course URL</label><input type="url" name="course_url" placeholder="https://..."></div>
                <div class="field-group"><label>Completion Date</label><input type="date" name="completion_date"></div>
                <button type="submit" class="btn">Add Certification</button>
            </form>

            <div class="list-box">
                <?php foreach ($certifications as $item): ?>
                    <div class="list-item">
                        <div class="list-item-row">
                            <div>
                                <strong><?= esc($item['certification_name'] ?? '') ?></strong><br>
                                <span class="small-text">
                                    <?= esc($item['organisation_name'] ?? '') ?>
                                    <?php if (!empty($item['completion_date'])): ?> – <?= esc($item['completion_date']) ?><?php endif; ?>
                                    <?php if (!empty($item['course_url'])): ?> &nbsp;<a href="<?= esc($item['course_url']) ?>" target="_blank" rel="noopener" style="color:#38bdf8;">Link</a><?php endif; ?>
                                </span>
                            </div>
                            <div style="display:flex;gap:6px;">
                                <button class="btn-edit" onclick="toggleEdit('cert-<?= $item['id'] ?>')">Edit</button>
                                <a class="btn-delete" href="<?= base_url('profile/delete-certification/' . $item['id']) ?>">Delete</a>
                            </div>
                        </div>
                        <div class="edit-form" id="edit-cert-<?= $item['id'] ?>">
                            <form action="<?= base_url('profile/edit-certification/' . $item['id']) ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="field-group"><label>Name *</label><input type="text" name="certification_name" value="<?= esc($item['certification_name'] ?? '') ?>" required></div>
                                <div class="field-group"><label>Organisation</label><input type="text" name="organisation_name" value="<?= esc($item['organisation_name'] ?? '') ?>"></div>
                                <div class="field-group"><label>Course URL</label><input type="url" name="course_url" value="<?= esc($item['course_url'] ?? '') ?>"></div>
                                <div class="field-group"><label>Completion Date</label><input type="date" name="completion_date" value="<?= esc($item['completion_date'] ?? '') ?>"></div>
                                <button type="submit" class="btn">Save</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ── Licences ───────────────────────────────────────────────────── -->
        <div class="manage-card">
            <div class="sub-title">Professional Licences</div>
            <form action="<?= base_url('profile/add-licence') ?>" method="post">
                <?= csrf_field() ?>
                <div class="field-group"><label>Licence Name *</label><input type="text" name="licence_name" required></div>
                <div class="field-group"><label>Awarding Body</label><input type="text" name="awarding_body"></div>
                <div class="field-group"><label>Official URL</label><input type="url" name="official_url" placeholder="https://..."></div>
                <div class="field-group"><label>Completion Date</label><input type="date" name="completion_date"></div>
                <button type="submit" class="btn">Add Licence</button>
            </form>

            <div class="list-box">
                <?php foreach ($licences as $item): ?>
                    <div class="list-item">
                        <div class="list-item-row">
                            <div>
                                <strong><?= esc($item['licence_name'] ?? '') ?></strong><br>
                                <span class="small-text">
                                    <?= esc($item['awarding_body'] ?? '') ?>
                                    <?php if (!empty($item['completion_date'])): ?> – <?= esc($item['completion_date']) ?><?php endif; ?>
                                    <?php if (!empty($item['official_url'])): ?> &nbsp;<a href="<?= esc($item['official_url']) ?>" target="_blank" rel="noopener" style="color:#38bdf8;">Link</a><?php endif; ?>
                                </span>
                            </div>
                            <div style="display:flex;gap:6px;">
                                <button class="btn-edit" onclick="toggleEdit('lic-<?= $item['id'] ?>')">Edit</button>
                                <a class="btn-delete" href="<?= base_url('profile/delete-licence/' . $item['id']) ?>">Delete</a>
                            </div>
                        </div>
                        <div class="edit-form" id="edit-lic-<?= $item['id'] ?>">
                            <form action="<?= base_url('profile/edit-licence/' . $item['id']) ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="field-group"><label>Name *</label><input type="text" name="licence_name" value="<?= esc($item['licence_name'] ?? '') ?>" required></div>
                                <div class="field-group"><label>Awarding Body</label><input type="text" name="awarding_body" value="<?= esc($item['awarding_body'] ?? '') ?>"></div>
                                <div class="field-group"><label>Official URL</label><input type="url" name="official_url" value="<?= esc($item['official_url'] ?? '') ?>"></div>
                                <div class="field-group"><label>Completion Date</label><input type="date" name="completion_date" value="<?= esc($item['completion_date'] ?? '') ?>"></div>
                                <button type="submit" class="btn">Save</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ── Degrees ────────────────────────────────────────────────────── -->
        <div class="manage-card">
            <div class="sub-title">Degrees</div>
            <form action="<?= base_url('profile/add-degree') ?>" method="post">
                <?= csrf_field() ?>
                <div class="field-group"><label>Degree Name *</label><input type="text" name="degree_name" required></div>
                <div class="field-group"><label>Institution</label><input type="text" name="institution_name"></div>
                <div class="field-group"><label>Official URL</label><input type="url" name="official_url" placeholder="https://..."></div>
                <div class="field-group"><label>Completion Date</label><input type="date" name="completion_date"></div>
                <button type="submit" class="btn">Add Degree</button>
            </form>

            <div class="list-box">
                <?php foreach ($degrees as $item): ?>
                    <div class="list-item">
                        <div class="list-item-row">
                            <div>
                                <strong><?= esc($item['degree_name'] ?? '') ?></strong><br>
                                <span class="small-text">
                                    <?= esc($item['institution_name'] ?? '') ?>
                                    <?php if (!empty($item['completion_date'])): ?> – <?= esc($item['completion_date']) ?><?php endif; ?>
                                    <?php if (!empty($item['official_url'])): ?> &nbsp;<a href="<?= esc($item['official_url']) ?>" target="_blank" rel="noopener" style="color:#38bdf8;">Link</a><?php endif; ?>
                                </span>
                            </div>
                            <div style="display:flex;gap:6px;">
                                <button class="btn-edit" onclick="toggleEdit('deg-<?= $item['id'] ?>')">Edit</button>
                                <a class="btn-delete" href="<?= base_url('profile/delete-degree/' . $item['id']) ?>">Delete</a>
                            </div>
                        </div>
                        <div class="edit-form" id="edit-deg-<?= $item['id'] ?>">
                            <form action="<?= base_url('profile/edit-degree/' . $item['id']) ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="field-group"><label>Name *</label><input type="text" name="degree_name" value="<?= esc($item['degree_name'] ?? '') ?>" required></div>
                                <div class="field-group"><label>Institution</label><input type="text" name="institution_name" value="<?= esc($item['institution_name'] ?? '') ?>"></div>
                                <div class="field-group"><label>Official URL</label><input type="url" name="official_url" value="<?= esc($item['official_url'] ?? '') ?>"></div>
                                <div class="field-group"><label>Completion Date</label><input type="date" name="completion_date" value="<?= esc($item['completion_date'] ?? '') ?>"></div>
                                <button type="submit" class="btn">Save</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ── Short Courses ──────────────────────────────────────────────── -->
        <div class="manage-card">
            <div class="sub-title">Short Courses</div>
            <form action="<?= base_url('profile/add-course') ?>" method="post">
                <?= csrf_field() ?>
                <div class="field-group"><label>Course Name *</label><input type="text" name="course_name" required></div>
                <div class="field-group"><label>Provider</label><input type="text" name="provider_name"></div>
                <div class="field-group"><label>Course URL</label><input type="url" name="course_url" placeholder="https://..."></div>
                <div class="field-group"><label>Completion Date</label><input type="date" name="completion_date"></div>
                <button type="submit" class="btn">Add Course</button>
            </form>

            <div class="list-box">
                <?php foreach ($courses as $item): ?>
                    <div class="list-item">
                        <div class="list-item-row">
                            <div>
                                <strong><?= esc($item['course_name'] ?? '') ?></strong><br>
                                <span class="small-text">
                                    <?= esc($item['provider_name'] ?? '') ?>
                                    <?php if (!empty($item['completion_date'])): ?> – <?= esc($item['completion_date']) ?><?php endif; ?>
                                    <?php if (!empty($item['course_url'])): ?> &nbsp;<a href="<?= esc($item['course_url']) ?>" target="_blank" rel="noopener" style="color:#38bdf8;">Link</a><?php endif; ?>
                                </span>
                            </div>
                            <div style="display:flex;gap:6px;">
                                <button class="btn-edit" onclick="toggleEdit('crs-<?= $item['id'] ?>')">Edit</button>
                                <a class="btn-delete" href="<?= base_url('profile/delete-course/' . $item['id']) ?>">Delete</a>
                            </div>
                        </div>
                        <div class="edit-form" id="edit-crs-<?= $item['id'] ?>">
                            <form action="<?= base_url('profile/edit-course/' . $item['id']) ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="field-group"><label>Name *</label><input type="text" name="course_name" value="<?= esc($item['course_name'] ?? '') ?>" required></div>
                                <div class="field-group"><label>Provider</label><input type="text" name="provider_name" value="<?= esc($item['provider_name'] ?? '') ?>"></div>
                                <div class="field-group"><label>Course URL</label><input type="url" name="course_url" value="<?= esc($item['course_url'] ?? '') ?>"></div>
                                <div class="field-group"><label>Completion Date</label><input type="date" name="completion_date" value="<?= esc($item['completion_date'] ?? '') ?>"></div>
                                <button type="submit" class="btn">Save</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ── Employment History ─────────────────────────────────────────── -->
        <div class="manage-card" style="grid-column:1/-1;">
            <div class="sub-title">Employment History</div>
            <form action="<?= base_url('profile/add-employment') ?>" method="post">
                <?= csrf_field() ?>
                <div class="grid-2">
                    <div class="field-group"><label>Company Name *</label><input type="text" name="company_name" required></div>
                    <div class="field-group"><label>Job Title *</label><input type="text" name="job_title" required></div>
                    <div class="field-group"><label>Start Date</label><input type="date" name="start_date"></div>
                    <div class="field-group"><label>End Date</label><input type="date" name="end_date"></div>
                </div>
                <div class="field-group"><label>Description</label><textarea name="description"></textarea></div>
                <div class="field-group">
                    <label><input type="checkbox" name="is_current" value="1" style="width:auto;margin-right:8px;"> I currently work here</label>
                </div>
                <button type="submit" class="btn">Add Employment</button>
            </form>

            <div class="list-box">
                <?php foreach ($employment as $item): ?>
                    <div class="list-item">
                        <div class="list-item-row">
                            <div>
                                <strong><?= esc($item['company_name'] ?? '') ?></strong> – <?= esc($item['job_title'] ?? '') ?><br>
                                <span class="small-text">
                                    <?= esc($item['start_date'] ?? '') ?> to
                                    <?= !empty($item['is_current']) ? 'Present' : esc($item['end_date'] ?? '') ?>
                                </span>
                            </div>
                            <div style="display:flex;gap:6px;">
                                <button class="btn-edit" onclick="toggleEdit('emp-<?= $item['id'] ?>')">Edit</button>
                                <a class="btn-delete" href="<?= base_url('profile/delete-employment/' . $item['id']) ?>">Delete</a>
                            </div>
                        </div>
                        <div class="edit-form" id="edit-emp-<?= $item['id'] ?>">
                            <form action="<?= base_url('profile/edit-employment/' . $item['id']) ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="grid-2">
                                    <div class="field-group"><label>Company *</label><input type="text" name="company_name" value="<?= esc($item['company_name'] ?? '') ?>" required></div>
                                    <div class="field-group"><label>Job Title *</label><input type="text" name="job_title" value="<?= esc($item['job_title'] ?? '') ?>" required></div>
                                    <div class="field-group"><label>Start Date</label><input type="date" name="start_date" value="<?= esc($item['start_date'] ?? '') ?>"></div>
                                    <div class="field-group"><label>End Date</label><input type="date" name="end_date" value="<?= esc($item['end_date'] ?? '') ?>"></div>
                                </div>
                                <div class="field-group"><label>Description</label><textarea name="description"><?= esc($item['description'] ?? '') ?></textarea></div>
                                <div class="field-group">
                                    <label><input type="checkbox" name="is_current" value="1" style="width:auto;margin-right:8px;" <?= !empty($item['is_current']) ? 'checked' : '' ?>> I currently work here</label>
                                </div>
                                <button type="submit" class="btn">Save</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- end grid-2 -->
</div><!-- end manage-wrap -->

<script>
    /* Toggle inline edit forms */
    function toggleEdit(key) {
        const el = document.getElementById('edit-' + key);
        if (el) el.classList.toggle('open');
    }

    /* Profile image drag-drop & preview */
    const dropZone     = document.getElementById('dropZone');
    const fileInput    = document.getElementById('profileImageInput');
    const previewImage = document.getElementById('previewImage');

    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) { fileInput.files = e.dataTransfer.files; showPreview(e.dataTransfer.files[0]); }
    });
    fileInput.addEventListener('change', () => { if (fileInput.files.length > 0) showPreview(fileInput.files[0]); });
    function showPreview(file) {
        const reader = new FileReader();
        reader.onload = e => previewImage.src = e.target.result;
        reader.readAsDataURL(file);
    }
</script>

<?= $this->include('layout/footer') ?>
