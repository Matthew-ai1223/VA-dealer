<?php
/** Lead capture modal — included on pages with car listings */
if (!function_exists('url')) {
    require_once __DIR__ . '/../../Backend/lib/helpers.php';
}
if (!isset($config)) {
    $config = appConfig();
}
$leadApiUrl = url('Backend/api/leads.php');
?>
<div class="lead-modal" id="lead-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="lead-modal-title" data-api-url="<?= sanitize($leadApiUrl) ?>">
    <button type="button" class="lead-modal__backdrop" aria-label="Close form"></button>
    <div class="lead-modal__dialog">
        <button type="button" class="lead-modal__close" id="lead-modal-close" aria-label="Close">&times;</button>

        <div class="lead-modal__body" id="lead-modal-body">
            <div class="lead-modal__header">
                <h2 id="lead-modal-title">Interested in this car?</h2>
                <p class="lead-modal__subtitle" id="lead-modal-subtitle">Fill in your details — then continue on WhatsApp to send your inquiry.</p>
                <p class="lead-modal__vehicle" id="lead-modal-vehicle" hidden></p>
            </div>

            <div class="lead-forms__tabs" role="tablist" aria-label="Lead request types">
                <button type="button" class="lead-forms__tab is-active" role="tab" aria-selected="true" data-tab="request_info">Request Info</button>
                <button type="button" class="lead-forms__tab" role="tab" aria-selected="false" data-tab="book_inspection">Book Inspection</button>
                <button type="button" class="lead-forms__tab" role="tab" aria-selected="false" data-tab="request_callback">Request Callback</button>
            </div>

            <div class="lead-forms__panels">
                <form class="lead-form is-active" id="lead-form-info" data-inquiry-type="request_info" novalidate>
                    <input type="hidden" name="car_id" class="js-lead-car-id" value="">
                    <input type="hidden" name="interested_vehicle" class="js-lead-vehicle" value="">
                    <input type="hidden" name="inquiry_type" value="request_info">
                    <div class="lead-form__grid">
                        <div class="lead-form__field">
                            <label for="info-name">Full Name <span class="lead-form__required">*</span></label>
                            <input type="text" id="info-name" name="full_name" required maxlength="120" autocomplete="name" placeholder="John Doe">
                        </div>
                        <div class="lead-form__field">
                            <label for="info-phone">Phone Number <span class="lead-form__required">*</span></label>
                            <input type="tel" id="info-phone" name="phone_number" required maxlength="30" autocomplete="tel" placeholder="0801 234 5678">
                        </div>
                        <div class="lead-form__field">
                            <label for="info-email">Email</label>
                            <input type="email" id="info-email" name="email" maxlength="120" autocomplete="email" placeholder="you@email.com">
                        </div>
                        <div class="lead-form__field">
                            <label for="info-budget">Budget (₦)</label>
                            <input type="number" id="info-budget" name="budget" min="0" step="100000" placeholder="Optional">
                        </div>
                        <div class="lead-form__field lead-form__field--full">
                            <label for="info-message">Message</label>
                            <textarea id="info-message" name="message" rows="3" placeholder="Any questions about this vehicle?"></textarea>
                        </div>
                    </div>
                    <div class="lead-form__actions">
                        <button type="submit" class="btn btn--primary btn--lg lead-form__submit">Submit &amp; Continue on WhatsApp</button>
                        <p class="lead-form__feedback" role="status" aria-live="polite"></p>
                    </div>
                </form>

                <form class="lead-form" id="lead-form-inspection" data-inquiry-type="book_inspection" hidden novalidate>
                    <input type="hidden" name="car_id" class="js-lead-car-id" value="">
                    <input type="hidden" name="interested_vehicle" class="js-lead-vehicle" value="">
                    <input type="hidden" name="inquiry_type" value="book_inspection">
                    <div class="lead-form__grid">
                        <div class="lead-form__field">
                            <label for="inspect-name">Full Name <span class="lead-form__required">*</span></label>
                            <input type="text" id="inspect-name" name="full_name" required maxlength="120" placeholder="John Doe">
                        </div>
                        <div class="lead-form__field">
                            <label for="inspect-phone">Phone Number <span class="lead-form__required">*</span></label>
                            <input type="tel" id="inspect-phone" name="phone_number" required maxlength="30" placeholder="0801 234 5678">
                        </div>
                        <div class="lead-form__field lead-form__field--full">
                            <label for="inspect-date">Preferred Date / Time</label>
                            <input type="text" id="inspect-date" name="message" placeholder="e.g. Saturday 10am">
                        </div>
                    </div>
                    <div class="lead-form__actions">
                        <button type="submit" class="btn btn--primary btn--lg lead-form__submit">Submit &amp; Continue on WhatsApp</button>
                        <p class="lead-form__feedback" role="status" aria-live="polite"></p>
                    </div>
                </form>

                <form class="lead-form" id="lead-form-callback" data-inquiry-type="request_callback" hidden novalidate>
                    <input type="hidden" name="car_id" class="js-lead-car-id" value="">
                    <input type="hidden" name="interested_vehicle" class="js-lead-vehicle" value="">
                    <input type="hidden" name="inquiry_type" value="request_callback">
                    <div class="lead-form__grid">
                        <div class="lead-form__field">
                            <label for="callback-name">Full Name <span class="lead-form__required">*</span></label>
                            <input type="text" id="callback-name" name="full_name" required maxlength="120" placeholder="John Doe">
                        </div>
                        <div class="lead-form__field">
                            <label for="callback-phone">Phone Number <span class="lead-form__required">*</span></label>
                            <input type="tel" id="callback-phone" name="phone_number" required maxlength="30" placeholder="0801 234 5678">
                        </div>
                        <div class="lead-form__field lead-form__field--full">
                            <label for="callback-time">Best Time to Call</label>
                            <input type="text" id="callback-time" name="message" placeholder="e.g. Weekdays after 5pm">
                        </div>
                    </div>
                    <div class="lead-form__actions">
                        <button type="submit" class="btn btn--primary btn--lg lead-form__submit">Submit &amp; Continue on WhatsApp</button>
                        <p class="lead-form__feedback" role="status" aria-live="polite"></p>
                    </div>
                </form>
            </div>
        </div>

        <div class="lead-modal__success" id="lead-modal-success" hidden>
            <div class="lead-modal__success-icon" aria-hidden="true">✓</div>
            <h3>Request saved!</h3>
            <p>Please continue on WhatsApp and tap <strong>Send</strong> to deliver your inquiry to our team.</p>
            <p class="lead-modal__success-note">Your message will include your form details and: <em>I'm interested in [car name] priced at [price]</em></p>
            <a href="#" class="btn btn--whatsapp btn--lg btn--block" id="lead-modal-whatsapp" target="_blank" rel="noopener">Continue on WhatsApp</a>
            <button type="button" class="btn btn--outline btn--block" id="lead-modal-done">Close</button>
        </div>
    </div>
</div>
