document.addEventListener('DOMContentLoaded', function() {
    // Initialize all severity selects
    document.querySelectorAll('.severity-select').forEach(select => {
        updateOffensesAndPenalties(select);
    });

    // Track new violation count for each category
    const newViolationCounts = {};

    // Initialize counters for existing categories
    document.querySelectorAll('.category-section').forEach(section => {
        const addButton = section.querySelector('.add-violation-btn');
        if (addButton) {
            const categoryId = addButton.getAttribute('data-category-id');
            newViolationCounts[categoryId] = 0;
        }
    });

    // Add new violation to existing category
    document.querySelectorAll('.add-violation-btn').forEach(button => {
        button.addEventListener('click', function() {
            const categoryId = this.getAttribute('data-category-id');
            const categoryIndex = this.getAttribute('data-category-index');
            const container = document.getElementById(`violations-container-${categoryId}`);

            // Increment the counter for this category
            if (!newViolationCounts[categoryId]) {
                newViolationCounts[categoryId] = 0;
            }

            const violationIndex = newViolationCounts[categoryId];
            const newRowIndex = container.rows.length;

            // Create new row at the end of the table
            const newRow = container.insertRow(-1); // -1 means append at the end
            newRow.className = 'new-violation-row';

            // Create cells
            const idCell = newRow.insertCell(0);
            const nameCell = newRow.insertCell(1);
            const severityCell = newRow.insertCell(2);
            const offensesCell = newRow.insertCell(3);
            const penaltiesCell = newRow.insertCell(4);
            const actionCell = newRow.insertCell(5);

            // Add hidden input for new violation flag
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = `categories[${categoryIndex}][new_violations][${violationIndex}][id]`;
            hiddenInput.value = 'new';
            nameCell.appendChild(hiddenInput);

            // Set cell content
            idCell.innerHTML = `New`;
            nameCell.className = 'editable-cell';
            nameCell.innerHTML = `
                <textarea name="categories[${categoryIndex}][new_violations][${violationIndex}][name]"
                          class="violation-textarea" placeholder="Enter new violation name" rows="3" maxlength="500"></textarea>
                <div class="char-counter small text-muted">
                    <span class="current-count">0</span>/500 characters
                </div>
            `;

            const offensesFieldName = `categories[${categoryIndex}][new_violations][${violationIndex}][offenses]`;
            const penaltiesFieldName = `categories[${categoryIndex}][new_violations][${violationIndex}][penalties_text]`;

            severityCell.innerHTML = `
                <select class="penalty-select severity-select"
                       name="categories[${categoryIndex}][new_violations][${violationIndex}][default_penalty]"
                       data-offenses-field="${offensesFieldName}"
                       data-penalties-field="${penaltiesFieldName}"
                       onchange="updateOffensesAndPenalties(this)" required>
                    <option value="W">Low</option>
                    <option value="VW">Medium</option>
                    <option value="WW">High</option>
                    <option value="Exp">Very High</option>
                </select>
            `;

            offensesCell.innerHTML = `
                <input type="text" class="form-control" readonly
                       name="${offensesFieldName}"
                       value="1st, 2nd, 3rd">
            `;

            penaltiesCell.innerHTML = `
                <input type="text" class="form-control" readonly
                       name="${penaltiesFieldName}"
                       value="1st: Warning, 2nd: Verbal Warning, 3rd: Written Warning">
            `;

            actionCell.className = 'text-center';
            actionCell.innerHTML = `
                <button type="button" class="btn btn-danger btn-sm delete-new-violation"
                        onclick="deleteNewViolation(this)">
                    <i class="fas fa-trash"></i> Delete
                </button>
            `;

            // Initialize character counter for the new textarea
            const newTextarea = nameCell.querySelector('.violation-textarea');
            if (newTextarea) {
                newTextarea.addEventListener('input', function() {
                    const counter = this.parentNode.querySelector('.char-counter .current-count');
                    if (counter) {
                        const currentLength = this.value.length;
                        counter.textContent = currentLength;

                        // Add warning class if approaching limit
                        if (currentLength > 450) {
                            counter.classList.add('char-limit-warning');
                        } else {
                            counter.classList.remove('char-limit-warning');
                        }
                    }
                });
            }

            // Initialize the severity select
            const severitySelect = severityCell.querySelector('.severity-select');
            if (severitySelect) {
                updateOffensesAndPenalties(severitySelect);
            }

            newViolationCounts[categoryId]++;
        });
    });

    // Add character counter functionality
    document.querySelectorAll('.violation-textarea').forEach(textarea => {
        textarea.addEventListener('input', function() {
            const counter = this.parentNode.querySelector('.char-counter .current-count');
            if (counter) {
                const currentLength = this.value.length;
                counter.textContent = currentLength;

                // Add warning class if approaching limit
                if (currentLength > 450) {
                    counter.classList.add('char-limit-warning');
                } else {
                    counter.classList.remove('char-limit-warning');
                }
            }
        });
    });

    // Add confirmation before form submission
    const form = document.getElementById('manualForm');
    const saveButton = document.getElementById('saveButton');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Remove any highlighting from previous validation attempts
        document.querySelectorAll('.border-danger').forEach(el => {
            el.classList.remove('border-danger');
        });

        document.querySelectorAll('.text-danger').forEach(el => {
            el.remove();
        });

        let hasValidationErrors = false;

        // Validate new category
        const newCategoryName = document.getElementById('new_category_name');
        const newViolationName = document.getElementById('new_violation_name');
        const emptyCategoryAlert = document.getElementById('empty-category-alert');

        // Reset alerts
        emptyCategoryAlert.style.display = 'none';

        // Check if new category has a name but no violation name
        if (newCategoryName.value.trim() !== '' && newViolationName.value.trim() === '') {
            hasValidationErrors = true;
            emptyCategoryAlert.style.display = 'block';
            newViolationName.classList.add('border-danger');

            // Add a small error message
            if (!newViolationName.parentNode.querySelector('.text-danger')) {
                const errorMsg = document.createElement('div');
                errorMsg.className = 'text-danger small mt-1';
                errorMsg.textContent = 'Please enter a violation name or leave the category name empty';
                newViolationName.parentNode.appendChild(errorMsg);
            }
        }

        // Check if violation name has a value but no category name
        if (newCategoryName.value.trim() === '' && newViolationName.value.trim() !== '') {
            hasValidationErrors = true;
            emptyCategoryAlert.style.display = 'block';
            newCategoryName.classList.add('border-danger');

            // Add a small error message
            if (!newCategoryName.parentNode.querySelector('.text-danger')) {
                const errorMsg = document.createElement('div');
                errorMsg.className = 'text-danger small mt-1';
                errorMsg.textContent = 'Please enter a category name or leave the violation name empty';
                newCategoryName.parentNode.appendChild(errorMsg);
            }
        }

        if (hasValidationErrors) {
            // Scroll to the first error
            const firstError = document.querySelector('.border-danger') || document.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        // Create a simple, modern confirmation dialog
        const confirmDialog = document.createElement('div');
        confirmDialog.className = 'modal fade';
        confirmDialog.id = 'saveConfirmModal';
        confirmDialog.setAttribute('tabindex', '-1');
        confirmDialog.setAttribute('aria-labelledby', 'saveConfirmModalLabel');
        confirmDialog.setAttribute('aria-hidden', 'true');

        confirmDialog.innerHTML = `
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center p-4">
                        <h5 class="mb-3">Save Changes?</h5>
                        <div class="d-flex justify-content-center">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-success" id="confirmSaveBtn">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(confirmDialog);

        // Initialize the Bootstrap modal
        const modal = new bootstrap.Modal(document.getElementById('saveConfirmModal'));
        modal.show();

        // Handle the confirmation
        document.getElementById('confirmSaveBtn').addEventListener('click', function() {
            // Show loading state
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
            saveButton.disabled = true;

            // Hide and remove the modal
            modal.hide();

            // Submit the form
            form.submit();
        });

        // Remove the modal from the DOM when it's closed
        document.getElementById('saveConfirmModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('saveConfirmModal').remove();
        });
    });
});
