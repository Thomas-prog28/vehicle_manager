import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['fileInput', 'status'];
    static values = { url: String };

    async scan() {
        const file = this.fileInputTarget.files[0];
        if (!file) return;

        if (!navigator.onLine) {
            if (this.hasStatusTarget) {
                this.statusTarget.textContent = 'Mode hors ligne : le document est prêt. Remplis les champs manuellement puis clique sur Enregistrer.';
                this.statusTarget.classList.remove('hidden');
            }
            return;
        }

        if (this.hasStatusTarget) {
            this.statusTarget.textContent = 'Analyse du document par l’IA en cours...';
            this.statusTarget.classList.remove('hidden');
        }

        const formData = new FormData();
        formData.append('document', file);

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                body: formData,
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                const rawError = (result.error || '').toLowerCase();
                let friendlyMessage = 'Impossible de lire automatiquement ce document. Tu peux saisir les champs manuellement.';

                if (response.status === 429 || rawError.includes('quota') || rawError.includes('rate limit') || rawError.includes('resource_exhausted')) {
                    friendlyMessage = 'Quota IA temporairement atteint (limite de requêtes). Réessaie dans 1 minute ou saisis les champs manuellement.';
                } else if (response.status === 422 || rawError.includes('lourd') || rawError.includes('format')) {
                    friendlyMessage = result.error;
                }

                throw new Error(friendlyMessage);
            }

            Object.entries(result).forEach(([key, value]) => {
                if (value === null || value === undefined) return;
                const field = this.element.querySelector(`[data-scan-field="${key}"]`);
                if (field) {
                    field.value = value;
                }
            });

            if (this.hasStatusTarget) {
                this.statusTarget.textContent = 'Document analysé avec succès. Vérifie les champs pré-remplis avant d’enregistrer.';
                this.statusTarget.classList.remove('hidden');
            }
        } catch (error) {
            if (this.hasStatusTarget) {
                this.statusTarget.textContent = error.message || 'Impossible de lire automatiquement ce document. Tu peux saisir les champs manuellement.';
                this.statusTarget.classList.remove('hidden');
            }
        }
    }
}
