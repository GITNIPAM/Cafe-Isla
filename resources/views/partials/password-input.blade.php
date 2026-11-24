<div class="password-field-wrap">
    <input
        type="password"
        @if(!empty($id)) id="{{ $id }}" @endif
        name="{{ $name ?? 'password' }}"
        class="password-field-wrap__input"
        @if(!empty($placeholder)) placeholder="{{ $placeholder }}" @endif
        @if(!empty($minlength)) minlength="{{ $minlength }}" @endif
        @if(!isset($required) || $required) required @endif
    >
    <button
        type="button"
        class="password-toggle-btn"
        data-password-toggle
        aria-label="Show password"
        aria-pressed="false"
    >
        <svg class="password-toggle-btn__icon-show" width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path d="M1.5 8C3.1 4.9 5.3 3.5 8 3.5C10.7 3.5 12.9 4.9 14.5 8C12.9 11.1 10.7 12.5 8 12.5C5.3 12.5 3.1 11.1 1.5 8Z"/>
            <circle cx="8" cy="8" r="2"/>
        </svg>
        <svg class="password-toggle-btn__icon-hide" width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" style="display:none;">
            <path d="M1.5 8C3.1 4.9 5.3 3.5 8 3.5C10.7 3.5 12.9 4.9 14.5 8C12.9 11.1 10.7 12.5 8 12.5C5.3 12.5 3.1 11.1 1.5 8Z"/>
            <path d="M2.5 2.5L13.5 13.5"/>
        </svg>
        <span data-password-toggle-label>Show</span>
    </button>
</div>
