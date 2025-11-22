<template>
  <!-- Modal backdrop -->
  <div class="modal fade" id="reservationModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true" ref="modalRef">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="reservationModalLabel">
            <i class="bi bi-plus-circle me-2"></i>
            Nueva Reserva
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="handleSubmit">
            <!-- Campo -->
            <div class="mb-3">
              <label class="form-label">Campo *</label>
              <select v-model="form.field_id" class="form-select" required>
                <option value="">Selecciona un campo</option>
                <option v-for="field in fields" :key="field.id" :value="field.id">
                  {{ field.name }} - {{ field.location }}
                </option>
              </select>
            </div>

            <!-- Solicitante (búsqueda) -->
            <UserSearch 
              label="Solicitante *" 
              placeholder="ID o Email del solicitante"
              @user-found="onUserFound"
              @user-cleared="form.applicant_id = null"
            />

            <!-- Tipo de Actividad -->
            <div class="mb-3">
              <label class="form-label">Tipo de Actividad *</label>
              <select v-model="form.activity_type" class="form-select" required @change="updateDurationHint">
                <option value="practice_individual">Práctica Individual</option>
                <option value="practice_group">Práctica Grupal</option>
                <option value="match_friendly">Partido Amistoso</option>
                <option value="match_championship">Campeonato</option>
              </select>
              <small class="text-muted d-block mt-1">
                <i class="bi bi-info-circle"></i> {{ durationHint }}
              </small>
            </div>

            <!-- Fechas -->
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Inicio *</label>
                <input type="datetime-local" v-model="form.start_datetime" class="form-control" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Fin *</label>
                <input type="datetime-local" v-model="form.end_datetime" class="form-control" required>
              </div>
            </div>

            <!-- Notas -->
            <div class="mb-3">
              <label class="form-label">Notas <small class="text-muted">(opcional)</small></label>
              <textarea v-model="form.notes" class="form-control" rows="3" 
                placeholder="Información adicional sobre la reserva..."></textarea>
            </div>

            <div v-if="error" class="alert alert-danger mb-0">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              {{ error }}
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-2"></i>
            Cancelar
          </button>
          <button 
            type="button" 
            class="btn btn-primary" 
            @click="handleSubmit" 
            :disabled="submitting || !form.applicant_id"
          >
            <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
            <i v-else class="bi bi-check-circle me-2"></i>
            Crear Reserva
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import { getFields, createReservation } from '@/services/api';
import UserSearch from './UserSearch.vue';
import { Modal } from 'bootstrap'; // ✅ Importar Bootstrap Modal

