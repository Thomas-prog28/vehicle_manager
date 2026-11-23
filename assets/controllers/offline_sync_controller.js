import { Controller } from '@hotwired/stimulus';

const DB_NAME = 'GarageOfflineQueueDB';
const STORE_NAME = 'pending_submissions';

export default class extends Controller {
    static targets = ['banner', 'bannerText'];

    connect() {
        this.updateBanner();

        if (!window.__offlineSyncListenersAttached) {
            window.__offlineSyncListenersAttached = true;
            window.addEventListener('online', () => this.syncAll());
            window.addEventListener('offline', () => this.updateBanner());
        }

        if (navigator.onLine) {
            this.syncAll();
        }
    }

    async queueFormSubmit(event) {
        if (navigator.onLine) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        const form = event.target;
        const formData = new FormData(form);
        const entries = [];

        for (const [key, value] of formData.entries()) {
            if (value instanceof File) {
                if (value.size > 0) {
                    const arrayBuffer = await value.arrayBuffer();
                    entries.push({
                        key,
                        isFile: true,
                        name: value.name || 'document.jpg',
                        type: value.type || 'application/octet-stream',
                        buffer: arrayBuffer,
                    });
                }
            } else {
                entries.push({ key, isFile: false, value });
            }
        }

        await this.saveToIndexedDB({
            url: form.action || window.location.href,
            createdAt: Date.now(),
            entries,
        });

        const modal = form.closest('[data-modal-target="dialog"]');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        form.reset();
        await this.updateBanner();
        alert('Hors connexion : ta saisie et ton document sont enregistrés localement sur ton appareil. Ils seront envoyés automatiquement dès le retour du réseau.');
    }

    async syncAll() {
        if (!navigator.onLine || window.__offlineSyncRunning) return;
        window.__offlineSyncRunning = true;

        try {
            const items = await this.getAllFromIndexedDB();
            if (!items.length) {
                await this.updateBanner();
                return;
            }

            let syncedCount = 0;
            let authRequired = false;

            for (const item of items) {
                try {
                    const formData = new FormData();
                    for (const entry of item.entries) {
                        if (entry.isFile) {
                            const file = new File([entry.buffer], entry.name, { type: entry.type });
                            formData.append(entry.key, file);
                        } else {
                            formData.append(entry.key, entry.value);
                        }
                    }

                    const response = await fetch(item.url, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                    });

                    // Si Symfony redirige vers /login, la session a expiré : on GARDE l'élément en mémoire !
                    if (response.url && response.url.includes('/login')) {
                        authRequired = true;
                        break;
                    }

                    // Succès uniquement si HTTP 200-299 et pas de page login
                    if (response.ok) {
                        await this.deleteFromIndexedDB(item.id);
                        syncedCount++;
                    }
                } catch (e) {
                    // Réseau encore instable, on réessaiera automatiquement
                }
            }

            await this.updateBanner(
                authRequired
                    ? 'Connexion requise pour envoyer tes éléments en attente (connecte-toi puis reviens ici).'
                    : syncedCount > 0
                        ? `Synchronisation terminée : ${syncedCount} élément(s) enregistré(s) !`
                        : null
            );

            // Si l'utilisateur est resté sur la fiche du véhicule concerné, on rafraîchit la page en douceur
            if (syncedCount > 0 && window.location.pathname.startsWith('/vehicle/')) {
                setTimeout(() => window.location.reload(), 1200);
            }
        } finally {
            window.__offlineSyncRunning = false;
        }
    }

    async updateBanner(customMessage = null) {
        const banner = document.querySelector('[data-offline-sync-target="banner"]');
        const bannerText = document.querySelector('[data-offline-sync-target="bannerText"]');
        if (!banner || !bannerText) return;

        if (customMessage) {
            banner.classList.remove('hidden');
            bannerText.textContent = customMessage;
            if (customMessage.startsWith('Synchronisation terminée')) {
                setTimeout(() => this.updateBanner(), 4000);
            }
            return;
        }

        const items = await this.getAllFromIndexedDB();
        if (!navigator.onLine || items.length > 0) {
            banner.classList.remove('hidden');
            if (!navigator.onLine) {
                bannerText.textContent = items.length > 0
                    ? `Mode hors ligne — ${items.length} élément(s) en attente de synchronisation`
                    : 'Mode hors ligne — Consultation des données en cache';
            } else {
                bannerText.textContent = `Synchronisation de ${items.length} élément(s) en cours...`;
            }
        } else {
            banner.classList.add('hidden');
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
