/**
 * JAVASCRIPT PARA SISTEMA DE RESERVAS
 * ===================================
 * 
 * Maneja el flujo de reservas, validaciones y pagos
 */

// Variables globales para reservas
let contadores = {
    adultos: 2,
    ninos: 0,
    bebes: 0
};

let reservaData = {
    tourId: null,
    fecha: null,
    personas: {},
    cliente: {},
    acompanantes: [],
    metodoPago: null,
    total: 0
};

/**
 * NAVEGACIÓN ENTRE PASOS
 */

function siguientePaso() {
    if (validarPasoActual()) {
        if (pasoActual < 4) {
            pasoActual++;
            mostrarPaso(pasoActual);
            actualizarIndicadores();
            
            // Acciones específicas para cada paso
            if (pasoActual === 3) {
                generarCamposAcompanantes();
            } else if (pasoActual === 4) {
                actualizarResumenFinal();
            }
        } else {
            procesarReserva();
        }
    }
}

function pasoAnterior() {
    if (pasoActual > 1) {
        pasoActual--;
        mostrarPaso(pasoActual);
        actualizarIndicadores();
    }
}

function mostrarPaso(numero) {
    $('.paso').removeClass('activo');
    $(`.paso[data-paso="${numero}"]`).addClass('activo');
    
    // Actualizar botones
    if (numero === 1) {
        $('.btn-anterior').hide();
    } else {
        $('.btn-anterior').show();
    }
    
    if (numero === 4) {
        $('.btn-siguiente').text('Procesar Pago');
    } else {
        $('.btn-siguiente').html('Siguiente <i class="fas fa-arrow-right"></i>');
    }
}

function actualizarIndicadores() {
    // Actualizar indicadores de pasos
    $('.paso-indicador').removeClass('activo completado');
    
    for (let i = 1; i <= 4; i++) {
        if (i < pasoActual) {
            $(`.paso-indicador:nth-child(${i + 1})`).addClass('completado');
        } else if (i === pasoActual) {
            $(`.paso-indicador:nth-child(${i + 1})`).addClass('activo');
        }
    }
    
    // Actualizar barra de progreso
    const progreso = ((pasoActual - 1) / 3) * 100;
    $('.progreso-linea').css('width', progreso + '%');
}

/**
 * VALIDACIONES
 */

function validarPasoActual() {
    switch (pasoActual) {
        case 1:
            return validarPaso1();
        case 2:
            return validarPaso2();
        case 3:
            return validarPaso3();
        case 4:
            return validarPaso4();
        default:
            return false;
    }
}

function validarPaso1() {
    if (!fechaSeleccionada) {
        alert('Por favor selecciona una fecha para el tour');
        return false;
    }
    
    const totalPersonas = contadores.adultos + contadores.ninos + contadores.bebes;
    if (totalPersonas < 1) {
        alert('Debe haber al menos una persona para la reserva');
        return false;
    }
    
    if (contadores.adultos < 1) {
        alert('Debe haber al menos un adulto en la reserva');
        return false;
    }
    
    return true;
}

function validarPaso2() {
    const campos = ['nombre', 'apellido', 'email', 'telefono', 'pais'];
    
    for (let campo of campos) {
        const valor = $(`#${campo}`).val().trim();
        if (!valor) {
            alert(`El campo ${campo} es obligatorio`);
            $(`#${campo}`).focus();
            return false;
        }
    }
    
    // Validar email
    const email = $('#email').val();
    if (!validarEmail(email)) {
        alert('Por favor ingresa un email válido');
        $('#email').focus();
        return false;
    }
    
    return true;
}

function validarPaso3() {
    // Validar información de acompañantes si los hay
    const totalAcompanantes = contadores.ninos + (contadores.adultos - 1);
    
    if (totalAcompanantes > 0) {
        for (let i = 0; i < totalAcompanantes; i++) {
            const nombre = $(`#acompanante-${i}-nombre`).val();
            const apellido = $(`#acompanante-${i}-apellido`).val();
            
            if (!nombre || !apellido) {
                alert(`Por favor completa la información del acompañante ${i + 1}`);
                return false;
            }
        }
    }
    
    return true;
}

