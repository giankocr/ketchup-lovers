 /**
 * JavaScript para el panel de administración del API de Wallet
 */

jQuery(document).ready(function($) {
    
    // Variables globales
    var $bulkForm = $('#bulk-action-form');
    var $selectAll = $('#cb-select-all-1');
    var $ipCheckboxes = $('input[name="selected_ips[]"]');
    
    // Inicializar funcionalidades
    initBulkActions();
    initFormValidation();
    initTooltips();
    initAutoRefresh();
    
    /**
     * Inicializar acciones en lote
     */
    function initBulkActions() {
        // Select all checkbox
        $selectAll.on('change', function() {
            var isChecked = $(this).is(':checked');
            $ipCheckboxes.prop('checked', isChecked);
            updateBulkActionButton();
        });
        
        // Individual checkboxes
        $ipCheckboxes.on('change', function() {
            updateSelectAllState();
            updateBulkActionButton();
        });
        
        // Bulk action form
        $bulkForm.on('submit', function(e) {
            var $selectedAction = $('select[name="bulk_action"]');
            var selectedAction = $selectedAction.val();
            
            if (!selectedAction) {
                e.preventDefault();
                showNotice('Por favor selecciona una acción', 'error');
                return false;
            }
            
            var checkedBoxes = $ipCheckboxes.filter(':checked');
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                showNotice('Por favor selecciona al menos una IP', 'error');
                return false;
            }
            
            // Confirmación para eliminación
            if (selectedAction === 'delete') {
                var count = checkedBoxes.length;
                var message = count === 1 
                    ? '¿Estás seguro de eliminar esta IP?' 
                    : '¿Estás seguro de eliminar ' + count + ' IPs?';
                
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
    
    /**
     * Actualizar estado del checkbox "Seleccionar todo"
     */
    function updateSelectAllState() {
        var totalCheckboxes = $ipCheckboxes.length;
        var checkedCheckboxes = $ipCheckboxes.filter(':checked').length;
        
        if (checkedCheckboxes === 0) {
            $selectAll.prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $selectAll.prop('indeterminate', false).prop('checked', true);
        } else {
            $selectAll.prop('indeterminate', true);
        }
    }
    
    /**
     * Actualizar botón de acción en lote
     */
    function updateBulkActionButton() {
        var checkedCount = $ipCheckboxes.filter(':checked').length;
        var $applyButton = $('.bulkactions .button');
        
        if (checkedCount > 0) {
            $applyButton.text('Aplicar (' + checkedCount + ')').prop('disabled', false);
        } else {
            $applyButton.text('Aplicar').prop('disabled', true);
        }
    }
    
    /**
     * Inicializar validación de formularios
     */
    function initFormValidation() {
        // Validación de IP
        $('#ip_address').on('blur', function() {
            var ip = $(this).val().trim();
            if (ip && !isValidIP(ip)) {
                showFieldError($(this), 'Formato de IP inválido');
            } else {
                clearFieldError($(this));
            }
        });
        
        // Validación en tiempo real
        $('#ip_address').on('input', function() {
            var ip = $(this).val().trim();
            if (ip && isValidIP(ip)) {
                clearFieldError($(this));
            }
        });
    }
    
    /**
     * Validar formato de IP
     */
    function isValidIP(ip) {
        // Validar IP individual
        var ipv4Regex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
        var ipv6Regex = /^(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$/;
        
        if (ipv4Regex.test(ip) || ipv6Regex.test(ip)) {
            return true;
        }
        
        // Validar CIDR
        var cidrRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/([0-9]|[1-2][0-9]|3[0-2])$/;
        return cidrRegex.test(ip);
    }
    
    /**
     * Mostrar error en campo
     */
    function showFieldError($field, message) {
        clearFieldError($field);
        $field.addClass('error');
        $field.after('<span class="field-error">' + message + '</span>');
    }
    
    /**
     * Limpiar error de campo
     */
    function clearFieldError($field) {
        $field.removeClass('error');
        $field.siblings('.field-error').remove();
    }
    
    /**
     * Inicializar tooltips
     */
    function initTooltips() {
        // Tooltip para ejemplos de IP
        $('.description').each(function() {
            var $desc = $(this);
            var text = $desc.text();
            
            if (text.includes('Ejemplos:')) {
                $desc.append('<span class="tooltip-toggle" title="Ver ejemplos"> [?]</span>');
            }
        });
        
        // Tooltip para información adicional
        $('.kl-wallet-card h3').each(function() {
            var $title = $(this);
            var text = $title.text();
            
            if (text.includes('Formatos') || text.includes('Ejemplos')) {
                $title.append('<span class="tooltip-icon" title="Información adicional"> ℹ</span>');
            }
        });
    }
    
    /**
     * Inicializar auto-refresh
     */
    function initAutoRefresh() {
        // Auto-refresh cada 30 segundos para mostrar estado actual
        setInterval(function() {
            refreshStatus();
        }, 30000);
    }
    
    /**
     * Refrescar estado del API
     */
    function refreshStatus() {
        $.ajax({
            url: klWalletAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'kl_wallet_refresh_status',
                nonce: klWalletAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateStatusDisplay(response.data);
                }
            }
        });
    }
    
    /**
     * Actualizar display de estado
     */
    function updateStatusDisplay(data) {
        // Actualizar IP actual
        $('.status-item:contains("Tu IP actual") .status-value').text(data.current_ip);
        
        // Actualizar estado de restricción
        var $restrictionStatus = $('.status-item:contains("Restricción de IPs") .status-value');
        if (data.restriction_enabled) {
            $restrictionStatus.text('🔒 Habilitada').removeClass('warning').addClass('success');
        } else {
            $restrictionStatus.text('🔓 Deshabilitada').removeClass('success').addClass('warning');
        }
        
        // Actualizar estado de IP permitida
        var $allowedStatus = $('.status-item:contains("Tu IP está permitida") .status-value');
        if (data.is_allowed) {
            $allowedStatus.text('✅ Sí').removeClass('error').addClass('success');
        } else {
            $allowedStatus.text('❌ No').removeClass('success').addClass('error');
        }
    }
    
    /**
     * Mostrar notificación
     */
    function showNotice(message, type) {
        var $notice = $('<div class="kl-wallet-notice ' + type + '">' + message + '</div>');
        $('.kl-wallet-admin-container').prepend($notice);
        
        // Auto-remover después de 5 segundos
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Confirmación personalizada
     */
    function confirmAction(message) {
        return confirm(message);
    }
    
    // Eventos adicionales
    $(document).on('click', '.button-link-delete', function(e) {
        if (!confirmAction(klWalletAdmin.strings.confirmDelete)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Formulario de toggle de restricción
    $('form:has(input[name="kl_wallet_ip_action"][value="toggle_restriction"])').on('submit', function(e) {
        var $button = $(this).find('button[type="submit"]');
        var isEnabling = $button.text().includes('Habilitar');
        
        if (isEnabling && !confirmAction(klWalletAdmin.strings.confirmDisable)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Mejoras de UX
    $('.kl-wallet-card').hover(
        function() {
            $(this).addClass('hover');
        },
        function() {
            $(this).removeClass('hover');
        }
    );
    
    // Copiar IP al portapapeles
    $(document).on('click', '.status-value:contains("Tu IP actual")', function() {
        var ip = $(this).text();
        navigator.clipboard.writeText(ip).then(function() {
            showNotice('IP copiada al portapapeles', 'success');
        });
    });
    
    // Búsqueda en tabla
    $('<input type="text" placeholder="Buscar IPs..." class="regular-text" style="margin-bottom: 10px;">')
        .insertBefore('.wp-list-table')
        .on('input', function() {
            var searchTerm = $(this).val().toLowerCase();
            $('.wp-list-table tbody tr').each(function() {
                var $row = $(this);
                var ipText = $row.find('.column-ip code').text().toLowerCase();
                if (ipText.includes(searchTerm)) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        });
    
});

/**
 * Estilos adicionales para JavaScript
 */
jQuery(document).ready(function($) {
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .field-error {
                color: #dc3232;
                font-size: 12px;
                margin-top: 5px;
                display: block;
            }
            
            .error {
                border-color: #dc3232 !important;
            }
            
            .tooltip-toggle, .tooltip-icon {
                color: #007cba;
                cursor: help;
                font-weight: bold;
            }
            
            .kl-wallet-card.hover {
                box-shadow: 0 4px 8px rgba(0,0,0,.15);
            }
            
            .status-value:contains("Tu IP actual") {
                cursor: pointer;
            }
            
            .status-value:contains("Tu IP actual"):hover {
                background: #e3f2fd !important;
            }
        `)
        .appendTo('head');
});