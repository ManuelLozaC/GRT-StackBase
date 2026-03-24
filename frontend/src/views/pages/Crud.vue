<script setup>
import api from '@/service/api';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref } from 'vue';

const toast = useToast();
const usuarios = ref([]);
const cargando = ref(false);
const guardando = ref(false);
const dialogoUsuario = ref(false);
const dialogoPassword = ref(false);
const errorFormulario = ref('');
const usuarioActual = ref(null);
const passwordReset = ref('');
const catalogos = ref({
    organizaciones: [],
    oficinas: [],
    ciudades: [],
    divisiones: [],
    areas: [],
    cargos: [],
    roles: []
});

const formulario = ref(crearFormularioBase());

onMounted(async () => {
    await Promise.all([cargarCatalogos(), cargarUsuarios()]);
});

function crearAsignacionBase() {
    return {
        id: null,
        oficina_id: null,
        division_id: null,
        area_id: null,
        cargo_id: null,
        jefe_asignacion_laboral_id: null,
        aprobador_asignacion_laboral_id: null,
        es_principal: false,
        activa: true,
        fecha_inicio: null,
        fecha_fin: null,
        roles: []
    };
}

function crearFormularioBase() {
    return {
        id: null,
        organizacion_id: null,
        alias: '',
        nombre_mostrar: '',
        email: '',
        telefono: '',
        password: '',
        es_superusuario: false,
        activo: true,
        persona: {
            nombres: '',
            apellido_paterno: '',
            apellido_materno: '',
            tipo_documento: 'CI',
            numero_documento: '',
            genero: 'masculino',
            fecha_nacimiento: null,
            email: '',
            telefono: '',
            direccion: '',
            ciudad_id: null
        },
        asignaciones: [crearAsignacionBase()]
    };
}

async function cargarCatalogos() {
    const { data } = await api.get('/catalogos/formulario-usuarios');
    catalogos.value = data?.datos ?? catalogos.value;

    if (!formulario.value.organizacion_id && catalogos.value.organizaciones.length) {
        formulario.value.organizacion_id = catalogos.value.organizaciones[0].id;
    }
}

async function cargarUsuarios() {
    cargando.value = true;

    try {
        const { data } = await api.get('/usuarios');
        usuarios.value = data?.datos ?? [];
    } finally {
        cargando.value = false;
    }
}

function abrirNuevo() {
    formulario.value = crearFormularioBase();
    formulario.value.organizacion_id = catalogos.value.organizaciones[0]?.id ?? null;
    errorFormulario.value = '';
    dialogoUsuario.value = true;
}

async function editarUsuario(usuario) {
    const { data } = await api.get(`/usuarios/${usuario.id}`);
    const detalle = data?.datos;

    formulario.value = {
        id: detalle.id,
        organizacion_id: detalle.organizacion_id,
        alias: detalle.alias,
        nombre_mostrar: detalle.nombre_mostrar,
        email: detalle.email,
        telefono: detalle.telefono || '',
        password: '',
        es_superusuario: detalle.es_superusuario,
        activo: detalle.activo,
        persona: {
            nombres: detalle.persona?.nombres || '',
            apellido_paterno: detalle.persona?.apellido_paterno || '',
            apellido_materno: detalle.persona?.apellido_materno || '',
            tipo_documento: detalle.persona?.tipo_documento || 'CI',
            numero_documento: detalle.persona?.numero_documento || '',
            genero: detalle.persona?.genero || 'masculino',
            fecha_nacimiento: detalle.persona?.fecha_nacimiento || null,
            email: detalle.persona?.email || '',
            telefono: detalle.persona?.telefono || '',
            direccion: detalle.persona?.direccion || '',
            ciudad_id: detalle.persona?.ciudad_id || null
        },
        asignaciones:
            detalle.asignaciones_laborales?.length > 0
                ? detalle.asignaciones_laborales.map((asignacion) => ({
                      id: asignacion.id,
                      oficina_id: asignacion.oficina_id,
                      division_id: asignacion.division_id,
                      area_id: asignacion.area_id,
                      cargo_id: asignacion.cargo_id,
                      jefe_asignacion_laboral_id: asignacion.jefe_asignacion_laboral_id,
                      aprobador_asignacion_laboral_id: asignacion.aprobador_asignacion_laboral_id,
                      es_principal: asignacion.es_principal,
                      activa: asignacion.activa,
                      fecha_inicio: asignacion.fecha_inicio,
                      fecha_fin: asignacion.fecha_fin,
                      roles: []
                  }))
                : [crearAsignacionBase()]
    };

    errorFormulario.value = '';
    dialogoUsuario.value = true;
}

function agregarAsignacion() {
    formulario.value.asignaciones.push(crearAsignacionBase());
}

