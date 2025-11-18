<?php
// filepath: c:\xampp\htdocs\hooping_week\frontend-demo\index.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Reserva - Hooping Week</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="form-wrapper">
            <h1>🏀 Hooping Week - Nueva Reserva</h1>
            <p class="subtitle">Sistema de Reservas Deportivas</p>
            
            <form id="reservationForm">
                <div class="form-group">
                    <label for="applicant_id">ID Usuario (Cédula) *</label>
                    <input type="number" id="applicant_id" name="applicant_id" required 
                           placeholder="Ej: 15" value="15">
                    <small>Usuarios de prueba: 15 (atleta), 17 (admin), 1 (admin)</small>
                </div>

                <div class="form-group">
                    <label for="field_id">Campo *</label>
                    <select id="field_id" name="field_id" required>
                        <option value="">Seleccione un campo</option>
                        <option value="5">Campo 5 - Pista de atletismo</option>
                        <option value="1">Campo 1</option>
                        <option value="2">Campo 2</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Fecha *</label>
                        <input type="date" id="start_date" name="start_date" required 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="start_time">Hora Inicio *</label>
                        <input type="time" id="start_time" name="start_time" required 
                               value="15:00" step="1800">
                        <small>Formato 24h (ej: 15:00)</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="duration_hours">Duración (horas) *</label>
                    <select id="duration_hours" name="duration_hours" required>
                        <option value="0.5">30 minutos</option>
                        <option value="1" selected>1 hora</option>
                        <option value="1.5">1.5 horas</option>
                        <option value="2">2 horas</option>
                        <option value="3">3 horas</option>
                        <option value="4">4 horas</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="purpose">Propósito de la Reserva *</label>
                    <textarea id="purpose" name="purpose" rows="3" required 
                              placeholder="Ej: Entrenamiento de atletismo"></textarea>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    Crear Reserva
                </button>
            </form>

            <div id="result" class="result hidden"></div>
        </div>
    </div>

    <script>
        document.getElementById('reservationForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const resultDiv = document.getElementById('result');
            
            // Deshabilitar botón
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creando reserva...';
            resultDiv.classList.add('hidden');
            
            // Recoger datos del formulario
            const formData = new FormData(e.target);
            const data = {
                applicant_id: parseInt(formData.get('applicant_id')),
                field_id: parseInt(formData.get('field_id')),
                start_date: formData.get('start_date'),
                start_time: formData.get('start_time') + ':00', // Agregar segundos
                duration_hours: parseFloat(formData.get('duration_hours')),
                purpose: formData.get('purpose')
            };
            
            console.log('📤 Enviando:', data);
            
            try {
                // Llamar al endpoint usando PHP como proxy
                const response = await fetch('create_reservation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                console.log('📥 Respuesta:', result);
                
                // Mostrar resultado
                resultDiv.classList.remove('hidden');
                
                if (result.ok) {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = `
                        <h3>✅ Reserva creada exitosamente</h3>
                        <p><strong>ID:</strong> ${result.reservation.id}</p>
                        <p><strong>Estado:</strong> ${result.reservation.status}</p>
                        <p><strong>Inicio:</strong> ${result.reservation.start_datetime}</p>
                        <p><strong>Duración:</strong> ${result.reservation.duration_hours} hora(s)</p>
                        ${result.message ? `<p class="info">${result.message}</p>` : ''}
                    `;
                    
                    // Reset form
                    e.target.reset();
                    document.getElementById('applicant_id').value = '15';
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = `
                        <h3>❌ Error al crear reserva</h3>
                        <p>${result.message || result.error || 'Error desconocido'}</p>
                    `;
                }
            } catch (error) {
                console.error('❌ Error:', error);
                resultDiv.classList.remove('hidden');
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `
                    <h3>❌ Error de conexión</h3>
                    <p>${error.message}</p>
                    <p>Verifica que el broker esté corriendo en http://localhost:5000</p>
                `;
            } finally {
                // Re-habilitar botón
                submitBtn.disabled = false;
                submitBtn.textContent = 'Crear Reserva';
            }
        });

        // Establecer fecha mínima como mañana
        const dateInput = document.getElementById('start_date');
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        dateInput.value = tomorrow.toISOString().split('T')[0];
    </script>
</body>
</html>