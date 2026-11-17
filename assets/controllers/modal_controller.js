import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['dialog'];
    static values = { open: Boolean };

    connect() {
        if (this.openValue) {
            this.open();
        }
    }

    open() {
        this.dialogTarget.classList.remove('hidden');
        this.dialogTarget.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    close() {
        this.dialogTarget.classList.add('hidden');
        this.dialogTarget.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    closeOnBackdrop(event) {
        if (event.target === this.dialogTarget) {
            this.close();
        }
    }

    closeOnEscape(event) {
        if (event.key === 'Escape') {
            this.close();
        }
    }
}
