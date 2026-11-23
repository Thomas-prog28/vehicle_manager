import { Controller } from '@hotwired/stimulus';

const CAR_BRANDS = [
    'Abarth', 'Alfa Romeo', 'Alpine', 'Aston Martin', 'Audi', 'Bentley', 'BMW', 'BYD',
    'Chevrolet', 'Chrysler', 'Citroën', 'Cupra', 'Dacia', 'DS Automobiles', 'Ferrari',
    'Fiat', 'Ford', 'Honda', 'Hyundai', 'Infiniti', 'Jaguar', 'Jeep', 'Kia', 'Lamborghini',
    'Lancia', 'Land Rover', 'Lexus', 'Lotus', 'Maserati', 'Mazda', 'Mercedes-Benz', 'MG',
    'Mini', 'Mitsubishi', 'Nissan', 'Opel', 'Peugeot', 'Polestar', 'Porsche', 'Renault',
    'Rolls-Royce', 'Seat', 'Škoda', 'Smart', 'SsangYong', 'Subaru', 'Suzuki', 'Tesla',
    'Toyota', 'Volkswagen', 'Volvo'
];

const MOTO_BRANDS = [
    'Aprilia', 'Benelli', 'Beta', 'BMW Motorrad', 'CFMoto', 'Ducati', 'Fantic', 'GasGas',
    'Harley-Davidson', 'Honda', 'Husqvarna', 'Indian', 'Kawasaki', 'KTM', 'Kymco',
    'Mash', 'Moto Guzzi', 'MV Agusta', 'Orcal', 'Peugeot Motocycles', 'Piaggio',
    'Royal Enfield', 'Sherco', 'Suzuki', 'SYM', 'Triumph', 'Vespa', 'Yamaha', 'Zero Motorcycles'
];

export default class extends Controller {
    static targets = ['typeSelect', 'datalist'];

    connect() {
        this.updateBrands();
    }

    updateBrands() {
        if (!this.hasDatalistTarget) return;

        const selectedType = this.hasTypeSelectTarget
            ? this.typeSelectTarget.value.toLowerCase()
            : '';

        const brands = selectedType === 'moto'
            ? MOTO_BRANDS
            : selectedType === 'voiture'
                ? CAR_BRANDS
                : [...new Set([...CAR_BRANDS, ...MOTO_BRANDS])].sort();

        this.datalistTarget.innerHTML = brands
            .map((brand) => `<option value="${brand}">`)
            .join('');
    }
}
