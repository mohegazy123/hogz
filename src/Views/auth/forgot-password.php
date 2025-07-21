<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Forgot Password</h4>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            Password reset instructions have been sent to your email.
                        </div>
                    <?php else: ?>
                        <p class="mb-4">Enter your email address and we will send you instructions to reset your password.</p>
                        
                        <?php if (!empty($errors['email'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= $errors['email'] ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="/forgot-password">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= $_POST['email'] ?? '' ?>" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Send Reset Link</button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <div class="mt-3 text-center">
                        <a href="/login">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>