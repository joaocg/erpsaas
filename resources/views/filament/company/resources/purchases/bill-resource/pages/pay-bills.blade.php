<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    {{ $this->form }}
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Total Selected:</div>
                    <div class="text-xl font-semibold text-gray-900" id="total-amount">{{ $this->totalSelectedPaymentAmount }}</div>
                </div>
            </div>
        </div>

        {{ $this->table }}
    </div>

    <script>
        // Update total when table state changes
        document.addEventListener('livewire:navigated', function() {
            updateTotal();
        });

        function updateTotal() {
            // This would need to be implemented to calculate total from selected bills
            // For now, it's a placeholder
        }
    </script>
</x-filament-panels::page>
