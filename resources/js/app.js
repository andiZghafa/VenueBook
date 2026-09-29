import flatpickr from 'flatpickr';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';

// The dashboard's own calendar starts weeks on Sunday (Min/Sen/Sel/...), so align
// flatpickr to the same convention instead of the Indonesian locale's Monday default.
flatpickr.localize({ ...Indonesian, firstDayOfWeek: 0 });

document.addEventListener('DOMContentLoaded', () => {
    const datePickers = new Map();

    document.querySelectorAll('[data-flatpickr="date"]').forEach((el) => {
        datePickers.set(el.id, flatpickr(el, {
            altInput: true,
            altFormat: 'd F Y',
            dateFormat: 'Y-m-d',
            allowInput: true,
        }));
    });

    document.querySelectorAll('[data-flatpickr="datetime"]').forEach((el) => {
        flatpickr(el, {
            enableTime: true,
            time_24hr: true,
            altInput: true,
            altFormat: 'd F Y, H:i',
            dateFormat: 'Y-m-d H:i',
            allowInput: true,
        });
    });

    // Keep "Tanggal Selesai" from allowing a date before "Tanggal Mulai".
    const startPicker = datePickers.get('startDate');
    const endPicker = datePickers.get('endDate');

    if (startPicker && endPicker) {
        startPicker.config.onChange.push((selectedDates) => {
            if (selectedDates[0]) {
                endPicker.set('minDate', selectedDates[0]);
            }
        });
    }
});