function validarPaso4() {
    if (!metodoPagoSeleccionado) {
        alert('Por favor selecciona un método de pago');
        return false;
    }
    
    return true;
}

/**
 * MANEJO DE CONTADORES
 */

function cambiarContador(tipo, cambio) {
    const nuevoValor = contadores[tipo] + cambio;
    
    if (nuevoValor < 0) return;
    
    // Validaciones específicas
    if (tipo === 'adultos' && nuevoValor < 1) {
        alert('Debe haber al menos un adulto');
        return;
    }
    
    const totalPersonas = Object.values(contadores).reduce((a, b) => a + b, 0) + cambio;
    if (totalPersonas > 12) {
        alert('El máximo son 12 personas por reserva');
        return;
    }
    
    contadores[tipo] = nuevoValor;
    $(`#contador-${tipo}`).text(nuevoValor);
    $(`#resumen-${tipo}`).text(nuevoValor);
    
    actualizarResumen();
}

/**
 * SELECCIÓN DE FECHA
 */

function seleccionarFecha(fecha, precio) {
    // Remover selección anterior
    $('.fecha-opcion').removeClass('seleccionada');
    
    // Seleccionar nueva fecha
    event.target.closest('.fecha-opcion').classList.add('seleccionada');
    
    fechaSeleccionada = fecha;
    reservaData.fecha = fecha;
    reservaData.precioBase = precio;
    
    // Formatear fecha para mostrar
    const fechaObj = new Date(fecha);
    const opciones = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    const fechaFormateada = fechaObj.toLocaleDateString('es-ES', opciones);
    
    $('#fecha-seleccionada').text(fechaFormateada);
    actualizarResumen();
}

/**
 * ACTUALIZAR RESUMEN DE PRECIOS
 */

function actualizarResumen() {
    if (!tourData || !fechaSeleccionada) return;
    
    const precioAdulto = tourData.precio;
    const precioNino = tourData.precio * 0.7; // 30% descuento para niños
    const precioBebe = 0; // Bebés gratis
    
    const subtotalAdultos = contadores.adultos * precioAdulto;
    const subtotalNinos = contadores.ninos * precioNino;
    const subtotalBebes = contadores.bebes * precioBebe;
    
    const subtotal = subtotalAdultos + subtotalNinos + subtotalBebes;
    const impuestos = subtotal * 0.18; // 18% IGV
    const total = subtotal + impuestos;
    
    reservaData.subtotal = subtotal;
    reservaData.impuestos = impuestos;
    reservaData.total = total;
    
    // Actualizar display
    $('#subtotal').text('$' + subtotal.toFixed(2));
    $('#impuestos').text('$' + impuestos.toFixed(2));
    $('#total-precio').text('$' + total.toFixed(2));
}

/**
 * GENERAR CAMPOS DE ACOMPAÑANTES
 */

function generarCamposAcompanantes() {
    const totalAcompanantes = (contadores.adultos - 1) + contadores.ninos;
    let html = '';
    
    if (totalAcompanantes === 0) {
        html = '<p>No hay acompañantes que registrar.</p>';
    } else {
        html = '<p>Por favor ingresa la información de tus acompañantes:</p>';
        
        let contador = 0;
        
        // Adultos acompañantes
        for (let i = 1; i < contadores.adultos; i++) {
            html += generarCampoAcompanante(contador, 'Adulto', `Adulto ${i + 1}`);
            contador++;
        }
        
        // Niños
        for (let i = 0; i < contadores.ninos; i++) {
            html += generarCampoAcompanante(contador, 'Niño', `Niño ${i + 1}`);
            contador++;
        }
    }
    
    $('#lista-acompanantes').html(html);
}

