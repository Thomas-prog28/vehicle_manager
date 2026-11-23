import { Controller } from '@hotwired/stimulus';

const DB_NAME = 'GarageOfflineQueueDB';
const STORE_NAME = 'pending_submissions';

export default class extends Controller {
    static targets = ['banner', 'bannerText'];

    connect() {
        this.updateBanner();
        window.addEventListener('online', () => this.syncAll());
        window.addEventListener('offline', () => this.updateBanner());

        // Tente une synchronisation dès l'ouverture si le réseau est revenu
        if (navigator.onLine) {
            this.syncAll();
        }
    }

    async queueFormSubmit(event) {
        // Si on est en ligne, on laisse Symfony traiter le formulaire normalement
        if (navigator.onLine) {
            return;
        }

        // Hors ligne : on intercepte l'envoi et on bloque toute tentative de soumission réseau
        event.preventDefault();
        event.stopImmediatePropagation();

        const form = event.currentTarget || event.target;
        const formData = new FormData(form);
        const entries = [];

        for (const [key, value] of formData.entries()) {
            if (value instanceof File) {
                entries.push({
                    key,
                    value,
                    filename: value.name,
                    isFile: true,
                });
            } else {
                entries.push({
                    key,
                    value,
                    isFile: false,
                });
            }
        }

        try {
            await this.saveToIndexedDB({
                url: form.action || window.location.href,
                createdAt: Date.now(),
                entries,
            });

            // Ferme la modale ouverte et affiche la confirmation hors-ligne
            const modal = form.closest('[data-modal-target="dialog"]');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }

            form.reset();
            await this.updateBanner();
            alert('Hors connexion : ta saisie et ton document ont été enregistrés localement sur ton appareil. Ils seront envoyés automatiquement dès le retour du réseau.');
        } catch (error) {
            console.error('Erreur de sauvegarde hors ligne :', error);
            alert('Erreur lors de la sauvegarde locale : ' + (error?.message || error));
        }
    }

    async syncAll() {
        if (!navigator.onLine) return;

        const items = await this.getAllFromIndexedDB();
        if (!items.length) {
            await this.updateBanner();
            return;
        }

        let syncedCount = 0;

        for (const item of items) {
            try {
                const formData = new FormData();
                for (const entry of item.entries) {
                    if (entry.isFile && entry.value instanceof Blob) {
                        formData.append(entry.key, entry.value, entry.filename || 'document');
                    } else if (entry.value instanceof Blob) {
                        const filename = entry.filename || (entry.value instanceof File ? entry.value.name : 'document');
                        formData.append(entry.key, entry.value, filename);
                    } else {
                        formData.append(entry.key, entry.value);
                    }
                }

                const response = await fetch(item.url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                });

                if (response.ok) {
                    await this.deleteFromIndexedDB(item.id);
                    syncedCount++;
                }
            } catch (e) {
                // Réseau encore instable, on réessaiera au prochain retour en ligne
            }
        }

        await this.updateBanner();

        if (syncedCount > 0) {
            window.location.reload();
        }
    }

    async updateBanner() {
        if (!this.hasBannerTarget) return;

        const items = await this.getAllFromIndexedDB();
        if (!navigator.onLine || items.length > 0) {
            this.bannerTarget.classList.remove('hidden');
            if (!navigator.onLine) {
                this.bannerTextTarget.textContent = items.length > 0
                    ? `Mode hors ligne — ${items.length} élément(s) en attente de synchronisation`
                    : 'Mode hors ligne — Consultation des données en cache';
            } else {
                this.bannerTextTarget.textContent = `Synchronisation de ${items.length} élément(s) en cours...`;
            }
        } else {
            this.bannerTarget.classList.add('hidden');
        }
    }

    openDB() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(DB_NAME, 1);
            req.onupgradeneeded = () => {
                req.result.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
            };
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    async saveToIndexedDB(record) {
        const db = await this.openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            tx.objectStore(STORE_NAME).add(record);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    }

    async getAllFromIndexedDB() {
        const db = await this.openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readonly');
            const req = tx.objectStore(STORE_NAME).getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject(req.error);
        });
    }

    async deleteFromIndexedDB(id) {
        const db = await this.openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            tx.objectStore(STORE_NAME).delete(id);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    }
}
