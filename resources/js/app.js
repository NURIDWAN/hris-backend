import './bootstrap';
import Alpine from 'alpinejs';

// Currency Input Component - untuk format angka dengan pemisah ribuan
window.currencyInput = function(initialValue = 0) {
    return {
        value: initialValue,
        display: initialValue ? new Intl.NumberFormat('id-ID').format(initialValue) : '',

        updateValue(event) {
            // Get cursor position
            const input = event.target;
            const cursorPos = input.selectionStart;
            const oldLength = this.display.length;

            // Remove non-numeric characters
            const numericValue = this.display.replace(/[^\d]/g, '');
            this.value = numericValue ? parseInt(numericValue) : 0;

            // Format immediately
            if (this.value > 0) {
                this.display = new Intl.NumberFormat('id-ID').format(this.value);
            } else {
                this.display = '';
            }

            // Restore cursor position (adjust for added separators)
            this.$nextTick(() => {
                const newLength = this.display.length;
                const diff = newLength - oldLength;
                const newPos = Math.max(0, cursorPos + diff);
                input.setSelectionRange(newPos, newPos);
            });
        },

        formatDisplay() {
            // Keep for backward compatibility but not strictly needed now
            if (this.value > 0) {
                this.display = new Intl.NumberFormat('id-ID').format(this.value);
            } else {
                this.display = '';
            }
        }
    }
};

window.Alpine = Alpine;
Alpine.start();
