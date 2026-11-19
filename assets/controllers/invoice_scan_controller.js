import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['fileInput', 'status'];
    static values = { url: String };

    async scan() {
        const file = this.fileInputTarget.files[0];
        if (!file) return;

        this.statusTarget.classList.remove('hidden');
        this.statusTarget.textContent = 'Analyse du document par l’IA en cours...';

        const formData = new FormData();
        formData.append('document', file);

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                body: formData,
            });

            if (!response.ok) throw new Error('Erreur lors de l’analyse');

            const data = await response.json();

            // Remplit automatiquement chaque champ correspondant aux clés renvoyées par l'IA
            Object.entries(data).forEach(([key, value]) => {
                if (value !== null) {
                    const field = this.element.querySelector(`[data-scan-field="${key}"]`);
                    if (field) field.value = value;
                }
            });

            this.statusTarget.textContent = 'Champs pré-remplis automatiquement. Vérifie les infos avant de valider.';
        } catch {
            this.statusTarget.textContent = 'Impossible de lire automatiquement ce document. Tu peux saisir les champs manuellement.';
        }
    }
}
