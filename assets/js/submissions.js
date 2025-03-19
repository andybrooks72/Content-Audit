/**
 * Content Audit Submissions JavaScript
 * 
 * Handles the AJAX CSV export functionality.
 */
(function($) {
    'use strict';

    // Initialize when the DOM is ready
    $(document).ready(function() {
        // Replace the export button with our AJAX version
        $('.content-audit-export-form').on('submit', function(e) {
            e.preventDefault();
            exportCSV();
        });
    });

    /**
     * Handle CSV export via AJAX
     */
    function exportCSV() {
        // Create a form to submit the AJAX request
        var form = $('<form></form>')
            .attr('method', 'post')
            .attr('action', contentAuditData.ajaxUrl)
            .css('display', 'none');

        // Add necessary fields
        form.append($('<input type="hidden" name="action" />').val('content_audit_export_csv'));
        form.append($('<input type="hidden" name="nonce" />').val(contentAuditData.nonce));
        
        // Add filter parameters if they exist
        if (contentAuditData.filterStakeholder) {
            form.append($('<input type="hidden" name="filter_stakeholder" />').val(contentAuditData.filterStakeholder));
        }
        if (contentAuditData.contentType) {
            form.append($('<input type="hidden" name="content_type" />').val(contentAuditData.contentType));
        }

        // Add the form to the body and submit it
        $('body').append(form);
        form.submit();
        
        // Remove the form after submission
        setTimeout(function() {
            form.remove();
        }, 1000);
    }

})(jQuery);
