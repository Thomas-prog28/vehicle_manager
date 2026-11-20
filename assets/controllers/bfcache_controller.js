import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.onPageShow = (event) => {
            if (event.persisted) {
                document.body.style.display = 'none';
                window.location.replace('/');
            }
        };
        window.addEventListener('pageshow', this.onPageShow);
    }

    disconnect() {
        window.removeEventListener('pageshow', this.onPageShow);
    }
}
