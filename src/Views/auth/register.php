<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Register</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors['register'])): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= $errors['register'] ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="/register">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control <?= !empty($errors['first_name']) ? 'is-invalid' : '' ?>" id="first_name" name="first_name" value="<?= $_POST['first_name'] ?? '' ?>">
                                <?php if (!empty($errors['first_name'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['first_name'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control <?= !empty($errors['last_name']) ? 'is-invalid' : '' ?>" id="last_name" name="last_name" value="<?= $_POST['last_name'] ?? '' ?>">
                                <?php if (!empty($errors['last_name'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['last_name'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
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
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= $_POST['email'] ?? '' ?>">
                            <?php if (!empty($errors['email'])): ?>
                                <div class="invalid-feedback">
                                    <?= $errors['email'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password">
                                <?php if (!empty($errors['password'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['password'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control <?= !empty($errors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password">
                                <?php if (!empty($errors['confirm_password'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['confirm_password'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Register</button>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        Already have an account? <a href="/login">Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>