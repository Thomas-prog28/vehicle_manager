import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['fileInput', 'date', 'garage', 'mileage', 'total', 'status'];
    static values = { url: String };

    async scan() {
        const file = this.fileInputTarget.files[0];
        if (!file) return;

        this.statusTarget.classList.remove('hidden');
        this.statusTarget.textContent = 'Analyse du document en cours...';

        const formData = new FormData();
        formData.append('document', file);

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                body: formData,
            });

            if (!response.ok) throw new Error('Erreur lors de l’analyse');

            const data = await response.json();

            if (data.date) this.dateTarget.value = data.date;
            if (data.garage) this.garageTarget.value = data.garage;
            if (data.mileage) this.mileageTarget.value = data.mileage;
            if (data.totalTTC) this.totalTarget.value = data.totalTTC;

            this.statusTarget.textContent = 'Champs pré-remplis automatiquement. Vérifie les infos avant de valider.';
        } catch (error) {
            this.statusTarget.textContent = 'Impossible de lire automatiquement ce document. Tu peux saisir les champs manuellement.';
        }
    }
}
