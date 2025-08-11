/**
 * JavaScript principal para el Sistema SAM
 * Compatible con EasyPanel y optimizado para producción
 */

(function($) {
    'use strict';
    
    // Configuración global
    window.SAM = window.SAM || {};
    
    SAM.config = {
        healthCheckInterval: 300000, // 5 minutos
        ajaxTimeout: 30000, // 30 segundos
        retryAttempts: 3
    };
    
    // Utilidades generales
    SAM.utils = {
        /**
         * Mostrar notificación
         */
        showNotification: function(message, type) {
            type = type || 'info';
            var alertClass = 'alert-' + type;
            var $alert = $('<div class="alert ' + alertClass + ' alert-dismissible" role="alert">')
                .append('<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>')
                .append(message);
            
            $('.container').first().prepend($alert);
            
            // Auto-dismiss después de 5 segundos
            setTimeout(function() {
                $alert.fadeOut();
            }, 5000);
        },
        
        /**
         * Formatear bytes para lectura humana
         */
        formatBytes: function(bytes, decimals) {
            if (bytes === 0) return '0 Bytes';
            
            var k = 1024;
            var dm = decimals || 2;
            var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        },
        
        /**
         * Validar formularios
         */
        validateForm: function($form) {
            var isValid = true;
            
            $form.find('input[required], select[required], textarea[required]').each(function() {
                var $field = $(this);
                if (!$field.val().trim()) {
                    $field.addClass('has-error');
                    isValid = false;
                } else {
                    $field.removeClass('has-error');
                }
            });
            
            return isValid;
        }
    };
    
    // Monitoreo de salud del sistema
    SAM.health = {
        lastCheck: null,
        status: 'unknown',
        
        /**
         * Verificar estado del sistema
         */
        check: function() {
            $.ajax({
                url: '/health',
                method: 'GET',
                timeout: SAM.config.ajaxTimeout,
                success: function(data) {
                    SAM.health.status = data.status;
                    SAM.health.lastCheck = new Date();
                    SAM.health.updateIndicator(data);
                },
                error: function() {
                    SAM.health.status = 'unhealthy';
                    SAM.health.updateIndicator({
                        status: 'unhealthy',
                        message: 'No se pudo conectar con el servidor'
                    });
                }
            });
        },
        
        /**
         * Actualizar indicador visual
         */
        updateIndicator: function(data) {
            var $indicator = $('.sam-status-indicator');
            if ($indicator.length === 0) return;
            
            $indicator.removeClass('online offline warning')
                     .addClass(data.status === 'healthy' ? 'online' : 
                              data.status === 'unhealthy' ? 'offline' : 'warning');
            
            var title = 'Estado: ' + data.status;
            if (data.message) {
                title += ' - ' + data.message;
            }
            $indicator.attr('title', title);
        },
        
        /**
         * Iniciar monitoreo automático
         */
        startMonitoring: function() {
            // Verificación inicial
            SAM.health.check();
            
            // Verificaciones periódicas
            setInterval(function() {
                SAM.health.check();
            }, SAM.config.healthCheckInterval);
        }
    };
    
    // Manejo de sesiones
    SAM.session = {
        /**
         * Verificar si la sesión está activa
         */
        isActive: function() {
            return $('meta[name="csrf-token"]').length > 0;
        },
        
        /**
         * Renovar token CSRF
         */
        refreshCsrfToken: function() {
            $.get('/site/csrf-token', function(data) {
                if (data.token) {
                    $('meta[name="csrf-token"]').attr('content', data.token);
                    $('input[name="_csrf"]').val(data.token);
                }
            });
        }
    };
    
    // Inicialización cuando el documento esté listo
    $(document).ready(function() {
        
        // Configurar AJAX para incluir token CSRF
        $.ajaxSetup({
            beforeSend: function(xhr, settings) {
                if (!this.crossDomain) {
                    var token = $('meta[name="csrf-token"]').attr('content');
                    if (token) {
                        xhr.setRequestHeader('X-CSRF-Token', token);
                    }
                }
            }
        });
        
        // Iniciar monitoreo de salud si estamos logueados
        if (SAM.session.isActive()) {
            SAM.health.startMonitoring();
        }
        
        // Manejar formularios con validación
        $('form').on('submit', function(e) {
            var $form = $(this);
            if ($form.hasClass('validate-form')) {
                if (!SAM.utils.validateForm($form)) {
                    e.preventDefault();
                    SAM.utils.showNotification('Por favor complete todos los campos requeridos', 'warning');
                }
            }
        });
        
        // Confirmar acciones destructivas
        $('[data-confirm]').on('click', function(e) {
            var message = $(this).data('confirm');
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
        
        // Auto-hide alerts
        $('.alert').each(function() {
            var $alert = $(this);
            if (!$alert.hasClass('alert-permanent')) {
                setTimeout(function() {
                    $alert.fadeOut();
                }, 5000);
            }
        });
        
        // Tooltips
        if ($.fn.tooltip) {
            $('[data-toggle="tooltip"]').tooltip();
        }
        
        // Popovers
        if ($.fn.popover) {
            $('[data-toggle="popover"]').popover();
        }
        
        // Loading states para botones
        $('button[type="submit"], input[type="submit"]').on('click', function() {
            var $btn = $(this);
            var $form = $btn.closest('form');
            
            if (SAM.utils.validateForm($form)) {
                $btn.prop('disabled', true)
                    .html('<span class="sam-loading"></span> Procesando...');
                
                // Re-habilitar después de 10 segundos como fallback
                setTimeout(function() {
                    $btn.prop('disabled', false).html($btn.data('original-text') || 'Enviar');
                }, 10000);
            }
        });
        
        // Guardar texto original de botones
        $('button[type="submit"], input[type="submit"]').each(function() {
            $(this).data('original-text', $(this).html());
        });
        
    });
    
    // Manejar errores AJAX globalmente
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        if (xhr.status === 403) {
            SAM.utils.showNotification('Sesión expirada. Por favor, inicie sesión nuevamente.', 'warning');
            setTimeout(function() {
                window.location.href = '/site/login';
            }, 2000);
        } else if (xhr.status === 500) {
            SAM.utils.showNotification('Error interno del servidor. Por favor, intente nuevamente.', 'danger');
        } else if (xhr.status === 0) {
            SAM.utils.showNotification('Error de conexión. Verifique su conexión a internet.', 'warning');
        }
    });
    
})(jQuery);