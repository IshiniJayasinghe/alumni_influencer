<?= $this->include('layout/header') ?>

<?php
$name = $user['name'] ?? '';
$jobTitle = $user['job_title_now'] ?? '';
$bio = $user['bio'] ?? '';
$linkedin = $user['linkedin_url'] ?? '';
$profileImage = $user['profile_image'] ?? '';

$certifications = $certifications ?? [];
$qualifications = $qualifications ?? [];

$imageUrl = (!empty($profileImage) && file_exists(FCPATH . 'uploads/profile_images/' . $profileImage))
    ? base_url('uploads/profile_images/' . $profileImage)
    : base_url('uploads/profile_images/default.png');
?>

<style>
    body {
        margin: 0;
        background: linear-gradient(180deg, #1b0f1b 0%, #2a1320 100%);
        font-family: Arial, Helvetica, sans-serif;
    }

    .profile-page {
        min-height: calc(100vh - 90px);
        padding: 70px 20px 50px;
    }

    .profile-grid {
        max-width: 1300px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 260px 1fr 260px;
        gap: 28px;
        align-items: start;
    }

    .section-title {
        background: #f4cf10;
        color: #111;
        font-weight: 700;
        text-align: center;
        padding: 18px 12px;
        border-radius: 8px;
        font-size: 28px;
        margin-bottom: 18px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.25);
    }

    .side-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .cert-card {
        background: #f4cf10;
        color: #111;
        border-radius: 8px;
        padding: 10px 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.25);
    }

    .cert-small {
        font-size: 13px;
        text-align: center;
        margin-bottom: 8px;
    }

    .cert-main {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        font-size: 22px;
        font-weight: 700;
    }

    .offer-badge {
        background: #fff;
        color: #666;
        border-radius: 999px;
        padding: 8px 10px;
        min-width: 52px;
        text-align: center;
        font-size: 11px;
        line-height: 1.1;
        font-weight: 700;
    }

    .qual-card {
        background: #f4cf10;
        color: #111;
        border-radius: 8px;
        padding: 16px 14px;
        font-size: 20px;
        font-weight: 700;
        box-shadow: 0 4px 10px rgba(0,0,0,0.25);
    }

    .middle {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
    }

    .profile-pic-wrap {
        position: absolute;
        top: -58px;
        z-index: 5;
    }

    .profile-pic {
        width: 116px;
        height: 116px;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid #f4cf10;
        background: #ffffff;
        box-shadow: 0 6px 14px rgba(0,0,0,0.28);
    }

    .name-box {
        width: 100%;
        max-width: 580px;
        background: #f4cf10;
        color: #111;
        padding: 18px 14px;
        text-align: center;
        font-size: 40px;
        font-weight: 700;
        border-radius: 8px 8px 0 0;
        box-shadow: 0 4px 10px rgba(0,0,0,0.25);
        margin-top: 46px;
    }

    .main-card {
        width: 100%;
        max-width: 580px;
        background: #f7f7f7;
        border: 10px solid #d7d7d7;
        border-top: 0;
        border-radius: 0 0 16px 16px;
        padding: 35px 34px 30px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.28);
        min-height: 540px;
        color: #222;
    }

    .job-title {
        text-align: center;
        font-size: 28px;
        font-weight: 700;
        color: #555;
        margin-bottom: 30px;
    }

    .about-label {
        font-size: 34px;
        font-weight: 800;
        margin-bottom: 12px;
    }

    .about-text {
        font-size: 18px;
        line-height: 1.7;
        margin-bottom: 28px;
        color: #333;
    }

    .linkedin-row {
        font-size: 15px;
        font-weight: 700;
        word-break: break-word;
        margin-top: 16px;
    }

    .linkedin-row a {
        color: #3b5bcc;
        text-decoration: underline;
        font-weight: 600;
        font-size: 14px;
    }

    .empty-box {
        background: rgba(255,255,255,0.08);
        color: #ddd;
        border: 1px dashed rgba(255,255,255,0.25);
        border-radius: 8px;
        padding: 14px;
        font-size: 15px;
        text-align: center;
    }

    @media (max-width: 1100px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }

        .middle {
            margin-top: 20px;
        }
    }
</style>

<div class="profile-page">
    <div class="profile-grid">

        <div>
            <div class="section-title">Certifications &amp; Licences</div>

            <div class="side-list">
                <?php if (!empty($certifications)): ?>
                    <?php foreach ($certifications as $cert): ?>
                        <div class="cert-card">
                            <div class="cert-small">I Endorse this</div>
                            <div class="cert-main">
                                <span><?= esc($cert['certification_name'] ?? $cert['licence_name'] ?? '') ?></span>
                                <span class="offer-badge">OFFER</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-box">No certifications or licences added yet.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="middle">
            <div class="profile-pic-wrap">
                <img src="<?= esc($imageUrl) ?>" alt="Profile Picture" class="profile-pic">
            </div>

            <div class="name-box"><?= esc($name ?: 'Profile Name') ?></div>

            <div class="main-card">
                <div class="job-title"><?= esc($jobTitle ?: 'Current Job Title') ?></div>

                <div class="about-label">About:</div>
                <div class="about-text"><?= esc($bio ?: 'No bio added yet.') ?></div>

                <div class="linkedin-row">
                    Linkedin:
                    <?php if (!empty($linkedin)): ?>
                        <a href="<?= esc($linkedin) ?>" target="_blank"><?= esc($linkedin) ?></a>
                    <?php else: ?>
                        <span>No LinkedIn link added</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div>
            <div class="section-title">Qualifications</div>

            <div class="side-list">
                <?php if (!empty($qualifications)): ?>
                    <?php foreach ($qualifications as $q): ?>
                        <div class="qual-card">
                            <?= esc($q['degree_name'] ?? $q['course_name'] ?? '') ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-box">No qualifications added yet.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?= $this->include('layout/footer') ?>