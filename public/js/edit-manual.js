// Function to update offenses and penalties based on severity
function updateOffensesAndPenalties(selectElement) {
    const severity = selectElement.value;
    const offensesFieldName = selectElement.getAttribute('data-offenses-field');
    const penaltiesFieldName = selectElement.getAttribute('data-penalties-field');
    
    // Find the corresponding offenses and penalties fields
    const offensesField = document.querySelector(`[name="${offensesFieldName}"]`);
    const penaltiesField = document.querySelector(`[name="${penaltiesFieldName}"]`);
    
    if (!offensesField || !penaltiesField) return;
    
    // Set values based on severity
    switch (severity) {
        case 'W': // Low
            offensesField.value = '1st, 2nd, 3rd';
            penaltiesField.value = '1st: Warning, 2nd: Verbal Warning, 3rd: Written Warning';
            break;
        case 'VW': // Medium
            offensesField.value = '1st, 2nd, 3rd';
            penaltiesField.value = '1st: Verbal Warning, 2nd: Written Warning, 3rd: Probation';
            break;
        case 'WW': // High
            offensesField.value = '1st, 2nd, 3rd';
            penaltiesField.value = '1st: Written Warning, 2nd: Probation, 3rd: Expulsion';
            break;
        case 'Exp': // Very High
            offensesField.value = '1st';
            penaltiesField.value = 'Immediate Expulsion';
            break;
        default:
            offensesField.value = '1st, 2nd, 3rd';
            penaltiesField.value = '1st: Warning, 2nd: Verbal Warning, 3rd: Written Warning';
    }
}

// Function to delete a category
function deleteCategory(button) {
    const categorySection = button.closest('.category-section');
    const categoryId = button.getAttribute('data-category-id');
    const categoryName = categorySection.querySelector('.category-name-input').value;
    
    // Create and show a custom confirmation dialog
    const confirmDialog = document.createElement('div');
    confirmDialog.className = 'modal fade';
    confirmDialog.id = 'deleteCategoryModal';
    confirmDialog.setAttribute('tabindex', '-1');
    confirmDialog.setAttribute('aria-labelledby', 'deleteCategoryModalLabel');
    confirmDialog.setAttribute('aria-hidden', 'true');
    
    confirmDialog.innerHTML = `
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <h5 class="mb-3">Delete Category?</h5>
                    <p class="mb-4">This will delete the category and all its violations. This action cannot be undone.</p>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteCategoryBtn">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(confirmDialog);
    
    // Initialize the Bootstrap modal
    const modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
    modal.show();
    
    // Handle the confirmation
    document.getElementById('confirmDeleteCategoryBtn').addEventListener('click', function() {
        // Create a hidden input to mark this category for deletion
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'delete_categories[]';
        hiddenInput.value = categoryId;
        document.getElementById('manualForm').appendChild(hiddenInput);
        
        // Remove the category section from the page
        categorySection.remove();
        
        // Hide and remove the modal
        modal.hide();
        document.getElementById('deleteCategoryModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('deleteCategoryModal').remove();
        });
    });
    
    // Remove the modal from the DOM when it's closed
    document.getElementById('deleteCategoryModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('deleteCategoryModal').remove();
    });
}

// Function to delete an existing violation
function deleteViolation(button) {
    // Get the violation name for the confirmation message
    const row = button.closest('tr');
    const violationName = row.querySelector('textarea').value.substring(0, 50) + (row.querySelector('textarea').value.length > 50 ? '...' : '');
    const violationId = button.getAttribute('data-violation-id');
    
    // Create and show a custom confirmation dialog
    const confirmDialog = document.createElement('div');
    confirmDialog.className = 'modal fade';
    confirmDialog.id = 'deleteConfirmModal';
    confirmDialog.setAttribute('tabindex', '-1');
    confirmDialog.setAttribute('aria-labelledby', 'deleteConfirmModalLabel');
    confirmDialog.setAttribute('aria-hidden', 'true');
    
    confirmDialog.innerHTML = `
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <h5 class="mb-3">Delete Violation?</h5>
                    <p class="mb-4">This action cannot be undone.</p>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(confirmDialog);
    
    // Initialize the Bootstrap modal
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    modal.show();
    
    // Handle the confirmation
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        // Create a hidden input to mark this violation for deletion
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'delete_violations[]';
        hiddenInput.value = violationId;
        document.getElementById('manualForm').appendChild(hiddenInput);
        
        // Remove the row from the table
        row.remove();
        
        // Hide and remove the modal
        modal.hide();
        document.getElementById('deleteConfirmModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('deleteConfirmModal').remove();
        });
    });
    
    // Remove the modal from the DOM when it's closed
    document.getElementById('deleteConfirmModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('deleteConfirmModal').remove();
    });
}

// Function to delete a new violation that hasn't been saved yet
function deleteNewViolation(button) {
    // Get the violation name for the confirmation message
    const row = button.closest('tr');
    const violationName = row.querySelector('textarea').value.substring(0, 50) + (row.querySelector('textarea').value.length > 50 ? '...' : '') || 'New Violation';
    
    // Create and show a custom confirmation dialog
    const confirmDialog = document.createElement('div');
    confirmDialog.className = 'modal fade';
    confirmDialog.id = 'deleteNewConfirmModal';
    confirmDialog.setAttribute('tabindex', '-1');
    confirmDialog.setAttribute('aria-labelledby', 'deleteNewConfirmModalLabel');
    confirmDialog.setAttribute('aria-hidden', 'true');
    
    confirmDialog.innerHTML = `
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <h5 class="mb-3">Delete Violation?</h5>
                    <p class="mb-4">This action cannot be undone.</p>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteNewBtn">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(confirmDialog);
    
    // Initialize the Bootstrap modal
    const modal = new bootstrap.Modal(document.getElementById('deleteNewConfirmModal'));
    modal.show();
    
    // Handle the confirmation
    document.getElementById('confirmDeleteNewBtn').addEventListener('click', function() {
        // Remove the row from the table
        row.remove();
        
        // Hide and remove the modal
        modal.hide();
        document.getElementById('deleteNewConfirmModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('deleteNewConfirmModal').remove();
        });
    });
    
    // Remove the modal from the DOM when it's closed
    document.getElementById('deleteNewConfirmModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('deleteNewConfirmModal').remove();
    });
}
