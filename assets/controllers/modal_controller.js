import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['dialog', 'docTitle', 'docImage', 'docPdf', 'docDownload'];
    static values = { open: Boolean };

    connect() {
        if (this.openValue && this.hasDialogTarget) {
            this.showDialog(this.dialogTarget);
        }
    }

    open(event) {
        if (event) event.preventDefault();

        const button = event?.currentTarget;
        const targetId = button?.dataset.modalIdParam;
        const dialog = targetId
            ? this.dialogTargets.find((el) => el.id === targetId)
            : this.dialogTarget;

        if (!dialog) return;

        // Si le bouton transporte un document (facture), on le charge avant d'ouvrir
        if (button?.dataset.url) {
            const url = button.dataset.url;
            const title = button.dataset.title || 'Aperçu du document';
            const isImage = /\.(jpe?g|png|webp|gif)$/i.test(url);

            if (this.hasDocTitleTarget) this.docTitleTarget.textContent = title;
            if (this.hasDocDownloadTarget) this.docDownloadTarget.href = url;

            if (isImage) {
                this.docPdfTarget?.classList.add('hidden');
                if (this.hasDocPdfTarget) this.docPdfTarget.src = '';
                if (this.hasDocImageTarget) {
                    this.docImageTarget.src = url;
                    this.docImageTarget.classList.remove('hidden');
                }
            } else {
                this.docImageTarget?.classList.add('hidden');
                if (this.hasDocImageTarget) this.docImageTarget.src = '';
                if (this.hasDocPdfTarget) {
                    this.docPdfTarget.src = url;
                    this.docPdfTarget.classList.remove('hidden');
                }
            }
        }

        this.showDialog(dialog);
    }

    close(event) {
        const dialog = event?.target?.closest('[data-modal-target="dialog"]')
            || this.dialogTargets.find((el) => !el.classList.contains('hidden'));

        if (!dialog) return;

        dialog.classList.add('hidden');
        dialog.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');

        if (this.hasDocPdfTarget) this.docPdfTarget.src = '';
        if (this.hasDocImageTarget) this.docImageTarget.src = '';
    }

    closeOnBackdrop(event) {
        if (event.target.dataset.modalTarget === 'dialog') {
            this.close(event);
        }
    }

    closeOnEscape(event) {
        if (event.key === 'Escape') {
            this.close();
        }
    }

    showDialog(dialog) {
        dialog.classList.remove('hidden');
        dialog.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }
}
