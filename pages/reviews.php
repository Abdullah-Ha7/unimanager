<?php // Rendering only; pre-processing done in reviews_pre.php ?>
<section class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-warning text-white text-center rounded-top-4 py-3">
                        <h1 class="h3 mb-0"><?php echo lang('review_event'); ?>: <?php echo e($event['title'] ?? ''); ?></h1>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger text-center"><?php echo e($error); ?></div>
                        <?php endif; ?>

                        <?php if (!isset($event) || !$event): ?>
                            <div class="alert alert-warning text-center mb-0"><?php echo lang('event_not_found'); ?></div>
                        <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="event_id" value="<?php echo $event['id'] ?? 0; ?>">

                            <div class="mb-4 text-center">
                                <label class="form-label h5 mb-3"><?php echo lang('your_rating'); ?> <span class="text-danger">*</span></label>
                                <div class="star-rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                        <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> <?php echo lang('stars'); ?>">&#9733;</label>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="comment" class="form-label"><?php echo lang('your_comment'); ?> (<?php echo lang('optional'); ?>)</label>
                                <textarea name="comment" id="comment" class="form-control" rows="5" maxlength="500"></textarea>
                                <small class="form-text text-muted"><?php echo lang('max_500_chars'); ?></small>
                            </div>

                            <button type="submit" name="submit_review" class="btn btn-warning w-100">
                                <i class="bi bi-send"></i>
                                <?php echo lang('submit_review'); ?>
                            </button>
                        </form>
                        <?php endif; ?>

                        <div class="text-center mt-4">
                            <a href="?page=student_dashboard" class="text-decoration-none">
                                <i class="bi bi-arrow-left-circle"></i>
                                <?php echo lang('back_to_dashboard'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