function generarCampoAcompanante(index, tipo, titulo) {
    return `
        <div class="acompanante-grupo" style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 8px;">
            <h4>${titulo}</h4>
            <div class="grupo-campos">
                <div class="campo">
                    <label for="acompanante-${index}-nombre">Nombre *</label>
                    <input type="text" id="acompanante-${index}-nombre" required>
                </div>
                <div class="campo">
                    <label for="acompanante-${index}-apellido">Apellido *</label>
                    <input type="text" id="acompanante-${index}-apellido" required>
                </div>
            </div>
            <div class="grupo-campos">
                <div class="campo">
                    <label for="acompanante-${index}-email">Email</label>
                    <input type="email" id="acompanante-${index}-email">
                </div>
                <div class="campo">
                    <label for="acompanante-${index}-documento">Documento</label>
                    <input type="text" id="acompanante-${index}-documento">
                </div>
            </div>
            ${tipo === 'Niño' ? `
                <div class="campo">
                    <label for="acompanante-${index}-edad">Edad</label>
                    <input type="number" id="acompanante-${index}-edad" min="3" max="11">
                </div>
            ` : ''}
        </div>
    `;
}

/**
 * MÉTODOS DE PAGO
 */

function seleccionarMetodoPago(metodo) {
    metodoPagoSeleccionado = metodo;
    
    // Actualizar UI
    $('.metodo-pago').removeClass('seleccionado');
    $(`#pago-${metodo}`).prop('checked', true);
    $(`#pago-${metodo}`).closest('.metodo-pago').addClass('seleccionado');
    
    // Mostrar/ocultar campos específicos
    $('.campos-tarjeta').removeClass('mostrar');
    $(`#campos-${metodo}`).addClass('mostrar');
    
    // Inicializar método específico
    if (metodo === 'paypal') {
        inicializarPayPal();
    }
}

/**
 * INICIALIZACIÓN DE STRIPE
 */

function inicializarStripe() {
    stripe = Stripe('pk_test_TU_CLAVE_PUBLICA_AQUI'); // Reemplazar con tu clave
    const elements = stripe.elements();
    
    cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#424770',
                '::placeholder': {
                    color: '#aab7c4',
                },
            },
        },
    });
    
    cardElement.mount('#card-element');
    
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
}

/**
 * INICIALIZACIÓN DE PAYPAL
 */

function inicializarPayPal() {
    if (!window.paypal) return;
    
    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: reservaData.total.toFixed(2)
                    },
                    description: `Reserva tour: ${tourData.nombre}`
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                procesarPagoPayPal(details);
            });
        },
        onError: function(err) {
            console.error('Error en PayPal:', err);
            alert('Error al procesar el pago con PayPal');
        }
    }).render('#paypal-button-container');
}

/**
 * INICIALIZACIÓN DE MERCADOPAGO
 */

function inicializarMercadoPago() {
    mp = new MercadoPago('TEST-tu-public-key-aqui'); // Reemplazar con tu clave
}

/**
 * PROCESAMIENTO DE RESERVA
 */

function procesarReserva() {
    if (!validarPasoActual()) return;
    
    // Recopilar datos
    recopilarDatosReserva();
    
    // Mostrar loading
    $('#loading').addClass('mostrar');
    $('.formulario-reserva > *:not(#loading)').hide();
    
    // Crear reserva primero
    crearReserva()
        .then(reservaId => {
            reservaData.id = reservaId;
            return procesarPago();
        })
        .then(resultado => {
            mostrarConfirmacion(resultado);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la reserva: ' + error.message);
            $('#loading').removeClass('mostrar');
            $('.formulario-reserva > *:not(#loading)').show();
        });
}

