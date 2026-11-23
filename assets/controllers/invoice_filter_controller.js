import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button', 'item', 'count', 'emptyFiltered'];

    filter(event) {
        const selectedType = event.currentTarget.dataset.type;

        // 1. Mise à jour visuelle des boutons pilules
        this.buttonTargets.forEach((btn) => {
            const isActive = btn.dataset.type === selectedType;
            btn.classList.toggle('bg-foreground', isActive);
            btn.classList.toggle('text-background', isActive);
            btn.classList.toggle('bg-secondary', !isActive);
            btn.classList.toggle('text-muted-foreground', !isActive);
        });

        // 2. Affichage / masquage des factures correspondantes
        let visibleCount = 0;

        this.itemTargets.forEach((item) => {
            const matches = selectedType === 'all' || item.dataset.invoiceType === selectedType;
            item.classList.toggle('hidden', !matches);
            if (matches) visibleCount++;
        });

        // 3. Mise à jour du compteur et du message "aucune facture dans cette catégorie"
        if (this.hasCountTarget) {
            this.countTarget.textContent = visibleCount;
        }
        if (this.hasEmptyFilteredTarget) {
            this.emptyFilteredTarget.classList.toggle('hidden', visibleCount > 0);
        }
    }
}
