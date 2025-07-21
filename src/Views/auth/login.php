<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Login</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors['auth'])): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= $errors['auth'] ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="/login">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control <?= !empty($errors['username']) ? 'is-invalid' : '' ?>" id="username" name="username" value="<?= $_POST['username'] ?? '' ?>">
                            <?php if (!empty($errors['username'])): ?>
                                <div class="invalid-feedback">
                                    <?= $errors['username'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password">
                            <?php if (!empty($errors['password'])): ?>
                                <div class="invalid-feedback">
                                    <?= $errors['password'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <a href="/forgot-password">Forgot Password?</a>
                    </div>
                    
                    <div class="mt-3 text-center">
                        Don't have an account? <a href="/register">Register</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>