function quitarAsignacion(index) {
    if (formulario.value.asignaciones.length === 1) {
        return;
    }

    formulario.value.asignaciones.splice(index, 1);
}

async function guardarUsuario() {
    guardando.value = true;
    errorFormulario.value = '';

    try {
        const payload = {
            ...formulario.value,
            persona: {
                ...formulario.value.persona,
                email: formulario.value.persona.email || formulario.value.email,
                telefono: formulario.value.persona.telefono || formulario.value.telefono
            }
        };

        if (payload.id) {
            await api.put(`/usuarios/${payload.id}`, payload);
            toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Usuario actualizado correctamente.', life: 3000 });
        } else {
            await api.post('/usuarios', payload);
            toast.add({ severity: 'success', summary: 'Creado', detail: 'Usuario creado correctamente.', life: 3000 });
        }

        dialogoUsuario.value = false;
        await cargarUsuarios();
    } catch (error) {
        errorFormulario.value = error?.response?.data?.mensaje || 'No se pudo guardar el usuario.';
    } finally {
        guardando.value = false;
    }
}

function abrirResetPassword(usuario) {
    usuarioActual.value = usuario;
    passwordReset.value = '';
    dialogoPassword.value = true;
}

async function resetearPassword() {
    if (!usuarioActual.value || !passwordReset.value) {
        return;
    }

    await api.patch(`/usuarios/${usuarioActual.value.id}/resetear-password`, {
        password: passwordReset.value,
        debe_cambiar_password: true
    });

    toast.add({ severity: 'success', summary: 'Contraseña actualizada', detail: 'El usuario deberá cambiarla al ingresar.', life: 3000 });
    dialogoPassword.value = false;
}

function etiquetaEstado(usuario) {
    return usuario.activo ? 'success' : 'danger';
}
</script>

