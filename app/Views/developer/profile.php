<?= $this->include('layout/header') ?>

<style>
body {
    background: #1b0f1b;
    color: white;
}

.container {
    display: flex;
    justify-content: space-between;
    padding: 30px;
}

.left, .right {
    width: 25%;
}

.center {
    width: 40%;
    text-align: center;
}

.yellow {
    background: #facc15;
    color: black;
    padding: 15px;
    border-radius: 8px;
    font-weight: bold;
    margin-bottom: 15px;
}

.card {
    background: white;
    color: black;
    padding: 20px;
    border-radius: 12px;
}
</style>

<div class="container">

    <!-- LEFT -->
    <div class="left">
        <div class="yellow">Certifications & Licences</div>

        <?php foreach ($certifications as $c): ?>
            <div class="yellow"><?= $c['title'] ?? 'AWS' ?></div>
        <?php endforeach; ?>
    </div>

    <!-- CENTER -->
    <div class="center">
        <div class="yellow" style="font-size:30px;">
            <?= $user['name'] ?? 'John Doe' ?>
        </div>

        <div class="card">
            <h2><?= $user['job_title_now'] ?? 'DevOps Engineer @ AMZ' ?></h2>

            <p><b>About:</b><br>
                <?= $user['bio'] ?? 'No bio yet' ?>
            </p>

            <p>
                <b>LinkedIn:</b><br>
                <?= $user['linkedin_url'] ?? '#' ?>
            </p>
        </div>
    </div>

    <!-- RIGHT -->
    <div class="right">
        <div class="yellow">Qualifications</div>

        <?php foreach ($qualifications as $q): ?>
            <div class="yellow"><?= $q['degree_name'] ?? 'Degree' ?></div>
        <?php endforeach; ?>
    </div>

</div>

<?= $this->include('layout/footer') ?>