function recopilarDatosReserva() {
    // Datos del cliente
    reservaData.cliente = {
        nombre: $('#nombre').val(),
        apellido: $('#apellido').val(),
        email: $('#email').val(),
        telefono: $('#telefono').val(),
        pais: $('#pais').val(),
        ciudad: $('#ciudad').val(),
        documento: $('#documento').val(),
        comentarios: $('#comentarios').val()
    };
    
    // Datos de personas
    reservaData.personas = {
        adultos: contadores.adultos,
        ninos: contadores.ninos,
        bebes: contadores.bebes,
        total: contadores.adultos + contadores.ninos + contadores.bebes
    };
    
    // Acompañantes
    reservaData.acompanantes = [];
    const totalAcompanantes = (contadores.adultos - 1) + contadores.ninos;
    
    for (let i = 0; i < totalAcompanantes; i++) {
        const acompanante = {
            nombre: $(`#acompanante-${i}-nombre`).val(),
            apellido: $(`#acompanante-${i}-apellido`).val(),
            email: $(`#acompanante-${i}-email`).val(),
            documento: $(`#acompanante-${i}-documento`).val(),
            tipo: i < (contadores.adultos - 1) ? 'Adulto' : 'Niño'
        };
        
        if (acompanante.tipo === 'Niño') {
            acompanante.edad = $(`#acompanante-${i}-edad`).val();
        }
        
        reservaData.acompanantes.push(acompanante);
    }
}

function crearReserva() {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'api/crear_reserva.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(reservaData),
            success: function(response) {
                if (response.success) {
                    resolve(response.data.reserva_id);
                } else {
                    reject(new Error(response.message));
                }
            },
            error: function() {
                reject(new Error('Error de conexión al crear la reserva'));
            }
        });
    });
}

function procesarPago() {
    return new Promise((resolve, reject) => {
        switch (metodoPagoSeleccionado) {
            case 'stripe':
                procesarPagoStripe().then(resolve).catch(reject);
                break;
            case 'paypal':
                // PayPal ya se procesó en el callback
                resolve({ metodo: 'paypal', exito: true });
                break;
            case 'mercadopago':
                procesarPagoMercadoPago().then(resolve).catch(reject);
                break;
            default:
                reject(new Error('Método de pago no válido'));
        }
    });
}

function procesarPagoStripe() {
    return new Promise((resolve, reject) => {
        stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
            billing_details: {
                name: reservaData.cliente.nombre + ' ' + reservaData.cliente.apellido,
                email: reservaData.cliente.email,
            },
        }).then(function(result) {
            if (result.error) {
                reject(new Error(result.error.message));
            } else {
                // Enviar al servidor para procesar
                $.ajax({
                    url: 'api/procesar_pago.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        reserva_id: reservaData.id,
                        metodo_pago: 'stripe',
                        monto: reservaData.total,
                        payment_method_id: result.paymentMethod.id
                    }),
                    success: function(response) {
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(new Error(response.message));
                        }
                    },
                    error: function() {
                        reject(new Error('Error al procesar el pago'));
                    }
                });
            }
        });
    });
}

function procesarPagoMercadoPago() {
    return new Promise((resolve, reject) => {
        // Implementar procesamiento con MercadoPago
        // Esto requiere obtener el token de la tarjeta primero
        
        const cardData = {
            cardNumber: $('#mp-card-number').val().replace(/\s/g, ''),
            cardExpirationMonth: $('#mp-expiry').val().split('/')[0],
            cardExpirationYear: '20' + $('#mp-expiry').val().split('/')[1],
            securityCode: $('#mp-cvc').val(),
            cardholderName: $('#mp-card-name').val()
        };
        
        mp.createCardToken(cardData).then(function(result) {
            if (result.error) {
                reject(new Error(result.error.message));
            } else {
                // Enviar token al servidor
                $.ajax({
                    url: 'api/procesar_pago.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        reserva_id: reservaData.id,
                        metodo_pago: 'mercadopago',
                        monto: reservaData.total,
                        token: result.id
                    }),
                    success: function(response) {
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(new Error(response.message));
                        }
                    },
                    error: function() {
                        reject(new Error('Error al procesar el pago'));
                    }
                });
            }
        }).catch(function(error) {
            reject(new Error('Error al procesar la tarjeta'));
        });
    });
}

function mostrarConfirmacion(resultado) {
    // Redirigir a página de confirmación
    window.location.href = `confirmacion.html?reserva=${reservaData.id}`;
}

/**
 * UTILIDADES
 */

function validarEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}