export default {
  name: 'ReservationModal',
  components: { UserSearch },
  emits: ['reservation-saved'],
  setup(props, { emit }) {
    const modalRef = ref(null);
    const modalInstance = ref(null); // ✅ Guardar instancia del modal
    const fields = ref([]);
    const submitting = ref(false);
    const error = ref(null);

    const form = reactive({
      field_id: '',
      applicant_id: null,
      activity_type: 'practice_individual',
      start_datetime: '',
      end_datetime: '',
      notes: '',
    });

    const durationHint = ref('Duración exacta: 1 hora');

    const durationHints = {
      practice_individual: 'Duración exacta: 1 hora',
      practice_group: 'Entre 1 y 2 horas',
      match_friendly: 'Entre 1 y 6 horas',
      match_championship: 'Sin límite de duración',
    };

    const updateDurationHint = () => {
      durationHint.value = durationHints[form.activity_type] || '';
    };

    const onUserFound = (user) => {
      form.applicant_id = user.id;
      console.log('✅ Usuario seleccionado:', user.id, user.email);
    };

    const loadFields = async () => {
      try {
        const response = await getFields();
        fields.value = response.data.fields?.data || response.data.data || [];
        console.log('✅ Campos cargados:', fields.value.length);
      } catch (err) {
        console.error('❌ Error cargando campos:', err);
        error.value = 'No se pudieron cargar los campos disponibles';
      }
    };

    const closeModal = () => {
      // ✅ Limpiar backdrop manualmente
      if (modalInstance.value) {
        modalInstance.value.hide();
      } else if (modalRef.value) {
        const modal = new Modal(modalRef.value);
        modal.hide();
      }
      
      // ✅ FORZAR LIMPIEZA DEL BACKDROP
      setTimeout(() => {
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
          backdrop.remove();
        }
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
      }, 300);
    };

    const handleSubmit = async () => {
      if (!form.applicant_id) {
        error.value = 'Debes buscar y seleccionar un solicitante';
        return;
      }

      if (!form.field_id) {
        error.value = 'Debes seleccionar un campo';
        return;
      }

      error.value = null;
      submitting.value = true;

      try {
        const payload = {
          field_id: parseInt(form.field_id),
          applicant_id: parseInt(form.applicant_id),
          activity_type: form.activity_type,
          start_datetime: form.start_datetime.replace('T', ' ') + ':00',
          end_datetime: form.end_datetime.replace('T', ' ') + ':00',
          notes: form.notes || null,
        };

        console.log('📤 Enviando reserva:', payload);
        
        const response = await createReservation(payload);
        
        console.log('✅ Respuesta completa:', response);
        console.log('✅ response.data:', response.data);
        console.log('✅ response.data.ok:', response.data.ok);
        
        // ✅ Verificar que ok sea true
        if (response.data && response.data.ok === true) {
          console.log('✅ Reserva creada exitosamente');
          
          // ✅ Cerrar modal usando función dedicada
          closeModal();
          
          // Reset form
          Object.assign(form, {
            field_id: '',
            applicant_id: null,
            activity_type: 'practice_individual',
            start_datetime: '',
            end_datetime: '',
            notes: '',
          });
          
          // Limpiar error
          error.value = null;
          
          // ✅ Emitir evento DESPUÉS de cerrar el modal (para evitar race conditions)
          setTimeout(() => {
            emit('reservation-saved');
          }, 300); // Esperar a que termine la animación del modal
          
        } else {
          // Si ok=false o no existe
          const msg = response.data?.message || 'Error desconocido al crear reserva';
          error.value = msg;
          console.error('❌ ok=false o ausente:', msg);
        }
        
      } catch (err) {
        console.error('❌ Error creando reserva:', err);
        console.error('❌ Response completo:', err.response);
        
        const detail = err.response?.data?.detail;
        const message = err.response?.data?.message;
        const errors = err.response?.data?.errors;
        
        if (detail) {
          error.value = detail;
        } else if (message) {
          error.value = message;
        } else if (errors && errors.length > 0) {
          error.value = errors.join(', ');
        } else {
          error.value = 'Error al guardar reserva';
        }
      } finally {
        submitting.value = false;
      }
    };

    onMounted(() => {
      loadFields();
      updateDurationHint();
      
      // ✅ Crear instancia del modal al montar el componente
      if (modalRef.value) {
        modalInstance.value = new Modal(modalRef.value);
      }
    });

    return {
      modalRef,
      fields,
      form,
      durationHint,
      error,
      submitting,
      updateDurationHint,
      onUserFound,
      handleSubmit,
    };
  },
};
</script>

<style scoped>
/* ✅ CONFIAR EN BOOTSTRAP - Solo ajustes de altura y scroll */

.modal-dialog {
  max-height: 90vh;
}

.modal-content {
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  background-color: #fff;
  border-radius: 0.5rem;
  border: none;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.modal-body {
  overflow-y: auto;
  max-height: calc(90vh - 130px); 
}

.modal {
  z-index: 1055;
}

.modal-backdrop {
  z-index: 1050;
}

.form-label {
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.form-control:focus,
.form-select:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
}

textarea.form-control {
  resize: vertical;
}
</style>
