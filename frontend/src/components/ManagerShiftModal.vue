<template>
    <div class="modal fade" :id="modalId" tabindex="-1" :aria-labelledby="modalLabel" aria-hidden="true" ref="modalRef">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" :id="modalLabel">
                        <i class="bi bi-clock-history me-2"></i>
                        {{ isEdit ? 'Editar Turno' : 'Nuevo Turno' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="handleSubmit">
                        <div class="mb-3">
                            <label class="form-label">Administrador *</label>
                            <select v-model="form.manager_id" class="form-select" required>
                                <option value="">Selecciona un manager</option>
                                <option v-for="m in managers" :key="m.id" :value="m.id">
                                    {{ m.first_name }} {{ m.last_name }} (ID: {{ m.id }})
                                </option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Campo *</label>
                            <select v-model="form.field_id" class="form-select" required>
                                <option value="">Selecciona un campo</option>
                                <option v-for="f in fields" :key="f.id" :value="f.id">
                                    {{ f.name }} - {{ f.location }}
                                </option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Día de la semana *</label>
                            <select v-model="form.day_of_week" class="form-select" required>
                                <option v-for="(d, idx) in days" :key="idx" :value="idx">{{ d }}</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col mb-3">
                                <label class="form-label">Hora inicio *</label>
                                <input type="time" v-model="form.start_time" class="form-control" required>
                            </div>
                            <div class="col mb-3">
                                <label class="form-label">Hora fin *</label>
                                <input type="time" v-model="form.end_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Activo</label>
                            <input type="checkbox" v-model="form.active" class="form-check-input ms-2">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nota</label>
                            <textarea v-model="form.note" class="form-control" rows="2"
                                placeholder="Nota opcional..."></textarea>
                        </div>
                        <div v-if="error" class="alert alert-danger mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            {{ error }}
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" @click="handleSubmit" :disabled="submitting">
                        <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="bi bi-check-circle me-2"></i>
                        {{ isEdit ? 'Guardar Cambios' : 'Crear Turno' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, watch, onMounted } from 'vue';
import { Modal } from 'bootstrap';

export default {
    name: 'ManagerShiftModal',
    props: {
        show: Boolean,
        isEdit: Boolean,
        initialData: Object,
        fields: Array,
        managers: Array,
        modalId: { type: String, default: 'managerShiftModal' },
    },
    emits: ['saved', 'closed'],
    setup(props, { emit }) {
        const modalRef = ref(null);
        const modalInstance = ref(null);
        const submitting = ref(false);
        const error = ref(null);
        const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        const modalLabel = `${props.modalId}-label`;
        const form = reactive({
            manager_id: '',
            field_id: '',
            day_of_week: 1,
            start_time: '08:00:00',
            end_time: '16:00:00',
            active: true,
            note: '',
        });

        watch(() => props.initialData, (val) => {
            if (val) Object.assign(form, val);
        }, { immediate: true });

        watch(() => props.show, (val) => {
            if (val && modalRef.value) {
                if (!modalInstance.value) modalInstance.value = new Modal(modalRef.value);
                modalInstance.value.show();
            }
        });

        const handleSubmit = async () => {
            error.value = null;
            if (!form.manager_id || !form.field_id) {
                error.value = 'Debes seleccionar manager y campo';
                return;
            }
            if (!form.start_time || !form.end_time || form.end_time <= form.start_time) {
                error.value = 'La hora de fin debe ser mayor que la de inicio';
                return;
            }
            // Validación duración mínima/máxima (4-8h)
            const [h1, m1] = form.start_time.split(':').map(Number);
            const [h2, m2] = form.end_time.split(':').map(Number);
            const duration = (h2 + m2 / 60) - (h1 + m1 / 60);
            if (duration < 4) {
                error.value = 'El turno debe durar al menos 4 horas';
                return;
            }
            if (duration > 8) {
                error.value = 'El turno no puede durar más de 8 horas';
                return;
            }
            submitting.value = true;
            try {
                await emit('saved', { ...form });
            } catch (e) {
                // Mostrar error real si es posible
                if (e && e.response && (e.response.data?.detail || e.response.data?.message)) {
                    error.value = e.response.data.detail || e.response.data.message;
                } else if (typeof e === 'string') {
                    error.value = e;
                } else {
                    error.value = 'Error guardando turno';
                }
            } finally {
                submitting.value = false;
            }
        };

        onMounted(() => {
            if (props.show && modalRef.value) {
                modalInstance.value = new Modal(modalRef.value);
                modalInstance.value.show();
            }
        });

        return { modalRef, form, error, submitting, days, handleSubmit, modalLabel };
    },
};
</script>

<style scoped>
.modal-content {
    border-radius: 0.5rem;
}
</style>
