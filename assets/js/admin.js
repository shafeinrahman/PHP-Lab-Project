/**
 * Apex Auto Workshop - Admin Dashboard Script
 * CSE 391: Programming for the Internet - Assignment 3
 * Handles live table filtering, appointment search, and edit modal with real-time mechanic capacity check.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Search & Filter elements
    const searchInput = document.getElementById('searchAppointments');
    const filterDate = document.getElementById('filterDate');
    const filterMechanic = document.getElementById('filterMechanic');
    const appointmentsTable = document.getElementById('appointmentsTable');

    // Edit Modal elements
    const editModal = document.getElementById('editModal');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const cancelModalBtn = document.getElementById('cancelModalBtn');
    const editForm = document.getElementById('editAppointmentForm');
    const editAppIdInput = document.getElementById('edit_appointment_id');
    const editClientName = document.getElementById('edit_client_name');
    const editDateInput = document.getElementById('edit_appointment_date');
    const editMechanicSelect = document.getElementById('edit_mechanic_id');
    const modalSlotNotice = document.getElementById('modalSlotNotice');

    /**
     * Filter table rows based on search input, date, and mechanic filter
     */
    function filterTable() {
        if (!appointmentsTable) return;

        const term = (searchInput ? searchInput.value.toLowerCase().trim() : '');
        const dateVal = (filterDate ? filterDate.value : '');
        const mechVal = (filterMechanic ? filterMechanic.value : '');

        const rows = appointmentsTable.querySelectorAll('tbody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            const rowDate = row.getAttribute('data-date') || '';
            const rowMech = row.getAttribute('data-mechanic-id') || '';

            const matchesSearch = !term || rowText.includes(term);
            const matchesDate = !dateVal || rowDate === dateVal;
            const matchesMech = !mechVal || rowMech === mechVal;

            if (matchesSearch && matchesDate && matchesMech) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const noResultsRow = document.getElementById('noResultsRow');
        if (noResultsRow) {
            noResultsRow.style.display = (visibleCount === 0) ? '' : 'none';
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (filterDate) filterDate.addEventListener('change', filterTable);
    if (filterMechanic) filterMechanic.addEventListener('change', filterTable);

    const resetFiltersBtn = document.getElementById('resetFilters');
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function () {
            if (searchInput) searchInput.value = '';
            if (filterDate) filterDate.value = '';
            if (filterMechanic) filterMechanic.value = '';
            filterTable();
        });
    }

    /**
     * Open Edit Modal
     */
    document.querySelectorAll('.btn-edit-appointment').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const client = this.getAttribute('data-client');
            const date = this.getAttribute('data-date');
            const mechId = this.getAttribute('data-mechanic-id');
            const status = this.getAttribute('data-status');
            const notes = this.getAttribute('data-notes') || '';

            if (editAppIdInput) editAppIdInput.value = id;
            if (editClientName) editClientName.textContent = client;
            if (editDateInput) editDateInput.value = date;
            
            const editStatusSelect = document.getElementById('edit_status');
            if (editStatusSelect) editStatusSelect.value = status;

            const editNotesInput = document.getElementById('edit_notes');
            if (editNotesInput) editNotesInput.value = notes;

            // Fetch dynamic mechanic availability for this date
            loadModalMechanics(date, mechId, id);

            if (editModal) editModal.classList.add('active');
        });
    });

    /**
     * Listen for date change inside the Edit modal to re-evaluate mechanic capacities
     */
    if (editDateInput) {
        editDateInput.addEventListener('change', function () {
            const newDate = this.value;
            const currentMech = editMechanicSelect ? editMechanicSelect.value : null;
            const appId = editAppIdInput ? editAppIdInput.value : null;
            loadModalMechanics(newDate, currentMech, appId);
        });
    }

    /**
     * AJAX: Fetch mechanics and their free slots for modal
     */
    function loadModalMechanics(dateStr, preselectedMechId, excludeAppId) {
        if (!editMechanicSelect || !dateStr) return;

        editMechanicSelect.innerHTML = '<option value="">Loading mechanic availability...</option>';
        if (modalSlotNotice) modalSlotNotice.textContent = 'Checking daily limits...';

        fetch(`get_slots.php?date=${encodeURIComponent(dateStr)}&exclude_id=${encodeURIComponent(excludeAppId || '')}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success || !Array.isArray(data.mechanics)) {
                    editMechanicSelect.innerHTML = '<option value="">Error loading mechanics</option>';
                    return;
                }

                editMechanicSelect.innerHTML = '';
                let hasAvailable = false;

                data.mechanics.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.id;
                    const free = parseInt(m.free_slots, 10);
                    const isCurrent = (m.id == preselectedMechId);

                    if (free <= 0 && !isCurrent) {
                        opt.textContent = `${m.name} — FULL (0/4 Free)`;
                        opt.disabled = true;
                    } else {
                        opt.textContent = `${m.name} (${free}/4 Free Places Available) - ${m.specialty}`;
                        hasAvailable = true;
                    }

                    if (m.id == preselectedMechId) {
                        opt.selected = true;
                    }

                    editMechanicSelect.appendChild(opt);
                });

                if (modalSlotNotice) {
                    modalSlotNotice.textContent = hasAvailable 
                        ? 'Select any available senior mechanic with free slots.' 
                        : '⚠️ Warning: Most mechanics are occupied on this date.';
                }
            })
            .catch(err => {
                console.error('Error in loadModalMechanics:', err);
                editMechanicSelect.innerHTML = '<option value="">Failed to connect to server</option>';
            });
    }

    /**
     * Close modal helpers
     */
    function closeModal() {
        if (editModal) editModal.classList.remove('active');
    }

    if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
    if (cancelModalBtn) cancelModalBtn.addEventListener('click', closeModal);

    // Close on outside click
    if (editModal) {
        editModal.addEventListener('click', function (e) {
            if (e.target === editModal) closeModal();
        });
    }

    // Delete confirmation
    document.querySelectorAll('.btn-delete-appointment').forEach(btn => {
        btn.addEventListener('click', function (e) {
            const client = this.getAttribute('data-client');
            if (!confirm(`Are you sure you want to cancel and remove the appointment for "${client}"?`)) {
                e.preventDefault();
            }
        });
    });
});

