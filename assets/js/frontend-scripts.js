/**
 * Content Audit Frontend Scripts
 *
 * @package ContentAudit
 */

document.addEventListener('DOMContentLoaded', function() {
    const needsChangesYes = document.getElementById('needs_changes_yes');
    const needsChangesNo = document.getElementById('needs_changes_no');
    const supportTicketField = document.getElementById('support_ticket_field');
    const submitButton = document.querySelector('input[name="content_audit_submit"]');
    
    // Function to toggle support ticket field visibility
    function toggleSupportTicketField() {
        if (needsChangesYes.checked) {
            supportTicketField.style.display = 'block';
        } else {
            supportTicketField.style.display = 'none';
            document.getElementById('support_ticket_url').value = '';
        }
    }
    
    // Add event listeners
    needsChangesYes.addEventListener('change', toggleSupportTicketField);
    needsChangesNo.addEventListener('change', toggleSupportTicketField);
    
    // Add focus styles to form elements
    const formInputs = document.querySelectorAll('input[type="url"], input[type="text"]');
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.style.borderColor = '#d9042b';
            this.style.boxShadow = '0 0 0 1px #d9042b';
            this.style.outline = 'none';
        });
        input.addEventListener('blur', function() {
            this.style.borderColor = '#ddd';
            this.style.boxShadow = 'none';
        });
    });
    
    // Initial state
    toggleSupportTicketField();
});
