# Sistema de Manejo Institucional

Plataforma web de gestión escolar **multi-institución** desarrollada con **Laravel 13** y **PHP 8.3**. Permite administrar instituciones, docentes, cursos, materias y alumnos, además de registrar asistencias y calificaciones, con control de acceso por roles y autenticación en dos pasos (2FA).

---

## Índice

- [Funciones principales](#funciones-principales)
- [Roles y permisos](#roles-y-permisos)
- [Seguridad](#seguridad)
- [Eliminación de registros](#eliminación-de-registros)
- [Tecnologías](#tecnologías)
- [Instalación](#instalación)
- [Usuarios de demostración](#usuarios-de-demostración)
- [Pruebas automatizadas](#pruebas-automatizadas)
- [Estructura del proyecto](#estructura-del-proyecto)

---

## Funciones principales

### 🏫 Gestión multi-institución
- Alta, edición y eliminación de instituciones educativas (nombre, código y color identificatorio).
- **Selector de institución activa**: toda la información (cursos, alumnos, asistencias, notas) se filtra según la institución seleccionada.

### 📊 Panel de control (Dashboard)
- **Administrador**: métricas generales de la institución (alumnos, docentes, cursos, asistencia promedio, etc.).
- **Docente**: métricas de sus propias clases y materias asignadas.

### 👩‍🏫 Docentes
- Directorio de docentes con alta, edición y eliminación.
- Asignación de docentes a instituciones y materias.

### 📚 Cursos y materias
- Creación de cursos por año académico y división/sección.
- Alta de materias dentro de cada curso, con docente titular asignado.

### 🎓 Alumnos
- Registro completo de alumnos: legajo, DNI, nombre, correo, curso y estado.
- Búsqueda y filtro por curso.
- **Ficha individual** del alumno con su historial de asistencias y calificaciones agrupadas por materia.

### ✅ Control de asistencias
- Toma de asistencia por **curso y fecha** (presente, ausente, tarde, etc.).
- Edición de registros existentes y eliminación de la asistencia de un día cargada por error.

### 📝 Calificaciones
- Creación de evaluaciones (parciales, trabajos prácticos, finales) por materia.
- **Matriz de notas** para cargar las calificaciones de todo el curso en una sola pantalla.

### 📥 Importación de planillas
- Carga de planillas académicas en formato **CSV/TXT** o pegando el contenido directamente.
- Crea o actualiza automáticamente alumnos, curso, materia, evaluación, asistencias y notas.
- Muestra un resumen de la importación (alumnos creados/actualizados, asistencias y notas registradas).

---

## Roles y permisos

El sistema cuenta con dos roles:

| Función | Administrador | Docente |
| :--- | :---: | :---: |
| Ver dashboard | ✅ General | ✅ Solo sus clases |
| Gestionar instituciones | ✅ | ❌ |
| Gestionar docentes | ✅ | ❌ |
| Crear / eliminar cursos y materias | ✅ | ❌ |
| Ver cursos y materias | ✅ Todos | ✅ Solo los asignados |
| Ver alumnos y fichas | ✅ Todos | ✅ Solo de sus cursos |
| Tomar asistencia | ✅ | ✅ Solo en sus cursos |
| Cargar calificaciones | ✅ | ✅ Solo en sus materias |
| Importar planillas | ✅ | ✅ Solo en sus materias |
| Eliminar registros | ✅ | ❌ |

> El menú lateral se adapta automáticamente según el rol: el docente no ve los módulos exclusivos del administrador. Si intenta acceder por URL, recibe un error **403 (Acceso denegado)**.

---

## Seguridad

- **Inicio de sesión con credenciales** (correo y contraseña), con opción "Recordarme".
- **Límite de intentos** de inicio de sesión para prevenir ataques de fuerza bruta.
- **Autenticación en dos pasos (2FA)** opcional, compatible con Google Authenticator, Microsoft Authenticator, Authy, etc.:
  - Activación mediante código QR o clave manual.
  - Confirmación con contraseña y código de 6 dígitos.
  - **Códigos de recuperación** de emergencia (de un solo uso) y posibilidad de regenerarlos.
  - Desactivación protegida por contraseña.
- Toda la configuración de seguridad se gestiona desde la sección **Seguridad** del menú de usuario.

---

## Eliminación de registros

El administrador puede eliminar:

- Instituciones
- Docentes
- Cursos y materias
- Alumnos
- Evaluaciones (con sus notas)
- Asistencias de un curso en una fecha

**Antes de cada eliminación se muestra un mensaje de confirmación** que explica qué se va a borrar y qué datos relacionados se verán afectados. La acción solo se ejecuta si el usuario confirma.

Protecciones incluidas:
- Al eliminar un alumno se borran también sus asistencias y calificaciones.
- Al eliminar un docente, sus materias quedan marcadas como *"Docente Titular (Por asignar)"*.
- No se puede eliminar la **última institución** del sistema.
- El administrador **no puede eliminar su propia cuenta**.

---

## Tecnologías

- **Backend**: Laravel 13, PHP 8.3
- **Base de datos**: SQLite (configurable a MySQL/PostgreSQL)
- **Frontend**: Blade, Tailwind CSS, Alpine.js
- **Pruebas**: PHPUnit
- **Estilo de código**: Laravel Pint

---

## Instalación

### Requisitos
- PHP 8.3 o superior
- Composer

### Pasos

```bash
# 1. Clonar el repositorio
git clone https://github.com/GerCroz/Sistema-de-manejo-Institucional.git
cd Sistema-de-manejo-Institucional

# 2. Instalar dependencias
composer install

# 3. Configurar el entorno
cp .env.example .env
php artisan key:generate

# 4. Crear la base de datos SQLite (en Windows: type nul > database\database.sqlite)
touch database/database.sqlite

# 5. Ejecutar migraciones y cargar datos de demostración
php artisan migrate --seed

# 6. Iniciar el servidor
php artisan serve
```

Luego abrí `http://127.0.0.1:8000` en el navegador.

> Verificá que el archivo `.env` tenga `CACHE_STORE=file` (o ejecutá las migraciones para usar la caché en base de datos).

---

## Usuarios de demostración

Creados automáticamente al ejecutar `php artisan migrate --seed`:

| Rol | Correo | Contraseña |
| :--- | :--- | :--- |
| Administrador | `admin@escuela.edu` | `password123` |
| Docente | `carlos.mendoza@escuela.edu` | `password123` |
| Docente | `marcela.soto@escuela.edu` | `password123` |

> ⚠️ Cambiá estas contraseñas antes de usar el sistema en producción.

---

## Pruebas automatizadas

```bash
php artisan test
```

La suite cubre:
- Inicio de sesión, cierre de sesión y 2FA.
- Restricciones de acceso por rol.
- Alcance de datos del docente.
- Eliminación de registros y sus protecciones.
- Importación de planillas.

---

## Estructura del proyecto

```
app/
├── Http/
│   ├── Controllers/     # Controladores de cada módulo
│   └── Middleware/      # EnsureUserHasRole (control de roles)
├── Models/              # Institution, User, Course, Subject, Student, Attendance, Evaluation, Grade
└── Services/            # TwoFactorAuthService, AttendanceSheetImportService
database/
├── migrations/          # Estructura de la base de datos
└── seeders/             # Datos de demostración
resources/views/         # Vistas Blade por módulo
routes/web.php           # Rutas agrupadas por rol
tests/                   # Pruebas Feature y Unit
```

---

## Autor

**Germán Crozy** — [GerCroz](https://github.com/GerCroz)
