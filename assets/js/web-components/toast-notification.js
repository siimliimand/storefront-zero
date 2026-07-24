/**
 * Toast Notification Web Component
 * Displays transient messages with auto-dismiss. Uses Light DOM for Tailwind inheritance.
 *
 * Usage: <toast-notification message="Item added" type="success"></toast-notification>
 *        <toast-notification message="Error occurred" type="error"></toast-notification>
 */
class ToastNotification extends HTMLElement {
	connectedCallback() {
		const message = this.getAttribute('message') || '';
		const type = this.getAttribute('type') || 'success';

		// Light DOM — no Shadow DOM, Tailwind classes apply directly.
		this.className =
			'fixed bottom-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white text-sm font-medium transition-opacity duration-300';

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
