/**
 * Apex Auto Workshop - Client Booking Script
 * CSE 391: Programming for the Internet - Assignment 3
 * Handles real-time slot checking via AJAX, dynamic mechanic capacity indicators,
 * and comprehensive form validations.
 */

document.addEventListener('DOMContentLoaded', function () {
    const appointmentDateInput = document.getElementById('appointment_date');
    const bookingForm = document.getElementById('bookingForm');
    const mechanicGrid = document.getElementById('mechanicGrid');
    const selectedDateDisplay = document.getElementById('selectedDateDisplay');
    const licenseInput = document.getElementById('car_license_no');
    const phoneInput = document.getElementById('client_phone');
    const duplicateAlert = document.getElementById('duplicateAlert');
    const duplicateMessage = document.getElementById('duplicateMessage');

    // 1. Enforce min date = today
    if (appointmentDateInput) {
        const todayStr = new Date().toISOString().split('T')[0];
        appointmentDateInput.min = todayStr;
        if (!appointmentDateInput.value) {
            appointmentDateInput.value = todayStr;
        }

        // Fetch initial mechanic availability for the initial date
        fetchMechanicSlots(appointmentDateInput.value);

        // Listen for date changes
        appointmentDateInput.addEventListener('change', function () {
            if (this.value) {
                fetchMechanicSlots(this.value);
                checkDuplicateAppointment();
            }
        });
    }

    // Pre-check duplicate appointment when license or phone changes
    if (licenseInput) {
        licenseInput.addEventListener('blur', checkDuplicateAppointment);
    }
    if (phoneInput) {
        phoneInput.addEventListener('blur', checkDuplicateAppointment);
    }

    /**
     * AJAX: Fetch available slots for all mechanics on a given date
     * Requirement: "The list of the mechanic should show how many free places are available for each mechanic."
     */
    function fetchMechanicSlots(dateStr) {
        if (!dateStr) return;

        if (selectedDateDisplay) {
            selectedDateDisplay.textContent = dateStr;
        }

        // Show subtle loading state on mechanic cards
        const cards = document.querySelectorAll('.mechanic-card');
        cards.forEach(c => c.style.opacity = '0.6');

        fetch(`api/get_mechanic_slots.php?date=${encodeURIComponent(dateStr)}`)
            .then(res => res.json())
            .then(data => {
                cards.forEach(c => c.style.opacity = '1');

                if (data.success && Array.isArray(data.mechanics)) {
                    updateMechanicCards(data.mechanics);
                }
            })
            .catch(err => {
                console.error('Error fetching mechanic slots:', err);
                cards.forEach(c => c.style.opacity = '1');
            });
    }

    /**
     * Update mechanic cards DOM with availability data
     */
    function updateMechanicCards(mechanics) {
        mechanics.forEach(mech => {
            const card = document.querySelector(`.mechanic-card[data-mechanic-id="${mech.id}"]`);
            if (!card) return;

            const radio = card.querySelector('input[type="radio"]');
            const slotBadge = card.querySelector('.slot-indicator');
            const freeSlots = parseInt(mech.free_slots, 10);
            const maxCap = parseInt(mech.max_daily_capacity, 10) || 4;

            // Update badge text and style
            if (slotBadge) {
                if (freeSlots <= 0) {
                    slotBadge.className = 'slot-indicator full';
                    slotBadge.innerHTML = `⛔ FULL (0/${maxCap} free)`;
                } else if (freeSlots === 1) {
                    slotBadge.className = 'slot-indicator almost-full';
                    slotBadge.innerHTML = `⚠️ 1 slot left (${freeSlots}/${maxCap} free)`;
                } else {
                    slotBadge.className = 'slot-indicator available';
                    slotBadge.innerHTML = `🟢 ${freeSlots}/${maxCap} free places`;
                }
            }

            // Disable or enable card
            if (freeSlots <= 0) {
                card.classList.add('disabled');
                if (radio) {
                    radio.disabled = true;
                    if (radio.checked) {
                        radio.checked = false;
                        card.classList.remove('selected');
                    }
                }
            } else {
                card.classList.remove('disabled');
                if (radio) {
                    radio.disabled = false;
                }
            }
        });
    }

    /**
     * Handle Mechanic Card Selection
     */
    if (mechanicGrid) {
        mechanicGrid.addEventListener('click', function (e) {
            const card = e.target.closest('.mechanic-card');
            if (!card || card.classList.contains('disabled')) return;

            // Remove selected from all
            document.querySelectorAll('.mechanic-card').forEach(c => c.classList.remove('selected'));

            // Select clicked card
            card.classList.add('selected');
            const radio = card.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                clearFieldError('mechanic_id');
            }
        });
    }

    /**
     * AJAX: Check if client already booked an appointment on the chosen date
     * Requirement: "After a client makes data submission, it should check whether the client 
     * has already taken any appointment from any mechanic on certain date or not."
     */
    function checkDuplicateAppointment() {
        if (!appointmentDateInput || !licenseInput) return;

        const date = appointmentDateInput.value;
        const license = licenseInput.value.trim();
        const phone = phoneInput ? phoneInput.value.trim() : '';

        if (!date || (!license && !phone)) {
            if (duplicateAlert) duplicateAlert.style.display = 'none';
            return;
        }

        fetch(`api/check_appointment.php?date=${encodeURIComponent(date)}&license=${encodeURIComponent(license)}&phone=${encodeURIComponent(phone)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.has_appointment) {
                    if (duplicateAlert && duplicateMessage) {
                        duplicateMessage.textContent = data.message;
                        duplicateAlert.style.display = 'flex';
                    }
                } else {
                    if (duplicateAlert) duplicateAlert.style.display = 'none';
                }
            })
            .catch(err => console.log('Check duplicate err', err));
    }

    /**
     * Form Validation (Client-Side) - Requirement Part 2
     */
    if (bookingForm) {
        // Real-time input validation cleanups
        ['client_name', 'client_address', 'client_phone', 'car_license_no', 'car_engine_no'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', function () {
                    clearFieldError(id);
                });
            }
        });

        // Numeric-only strict keypress filters for phone and engine number
        if (phoneInput) {
            phoneInput.addEventListener('keypress', function (e) {
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });
        }

        const engineInput = document.getElementById('car_engine_no');
        if (engineInput) {
            engineInput.addEventListener('keypress', function (e) {
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });
        }

        bookingForm.addEventListener('submit', function (e) {
            let isValid = true;

            // 1. Validate Name
            const nameEl = document.getElementById('client_name');
            if (!nameEl.value.trim()) {
                setFieldError('client_name', 'Please enter your full name.');
                isValid = false;
            } else if (nameEl.value.trim().length < 2) {
                setFieldError('client_name', 'Name must be at least 2 characters.');
                isValid = false;
            }

            // 2. Validate Address
            const addressEl = document.getElementById('client_address');
            if (!addressEl.value.trim()) {
                setFieldError('client_address', 'Please enter your address.');
                isValid = false;
            } else if (addressEl.value.trim().length < 4) {
                setFieldError('client_address', 'Address must be at least 4 characters.');
                isValid = false;
            }

            // 3. Validate Phone (only numbers)
            if (phoneInput) {
                const phoneVal = phoneInput.value.trim();
                if (!phoneVal) {
                    setFieldError('client_phone', 'Phone number is required.');
                    isValid = false;
                } else if (!/^[0-9]+$/.test(phoneVal)) {
                    setFieldError('client_phone', 'Phone number must contain only numbers (digits 0-9).');
                    isValid = false;
                } else if (phoneVal.length < 7 || phoneVal.length > 15) {
                    setFieldError('client_phone', 'Phone number must be between 7 and 15 digits.');
                    isValid = false;
                }
            }

            // 4. Validate Car License Number
            if (licenseInput) {
                if (!licenseInput.value.trim()) {
                    setFieldError('car_license_no', 'Car license / registration number is required.');
                    isValid = false;
                }
            }

            // 5. Validate Car Engine Number (only numbers)
            if (engineInput) {
                const engVal = engineInput.value.trim();
                if (!engVal) {
                    setFieldError('car_engine_no', 'Car engine number is required.');
                    isValid = false;
                } else if (!/^[0-9]+$/.test(engVal)) {
                    setFieldError('car_engine_no', 'Car engine number must contain only numbers (digits 0-9).');
                    isValid = false;
                }
            }

            // 6. Validate Date
            if (appointmentDateInput) {
                const dateVal = appointmentDateInput.value.trim();
                const today = new Date().toISOString().split('T')[0];
                if (!dateVal) {
                    setFieldError('appointment_date', 'Please select an appointment date.');
                    isValid = false;
                } else if (dateVal < today) {
                    setFieldError('appointment_date', 'Appointment date cannot be in the past.');
                    isValid = false;
                }
            }

            // 7. Validate Mechanic Selection
            const selectedMechanic = document.querySelector('input[name="mechanic_id"]:checked');
            if (!selectedMechanic) {
                setFieldError('mechanic_id', 'Please choose one of the available senior mechanics from the list.');
                isValid = false;
            } else {
                const parentCard = selectedMechanic.closest('.mechanic-card');
                if (parentCard && parentCard.classList.contains('disabled')) {
                    setFieldError('mechanic_id', 'The selected mechanic is fully booked. Please choose another mechanic.');
                    isValid = false;
                }
            }

            if (!isValid) {
                e.preventDefault();
                // Scroll to the first error
                const firstInvalid = document.querySelector('.is-invalid, .error-highlight');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }

    function setFieldError(fieldId, errorMsg) {
        const input = document.getElementById(fieldId);
        if (input) {
            input.classList.add('is-invalid');
            const feedback = input.parentElement.querySelector('.invalid-feedback');
            if (feedback) {
                feedback.textContent = errorMsg;
            }
        } else if (fieldId === 'mechanic_id') {
            const mechError = document.getElementById('mechanicError');
            if (mechError) {
                mechError.textContent = errorMsg;
                mechError.style.display = 'block';
            }
        }
    }

    function clearFieldError(fieldId) {
        const input = document.getElementById(fieldId);
        if (input) {
            input.classList.remove('is-invalid');
        } else if (fieldId === 'mechanic_id') {
            const mechError = document.getElementById('mechanicError');
            if (mechError) {
                mechError.style.display = 'none';
            }
        }
    }
});