<template>
    <div class="space-y-6">
        <Toast />

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-2">Administración</div>
                    <h2 class="text-2xl font-semibold text-slate-900">Usuarios</h2>
                    <p class="text-slate-500">Gestión base de acceso, persona y asignaciones laborales por oficina.</p>
                </div>

                <Button label="Nuevo usuario" icon="pi pi-plus" @click="abrirNuevo" />
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <DataTable :value="usuarios" :loading="cargando" dataKey="id" paginator :rows="10">
                <Column field="alias" header="Alias"></Column>
                <Column field="nombre_mostrar" header="Nombre"></Column>
                <Column field="email" header="Correo"></Column>
                <Column header="Estado">
                    <template #body="{ data }">
                        <Tag :value="data.activo ? 'Activo' : 'Inactivo'" :severity="etiquetaEstado(data)" />
                    </template>
                </Column>
                <Column header="Oficinas">
                    <template #body="{ data }">
                        {{ data.asignaciones_laborales?.length || 0 }}
                    </template>
                </Column>
                <Column header="Acciones" style="min-width: 12rem">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <Button icon="pi pi-pencil" rounded outlined @click="editarUsuario(data)" />
                            <Button icon="pi pi-key" rounded outlined severity="contrast" @click="abrirResetPassword(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="dialogoUsuario" modal :style="{ width: '72rem' }" :header="formulario.id ? 'Editar usuario' : 'Nuevo usuario'">
            <div class="grid grid-cols-12 gap-6">
                <div class="col-span-12 lg:col-span-6 space-y-4">
                    <h3 class="text-lg font-semibold text-slate-900">Cuenta</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block mb-2 font-medium">Organización</label>
                            <Select v-model="formulario.organizacion_id" :options="catalogos.organizaciones" optionLabel="nombre" optionValue="id" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-2 font-medium">Alias</label>
                            <InputText v-model="formulario.alias" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-2 font-medium">Nombre a mostrar</label>
                            <InputText v-model="formulario.nombre_mostrar" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-2 font-medium">Correo</label>
                            <InputText v-model="formulario.email" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-2 font-medium">Teléfono</label>
                            <InputText v-model="formulario.telefono" class="w-full" />
                        </div>
                        <div class="col-span-2">
                            <label class="block mb-2 font-medium">{{ formulario.id ? 'Nueva contraseña (opcional)' : 'Contraseña inicial' }}</label>
                            <Password v-model="formulario.password" :feedback="false" fluid toggleMask />
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox v-model="formulario.es_superusuario" binary inputId="superusuario" />
                            <label for="superusuario">Superusuario</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox v-model="formulario.activo" binary inputId="activo" />
                            <label for="activo">Activo</label>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 lg:col-span-6 space-y-4">
                    <h3 class="text-lg font-semibold text-slate-900">Persona</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 font-medium">Nombres</label>
                            <InputText v-model="formulario.persona.nombres" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-2 font-medium">Apellido paterno</label>
                            <InputText v-model="formulario.persona.apellido_paterno" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-2 font-medium">Apellido materno</label>
                            <InputText v-model="formulario.persona.apellido_materno" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-2 font-medium">Tipo documento</label>
                            <InputText v-model="formulario.persona.tipo_documento" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-2 font-medium">Número documento</label>
                            <InputText v-model="formulario.persona.numero_documento" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-2 font-medium">Género</label>
                            <Select v-model="formulario.persona.genero" :options="['masculino', 'femenino', 'otro']" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-2 font-medium">Fecha nacimiento</label>
                            <InputText v-model="formulario.persona.fecha_nacimiento" placeholder="YYYY-MM-DD" class="w-full" />
                        </div>
                        <div>
                            <label class="block mb-2 font-medium">Ciudad</label>
                            <Select v-model="formulario.persona.ciudad_id" :options="catalogos.ciudades" optionLabel="nombre" optionValue="id" class="w-full" />
                        </div>
                        <div class="col-span-2">
                            <label class="block mb-2 font-medium">Dirección</label>
                            <InputText v-model="formulario.persona.direccion" class="w-full" />
                        </div>
                    </div>
                </div>

                <div class="col-span-12 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-900">Asignaciones laborales</h3>
                        <Button label="Agregar asignación" icon="pi pi-plus" outlined @click="agregarAsignacion" />
                    </div>

                    <div v-for="(asignacion, index) in formulario.asignaciones" :key="index" class="rounded-2xl border border-slate-200 p-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="font-medium text-slate-800">Asignación {{ index + 1 }}</div>
                            <Button icon="pi pi-trash" text severity="danger" @click="quitarAsignacion(index)" />
                        </div>

                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-12 lg:col-span-4">
                                <label class="block mb-2 font-medium">Oficina</label>
                                <Select v-model="asignacion.oficina_id" :options="catalogos.oficinas" optionLabel="nombre" optionValue="id" class="w-full" />
                            </div>
                            <div class="col-span-12 lg:col-span-4">
                                <label class="block mb-2 font-medium">División</label>
                                <Select v-model="asignacion.division_id" :options="catalogos.divisiones" optionLabel="nombre" optionValue="id" class="w-full" />
                            </div>
                            <div class="col-span-12 lg:col-span-4">
                                <label class="block mb-2 font-medium">Área</label>
                                <Select v-model="asignacion.area_id" :options="catalogos.areas" optionLabel="nombre" optionValue="id" class="w-full" />
                            </div>
                            <div class="col-span-12 lg:col-span-4">
                                <label class="block mb-2 font-medium">Cargo</label>
                                <Select v-model="asignacion.cargo_id" :options="catalogos.cargos" optionLabel="nombre" optionValue="id" class="w-full" />
                            </div>
                            <div class="col-span-12 lg:col-span-4">
                                <label class="block mb-2 font-medium">Fecha inicio</label>
                                <InputText v-model="asignacion.fecha_inicio" placeholder="YYYY-MM-DD" class="w-full" />
                            </div>
                            <div class="col-span-12 lg:col-span-4">
                                <label class="block mb-2 font-medium">Fecha fin</label>
                                <InputText v-model="asignacion.fecha_fin" placeholder="YYYY-MM-DD" class="w-full" />
                            </div>
                            <div class="col-span-12">
                                <label class="block mb-2 font-medium">Roles</label>
                                <MultiSelect v-model="asignacion.roles" :options="catalogos.roles" optionLabel="name" optionValue="name" display="chip" class="w-full" />
                            </div>
                            <div class="col-span-12 flex gap-6">
                                <div class="flex items-center gap-2">
                                    <Checkbox v-model="asignacion.es_principal" binary :inputId="`principal-${index}`" />
                                    <label :for="`principal-${index}`">Principal</label>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Checkbox v-model="asignacion.activa" binary :inputId="`activa-${index}`" />
                                    <label :for="`activa-${index}`">Activa</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-12">
                    <Message v-if="errorFormulario" severity="error" :closable="false">{{ errorFormulario }}</Message>
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" text @click="dialogoUsuario = false" />
                <Button :label="guardando ? 'Guardando...' : 'Guardar'" :loading="guardando" @click="guardarUsuario" />
            </template>
        </Dialog>

        <Dialog v-model:visible="dialogoPassword" modal :style="{ width: '32rem' }" header="Resetear contraseña">
            <div class="space-y-4">
                <p class="text-slate-600">Define una nueva contraseña. El usuario deberá cambiarla al iniciar sesión.</p>
                <Password v-model="passwordReset" :feedback="false" fluid toggleMask />
            </div>
            <template #footer>
                <Button label="Cancelar" text @click="dialogoPassword = false" />
                <Button label="Guardar" @click="resetearPassword" />
            </template>
        </Dialog>
    </div>
</template>
