import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['dialog', 'vehicleSelect', 'pumpInput', 'odometerInput', 'step1', 'step2', 'loading', 'error'];

    connect() {
        this.coords = { lat: null, lon: null };
    }

    start() {
        // Récupère le GPS en arrière-plan dès le clic sur "Faire le plein"
        if ('geolocation' in navigator) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.coords.lat = pos.coords.latitude;
                    this.coords.lon = pos.coords.longitude;
                },
                () => { },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 }
            );
        }

        this.resetUI();
        this.dialogTarget.classList.remove('hidden');
        this.dialogTarget.classList.add('flex');

        // Si un seul véhicule dans le garage, ouvre directement l'appareil photo 1
        if (this.vehicleSelectTarget.options.length === 1) {
            this.pumpInputTarget.click();
        }
    }

    triggerPumpCamera() {
        this.pumpInputTarget.click();
    }

    onPumpCaptured() {
        if (!this.pumpInputTarget.files[0]) return;
        this.step1Target.classList.add('hidden');
        this.step2Target.classList.remove('hidden');
    }

    triggerOdometerCamera() {
        this.odometerInputTarget.click();
    }

    async onOdometerCaptured() {
        const pumpFile = this.pumpInputTarget.files[0];
        const odoFile = this.odometerInputTarget.files[0];
        if (!pumpFile || !odoFile) return;

        this.step2Target.classList.add('hidden');
        this.loadingTarget.classList.remove('hidden');

        // Si le GPS n'a pas encore fini de répondre, on l'attend brièvement (max 3s)
        if (!this.coords.lat && 'geolocation' in navigator) {
            await new Promise((resolve) => {
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        this.coords.lat = pos.coords.latitude;
                        this.coords.lon = pos.coords.longitude;
                        resolve();
                    },
                    () => resolve(),
                    { timeout: 3000, maximumAge: 60000 }
                );
            });
        }

        const formData = new FormData();
        formData.append('pumpPhoto', pumpFile);
        formData.append('odometerPhoto', odoFile);
        if (this.coords.lat) formData.append('lat', this.coords.lat);
        if (this.coords.lon) formData.append('lon', this.coords.lon);

        const url = this.vehicleSelectTarget.value;

        try {
            const response = await fetch(url, { method: 'POST', body: formData });
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Erreur lors de l’analyse.');
            }

            window.location.reload();
        } catch (err) {
            this.loadingTarget.classList.add('hidden');
            this.errorTarget.textContent = err.message;
            this.errorTarget.classList.remove('hidden');
        }
    }

    close() {
        this.dialogTarget.classList.add('hidden');
        this.dialogTarget.classList.remove('flex');
    }

    resetUI() {
        this.pumpInputTarget.value = '';
        this.odometerInputTarget.value = '';
        this.step1Target.classList.remove('hidden');
        this.step2Target.classList.add('hidden');
        this.loadingTarget.classList.add('hidden');
        this.errorTarget.classList.add('hidden');
    }
}
