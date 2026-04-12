<?php $title = 'Login'; ?>
<div class="card narrow">
    <h2>Login</h2>
    <?php if (!empty($error)): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="index.php?page=login" class="stack-form">
        <?= csrf_field() ?>
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Sign In</button>
    </form><p class="muted" style="margin-top:12px"><a href="index.php?page=forgot_password">Forgot password?</a></p>
</div>
