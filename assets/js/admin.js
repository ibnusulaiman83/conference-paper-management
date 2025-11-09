/**
 * Admin JavaScript
 * File: assets/js/admin.js
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initDashboardCharts();
        initPapersListFunctionality();
        initStatusUpdateHandlers();
        initExportFunctionality();
        initSearchFilters();
        initBulkActions();
    });
    
    /**
     * Initialize Dashboard Charts
     */
    function initDashboardCharts() {
        if (typeof Chart === 'undefined') {
            console.log('Chart.js not loaded');
            return;
        }
        
        // Status Distribution Pie Chart
        const statusChartCanvas = document.getElementById('statusChart');
        if (statusChartCanvas) {
            const ctx = statusChartCanvas.getContext('2d');
            
            // Chart will be initialized by dashboard.php
            // This is for additional customization
            Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        }
        
        // Monthly Registrations Bar Chart
        const monthlyChartCanvas = document.getElementById('monthlyChart');
        if (monthlyChartCanvas) {
            // Additional chart customization can be added here
        }
    }
    
    /**
     * Initialize Papers List Functionality
     */
    function initPapersListFunctionality() {
        // Quick view functionality
        $('.cms-quick-view').on('click', function(e) {
            e.preventDefault();
            const paperId = $(this).data('paper-id');
            quickViewPaper(paperId);
        });
        
        // Bulk select functionality
        $('#cms-select-all-papers').on('change', function() {
            $('.cms-paper-checkbox').prop('checked', $(this).is(':checked'));
            updateBulkActionsState();
        });
        
        $('.cms-paper-checkbox').on('change', function() {
            updateBulkActionsState();
        });
        
        // Status badge click for quick status change
        $('.status-badge').on('click', function() {
            if (!$(this).hasClass('editable')) return;
            
            const paperId = $(this).closest('tr').find('.cms-paper-checkbox').val();
            showQuickStatusChange(paperId, this);
        });
    }
    
    /**
     * Quick view paper details
     */
    function quickViewPaper(paperId) {
        const $modal = $('#paperDetailsModal');
        const $modalBody = $('#modalBody');
        
        $modalBody.html('<div class="cms-loading"><div class="spinner"></div></div>');
        $modal.show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cms_get_paper_details_admin',
                paper_id: paperId,
                nonce: cmsAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $modalBody.html(response.data.html);
                    initModalFunctionality();
                } else {
                    $modalBody.html('<div class="error"><p>Error loading paper details</p></div>');
                }
            },
            error: function() {
                $modalBody.html('<div class="error"><p>Connection error. Please try again.</p></div>');
            }
        });
    }
    
    /**
     * Initialize modal functionality
     */
    function initModalFunctionality() {
        // PDF viewer
        $('.view-pdf-inline').on('click', function(e) {
            e.preventDefault();
            const pdfUrl = $(this).attr('href');
            openPdfViewer(pdfUrl);
        });
        
        // Download participant pass
        $('.download-pass').on('click', function(e) {
            e.preventDefault();
            const paperId = $(this).data('paper-id');
            downloadParticipantPass(paperId);
        });
    }
    
    /**
     * Initialize status update handlers
     */
    function initStatusUpdateHandlers() {
        // Status update with confirmation
        window.updatePaperStatus = function(paperId, newStatus) {
            const statusNames = {
                'review': 'Under Review',
                'accept': 'Accept',
                'pending_payment': 'Pending Payment',
                'paid': 'Paid',
                'completed': 'Completed',
                'reject': 'Reject'
            };
            
            const statusName = statusNames[newStatus] || newStatus;
            
            if (!confirm('Are you sure you want to change status to: ' + statusName + '?\n\nThis action will send an email notification to the participant.')) {
                return;
            }
            
            const $button = $('button[onclick*="' + paperId + '"]');
            const originalText = $button.text();
            $button.prop('disabled', true).text('Updating...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cms_update_paper_status',
                    paper_id: paperId,
                    status: newStatus,
                    nonce: cmsAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Status updated successfully!', 'success');
                        
                        // Reload after delay
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification('Error: ' + response.data.message, 'error');
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    showNotification('Connection error. Please try again.', 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        };
    }
    
    /**
     * Show quick status change dropdown
     */
    function showQuickStatusChange(paperId, badgeElement) {
        const $badge = $(badgeElement);
        const currentStatus = $badge.data('status');
        
        const statuses = {
            'review': 'Under Review',
            'pending_payment': 'Pending Payment',
            'paid': 'Paid',
            'completed': 'Completed',
            'reject': 'Rejected'
        };
        
        let html = '<select class="quick-status-select" data-paper-id="' + paperId + '">';
        for (let status in statuses) {
            const selected = status === currentStatus ? 'selected' : '';
            html += '<option value="' + status + '" ' + selected + '>' + statuses[status] + '</option>';
        }
        html += '</select>';
        
        $badge.replaceWith(html);
        
        $('.quick-status-select').focus().on('change', function() {
            const newStatus = $(this).val();
            updatePaperStatus(paperId, newStatus);
        }).on('blur', function() {
            location.reload();
        });
    }
    
    /**
     * Initialize export functionality
     */
    function initExportFunctionality() {
        $('#cms-export-papers').on('click', function(e) {
            e.preventDefault();
            exportPapers();
        });
        
        $('#cms-export-participants').on('click', function(e) {
            e.preventDefault();
            exportParticipants();
        });
    }
    
    /**
     * Export papers to CSV
     */
    function exportPapers() {
        const status = $('#statusFilter').val();
        const dateFrom = $('#dateFrom').val();
        const dateTo = $('#dateTo').val();
        
        const params = new URLSearchParams({
            action: 'cms_export_papers',
            nonce: cmsAjax.nonce,
            status: status,
            date_from: dateFrom,
            date_to: dateTo
        });
        
        window.location.href = ajaxurl + '?' + params.toString();
        
        showNotification('Export started. Download will begin shortly.', 'success');
    }
    
    /**
     * Export participants to CSV
     */
    function exportParticipants() {
        const params = new URLSearchParams({
            action: 'cms_export_participants',
            nonce: cmsAjax.nonce
        });
        
        window.location.href = ajaxurl + '?' + params.toString();
        
        showNotification('Export started. Download will begin shortly.', 'success');
    }
    
    /**
     * Initialize search and filters
     */
    function initSearchFilters() {
        // Live search
        let searchTimeout;
        $('#cms-paper-search').on('keyup', function() {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val();
            
            searchTimeout = setTimeout(function() {
                filterPapers(searchTerm);
            }, 500);
        });
        
        // Date range filter
        $('#dateFrom, #dateTo').on('change', function() {
            applyDateFilter();
        });
        
        // Country filter
        $('#countryFilter').on('change', function() {
            applyCountryFilter();
        });
    }
    
    /**
     * Filter papers by search term
     */
    function filterPapers(searchTerm) {
        const $rows = $('.cms-papers-table tbody tr');
        
        if (!searchTerm) {
            $rows.show();
            return;
        }
        
        $rows.each(function() {
            const text = $(this).text().toLowerCase();
            if (text.indexOf(searchTerm.toLowerCase()) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        updateResultsCount();
    }
    
    /**
     * Apply date filter
     */
    function applyDateFilter() {
        const dateFrom = $('#dateFrom').val();
        const dateTo = $('#dateTo').val();
        
        if (!dateFrom && !dateTo) return;
        
        const url = new URL(window.location.href);
        if (dateFrom) url.searchParams.set('date_from', dateFrom);
        if (dateTo) url.searchParams.set('date_to', dateTo);
        
        window.location.href = url.toString();
    }
    
    /**
     * Apply country filter
     */
    function applyCountryFilter() {
        const country = $('#countryFilter').val();
        const url = new URL(window.location.href);
        
        if (country) {
            url.searchParams.set('country', country);
        } else {
            url.searchParams.delete('country');
        }
        
        window.location.href = url.toString();
    }
    
    /**
     * Initialize bulk actions
     */
    function initBulkActions() {
        $('#cms-apply-bulk-action').on('click', function(e) {
            e.preventDefault();
            applyBulkAction();
        });
    }
    
    /**
     * Apply bulk action
     */
    function applyBulkAction() {
        const action = $('#cms-bulk-action').val();
        const selectedPapers = [];
        
        $('.cms-paper-checkbox:checked').each(function() {
            selectedPapers.push($(this).val());
        });
        
        if (selectedPapers.length === 0) {
            alert('Please select at least one paper.');
            return;
        }
        
        if (!action) {
            alert('Please select an action.');
            return;
        }
        
        if (!confirm('Apply action "' + action + '" to ' + selectedPapers.length + ' paper(s)?')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cms_bulk_action',
                bulk_action: action,
                paper_ids: selectedPapers,
                nonce: cmsAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Bulk action completed successfully!', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Connection error. Please try again.', 'error');
            }
        });
    }
    
    /**
     * Update bulk actions button state
     */
    function updateBulkActionsState() {
        const checkedCount = $('.cms-paper-checkbox:checked').length;
        const $bulkAction = $('#cms-bulk-action');
        const $applyButton = $('#cms-apply-bulk-action');
        
        if (checkedCount > 0) {
            $bulkAction.prop('disabled', false);
            $applyButton.prop('disabled', false);
            $('#cms-selected-count').text(checkedCount + ' selected');
        } else {
            $bulkAction.prop('disabled', true);
            $applyButton.prop('disabled', true);
            $('#cms-selected-count').text('');
        }
    }
    
    /**
     * Update results count
     */
    function updateResultsCount() {
        const visible = $('.cms-papers-table tbody tr:visible').length;
        const total = $('.cms-papers-table tbody tr').length;
        
        $('#cms-results-count').text('Showing ' + visible + ' of ' + total + ' papers');
    }
    
    /**
     * Open PDF viewer
     */
    function openPdfViewer(pdfUrl) {
        const viewerHtml = `
            <div id="pdf-viewer-modal" class="cms-modal" style="display: block;">
                <div class="modal-content modal-large" style="max-width: 90%; height: 90vh;">
                    <span class="close" onclick="closePdfViewer()">&times;</span>
                    <h2>Paper Document</h2>
                    <iframe src="${pdfUrl}" style="width: 100%; height: calc(100% - 60px); border: none;"></iframe>
                </div>
            </div>
        `;
        
        $('body').append(viewerHtml);
    }
    
    /**
     * Close PDF viewer
     */
    window.closePdfViewer = function() {
        $('#pdf-viewer-modal').remove();
    };
    
    /**
     * Download participant pass
     */
    function downloadParticipantPass(paperId) {
        window.open(ajaxurl + '?action=cms_download_pass&paper_id=' + paperId + '&nonce=' + cmsAjax.nonce, '_blank');
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type) {
        const colors = {
            success: '#4CAF50',
            error: '#f44336',
            warning: '#FF9800',
            info: '#2196F3'
        };
        
        const $notification = $('<div>')
            .addClass('cms-notification')
            .css({
                position: 'fixed',
                top: '32px',
                right: '20px',
                padding: '15px 20px',
                backgroundColor: colors[type] || colors.info,
                color: 'white',
                borderRadius: '4px',
                boxShadow: '0 2px 8px rgba(0,0,0,0.2)',
                zIndex: '100000',
                minWidth: '250px',
                animation: 'slideInRight 0.3s ease-out'
            })
            .html('<strong>' + message + '</strong>')
            .appendTo('body');
        
        setTimeout(function() {
            $notification.fadeOut(400, function() {
                $(this).remove();
            });
        }, 4000);
    }
    
    /**
     * Confirm before leaving page with unsaved changes
     */
    let hasUnsavedChanges = false;
    
    $('form').on('change', 'input, select, textarea', function() {
        hasUnsavedChanges = true;
    });
    
    $('form').on('submit', function() {
        hasUnsavedChanges = false;
    });
    
    $(window).on('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            const message = 'You have unsaved changes. Are you sure you want to leave?';
            e.returnValue = message;
            return message;
        }
    });
    
    /**
     * Auto-save draft functionality
     */
    function initAutoSave() {
        if ($('#post_type').val() !== 'conference_paper') return;
        
        let autoSaveTimeout;
        
        $('input, textarea, select').on('change', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(function() {
                if (typeof wp !== 'undefined' && wp.autosave) {
                    wp.autosave.server.triggerSave();
                    showNotification('Draft saved', 'info');
                }
            }, 3000);
        });
    }
    
    initAutoSave();
    
    /**
     * Add CSS animations
     */
    $('<style>')
        .text(`
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            .cms-loading {
                text-align: center;
                padding: 40px;
            }
            
            .spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #4CAF50;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
                margin: 0 auto;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .quick-status-select {
                font-size: 12px;
                padding: 4px 8px;
                border-radius: 4px;
                border: 1px solid #ddd;
            }
        `)
        .appendTo('head');
    
})(jQuery);