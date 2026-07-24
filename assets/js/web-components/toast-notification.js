/**
 * Toast Notification Web Component
 * Displays transient messages with auto-dismiss. Uses Light DOM for Tailwind inheritance.
 * Multiple toasts stack vertically via a shared container (singleton pattern).
 *
 * Usage: <toast-notification message="Item added" type="success"></toast-notification>
 *        <toast-notification message="Error occurred" type="error"></toast-notification>
 */
const TOAST_CONTAINER_ID = 'toast-notification-container';

function getToastContainer() {
	let container = document.getElementById(TOAST_CONTAINER_ID);
	if (!container) {
		container = document.createElement('div');
		container.id = TOAST_CONTAINER_ID;
		container.className =
			'fixed bottom-4 right-4 z-50 flex flex-col gap-2';
		document.body.appendChild(container);
	}
	return container;
}

class ToastNotification extends HTMLElement {
	connectedCallback() {
		const message = this.getAttribute('message') || '';
		const type = this.getAttribute('type') || 'success';

		// Move this element into the shared stacking container.
		const container = getToastContainer();
		if (this.parentElement !== container) {
			container.appendChild(this);
		}

		// Light DOM — no Shadow DOM, Tailwind classes apply directly.
		// Positioning is handled by the container; toasts stack inside it.
		this.className =
			'pointer-events-auto px-4 py-3 rounded-lg shadow-lg text-white text-sm font-medium transition-opacity duration-300';
		this.setAttribute('role', 'status');
		this.setAttribute('aria-live', 'polite');

		if (type === 'error') {
			this.classList.add('bg-red-600');
		} else {
			this.classList.add('bg-green-600');
		}

		this.textContent = message;

		// Auto-dismiss after 3 seconds with fade-out.
		setTimeout(() => {
			this.style.opacity = '0';
			setTimeout(() => this.remove(), 300);
		}, 3000);
	}
}

customElements.define('toast-notification', ToastNotification);
