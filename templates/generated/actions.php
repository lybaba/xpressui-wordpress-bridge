<?php
// Generated from export/_partials/actions.j2. Do not edit manually.
if (!isset($__ctx) || !is_array($__ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><div
  class="template-submit-feedback"
  data-template-zone="submit_feedback"
  data-submit-feedback
  data-submit-feedback-state="idle"
  style="display:none;"
>
  <div class="template-submit-feedback-title"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'submit_feedback'), 'title')); ?></div>
  <div class="template-submit-feedback-message" data-submit-feedback-message><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'submit_feedback'), 'idle_message')); ?></div>
</div>
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'step_status'), 'enabled'))): ?>  <div class="template-step-actions" data-form-step-actions="true">
    <div class="template-step-actions-leading">
      <button type="button" data-step-action="back"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'navigation_labels'), 'previous')); ?></button>
    </div>
    <div class="template-step-actions-trailing">
      <button type="button" data-step-action="next"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'navigation_labels'), 'next')); ?></button>
    </div>
  </div>
<?php endif; ?><div class="template-submit-row" data-template-zone="submit_actions">
  <button type="submit" class="template-submit-btn"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'submit_label')); ?></button>
</div>
