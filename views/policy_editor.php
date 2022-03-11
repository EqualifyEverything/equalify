<h1>Policy Editor</h1>
<form>
    <div class="mb-3">
        <label for="policy_name" class="form-label">Policy Name</label>
        <input name="policy_name" type="text" class="form-control">
    </div>
    <p>Policy Settings</p>
    <div class="d-flex align-items-center">
        <div class="">
            <select class="form-select" aria-label="Policy Action">
                <option selected>Action</option>
                <option value="set_status_to_angry_emoji">Set Status to 😡</option>
                <option value="set_status_to_top_hat_emoji">Set Status to 🎩</option>
                <option value="set_status_to_celebration_emoji">Set Status to ✅</option>
                <option value="email_me">Email Me</option>
                <option value="[wp_action]">[Action from WP API]</option>
            </select>
        </div>
        <div class="text-center px-4">
            when
        </div>
        <div class="">
            <select class="form-select" aria-label="Policy Event">
                <option selected>Event</option>
                <option value="site_wcag_page_errors">Site WCAG Page Errors</option>
                <option value="days_since_last_login">Days Since Last Login</option>
                <option value="[wp_event]">[Event From WP API]</option>
                <option value="[accessibility_event]">[Event From Accessibility API]</option>
            </select>
        </div>
        <div class="text-center px-4"">
            is
        </div>
        <div class="">
            <select class="form-select" aria-label="Policy Operator">
                <option selected>Operator</option>
                <option value="greater_than">Greater Than</option>
                <option value="less_than">Less Than</option>
                <option value="equal_to">Equal To</option>
            </select>
        </div>
        <div class="ps-4">
            <input class="form-control" type="text" placeholder="value">
        </div>
        <div class="text-center ps-2">
            .
        </div>
    </div>
    <button type="submit" class="btn btn-primary my-4">Save Policy</button>
</form>
<hr>
<form>
    <button type="submit" class="btn btn-outline-danger mt-4">Perminently Delete Policy</button>
    <div class="form-text">This action cannot be undone.</div>
</form>