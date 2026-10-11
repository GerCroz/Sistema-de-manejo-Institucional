@extends('layouts.app')

@section('title', 'Importación de Planillas Académicas')

@section('content')
<div class="space-y-8 max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Módulo Automatizado de Carga
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Importar Planilla de Cátedra</h1>
            <p class="text-slate-400 text-sm mt-1">Carga masiva de nómina de alumnos, asistencias por fecha y calificaciones de evaluaciones parciales (PP).</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('students.index') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium border border-slate-700/60 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Ver Alumnos
            </a>
            <a href="{{ route('grades.index') }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Ver Calificaciones
            </a>
        </div>
    </div>

    <!-- Alert / Result Section -->
    @if(session('import_result'))
        @php $res = session('import_result'); @endphp
        <div class="p-6 rounded-2xl bg-emerald-950/40 border border-emerald-500/30 backdrop-blur-xl animate-fade-in shadow-2xl shadow-emerald-950/50">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-emerald-300">¡Importación Exitosa de la Planilla!</h3>
                    <p class="text-emerald-200/80 text-sm mt-0.5">Se procesaron todos los registros de alumnos, asistencias y notas sin conflictos.</p>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5">
                        <div class="p-4 rounded-xl bg-slate-900/60 border border-emerald-500/20">
                            <span class="text-xs text-slate-400 block font-medium">Alumnos Procesados</span>
                            <span class="text-2xl font-black text-white mt-1 block">{{ $res['students_total'] }}</span>
                            <span class="text-[11px] text-emerald-400">{{ $res['students_created'] }} creados / {{ $res['students_updated'] }} actualizados</span>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-900/60 border border-emerald-500/20">
                            <span class="text-xs text-slate-400 block font-medium">Asistencias Creadas</span>
                            <span class="text-2xl font-black text-white mt-1 block">{{ $res['attendances_count'] }}</span>
                            <span class="text-[11px] text-indigo-400">9 fechas procesadas</span>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-900/60 border border-emerald-500/20">
                            <span class="text-xs text-slate-400 block font-medium">Notas de Parcial (PP)</span>
                            <span class="text-2xl font-black text-white mt-1 block">{{ $res['grades_count'] }}</span>
                            <span class="text-[11px] text-amber-400">{{ $res['evaluation_title'] }}</span>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-900/60 border border-emerald-500/20">
                            <span class="text-xs text-slate-400 block font-medium">Curso y Asignatura</span>
                            <span class="text-xs font-bold text-white mt-2 block truncate">{{ $res['subject_name'] }}</span>
                            <span class="text-[11px] text-slate-400 truncate block">{{ $res['course_name'] }}</span>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-emerald-500/20 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2 text-slate-300">
                            <span class="font-semibold text-emerald-400">Fechas registradas:</span>
                            @foreach($res['dates_imported'] as $dt)
                                <span class="px-2 py-0.5 rounded bg-slate-800/80 border border-slate-700/50 text-slate-300 font-mono">{{ $dt }}</span>
                            @endforeach
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('students.index', ['course_id' => $res['course_id']]) }}" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold transition-all">
                                Explorar Alumnos &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-950/40 border border-rose-500/30 text-rose-300 text-sm">
            <div class="font-bold flex items-center gap-2 mb-1">
                <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Ocurrió un error al procesar el archivo:
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-xs text-rose-200/80">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">
            <form action="{{ route('import.process') }}" method="POST" enctype="multipart/form-data" class="glass-card p-6 md:p-8 rounded-2xl border border-slate-800 space-y-6">
                @csrf

                <!-- Selección de Destino -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Institución Destino</label>
                        <select name="institution_id" class="w-full bg-slate-800/90 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach($institutions as $inst)
                                <option value="{{ $inst->id }}" {{ $inst->id == $activeInstId ? 'selected' : '' }}>
                                    {{ $inst->name }} ({{ $inst->code }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Si la planilla tiene cabecera universitaria (Rectorado), se vinculará aquí.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Curso / Período (Opcional)</label>
                        <select name="course_id" class="w-full bg-slate-800/90 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Detección Automática desde la Planilla --</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->name }} ({{ $course->academic_year }})</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Por defecto creará o vinculará automáticamente el curso según el archivo.</p>
                    </div>
                </div>

                <!-- Tabs: Archivo vs Texto Pegado -->
                <div x-data="{ tab: 'file' }" class="space-y-4">
                    <div class="flex items-center gap-2 p-1 bg-slate-800/60 rounded-xl border border-slate-700/60 w-fit">
                        <button type="button" @click="tab = 'file'" :class="tab === 'file' ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-400 hover:text-white'" class="px-4 py-1.5 rounded-lg text-xs transition-all">
                            Subir Archivo (.csv / .txt)
                        </button>
                        <button type="button" @click="tab = 'paste'" :class="tab === 'paste' ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-400 hover:text-white'" class="px-4 py-1.5 rounded-lg text-xs transition-all">
                            Pegar Texto de Planilla
                        </button>
                    </div>

                    <!-- Pestaña Archivo -->
                    <div x-show="tab === 'file'" class="space-y-3">
                        <div class="border-2 border-dashed border-slate-700/80 hover:border-indigo-500/60 transition-all rounded-2xl p-8 text-center bg-slate-800/20 group">
                            <svg class="w-12 h-12 text-slate-500 group-hover:text-indigo-400 mx-auto transition-colors mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            <label class="block text-sm font-semibold text-slate-200 cursor-pointer">
                                <span>Seleccionar archivo CSV o TXT</span>
                                <input type="file" name="file" accept=".csv,.txt" class="hidden" @change="$el.closest('div').querySelector('.file-name').innerText = $el.files[0]?.name || ''">
                            </label>
                            <p class="text-xs text-slate-400 mt-1">Formato separado por punto y coma (;) exportado de Excel o Guaraní/Siu.</p>
                            <p class="file-name text-xs text-indigo-400 font-semibold mt-2"></p>
                        </div>
                    </div>

                    <!-- Pestaña Pegar Texto -->
                    <div x-show="tab === 'paste'" class="space-y-3" style="display: none;">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-semibold text-slate-300">Contenido delimitado por punto y coma (;)</label>
                            <button type="button" onclick="loadSampleData()" class="text-xs text-indigo-400 hover:text-indigo-300 underline font-medium">
                                Cargar ejemplo de Sistemas Operativos
                            </button>
                        </div>
                        <textarea id="rawContentTextarea" name="raw_content" rows="10" placeholder="PLANILLA DE ASISTENCIA – SISTEMAS OPERATIVOS Y REDES;;&#10;Nro;Legajo;Apellido y Nombres;...&#10;1;7253;ACOSTA, KARINA AILEN;..." class="w-full bg-slate-900/90 border border-slate-700 rounded-xl p-3 text-xs font-mono text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-between pt-4 border-t border-slate-800">
                    <span class="text-xs text-slate-400">Se validarán los 70 registros de alumnos y sus calificaciones.</span>
                    <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold text-sm shadow-xl shadow-indigo-600/30 transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Procesar e Importar Planilla
                    </button>
                </div>
            </form>
        </div>

        <!-- Info / Sidebar Guide -->
        <div class="space-y-6">
            <div class="glass-card p-6 rounded-2xl border border-slate-800 space-y-4">
                <h3 class="font-bold text-white text-base flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Estructura Reconocida
                </h3>
                <ul class="space-y-3 text-xs text-slate-300">
                    <li class="flex items-start gap-2">
                        <span class="w-5 h-5 rounded-md bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold flex-shrink-0">1</span>
                        <span><strong>Cabecera:</strong> Asignatura (ej. <em>Sistemas Operativos y Redes - 208</em>), Ciclo Lectivo (<em>2026 - 2do Cuatrimestre</em>) y Ubicación.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="w-5 h-5 rounded-md bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold flex-shrink-0">2</span>
                        <span><strong>Alumnos:</strong> Identificación por <em>Legajo</em>, número de orden y separación automática de <em>Apellido y Nombres</em>.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="w-5 h-5 rounded-md bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold flex-shrink-0">3</span>
                        <span><strong>Asistencias:</strong> Detección de clases de agosto (10, 17, 24, 31), septiembre (7, 14, 21 Feriado, 28) y octubre (5).</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="w-5 h-5 rounded-md bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold flex-shrink-0">4</span>
                        <span><strong>Primer Parcial (PP):</strong> Asignación directa a la tabla de notas (valores como <code>16</code>, <code>77,5</code>, <code>100</code>).</span>
                    </li>
                </ul>
            </div>

            <!-- Artisan Command Helper -->
            <div class="glass-card p-6 rounded-2xl border border-slate-800 space-y-3">
                <h4 class="text-xs font-bold text-slate-200 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Comando de Consola Artisan
                </h4>
                <p class="text-xs text-slate-400">También puedes ejecutar la importación directamente en el servidor mediante la terminal:</p>
                <div class="p-3 bg-slate-900 rounded-xl border border-slate-800 text-[11px] font-mono text-emerald-400 select-all">
                    php artisan school:import-sheet
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadSampleData() {
    const sample = `PLANILLA DE ASISTENCIA – SISTEMAS OPERATIVOS Y REDES;;;;;;;;;;;;
Año Académico;2026;Período Lectivo;2do Cuatrimestre;;Clases desde;31/08/2026;;;;;;
Ubicación;Rectorado;Modalidad;Presencial;;Horario;Lun 15:00 a 17:00 - Teórico-Práctica;;;;;;
Actividad;SISTEMAS OPERATIVOS Y REDES (208);;;;;;;;;;;
;;;;;;;;;;;;
Nro;Legajo;Apellido y Nombres;Inasist (Just);10, 17,24,31 de ago;7-sept;14-sept;21-sept;28-sept;5-oct;PP;19-oct;26-oct
1;7253;ACOSTA, KARINA AILEN;0.00 (0.00);4;1;1;F;1;1;16;;
2;8007;ALARCON, PATRICIO YAMIL;0.00 (0.00);3;1;1;F;1;1;77,5;;
3;;AQUINO, OMAR IVAN;0.00 (0.00);4;1;1;F;1;1;40,5;;
4;;ARCE, IRINA SOLEDAD;;3;1;1;F;1;1;62,5;;
5;;ARCE, ROBERTO CARLOS;0.00 (0.00);2;0;1;F;0;1;55;;
6;;AREVALO, ANTONIO JUAN;0.00 (0.00);4;1;1;F;0;1;58;;
7;;BAISCH, MAXIMILIANO;0.00 (0.00);4;1;1;F;1;1;70,5;;
8;;BARRIOS, MIQUEAS;;4;1;1;F;1;1;85,5;;
9;;BENITEZ GOMEZ, LAUTARO;0.00 (0.00);0;0;0;F;1;0;;;
10;8010;BENITEZ, IVAN ARIEL;0.00 (0.00);3;1;1;F;1;1;70;;
11;;BLANCO, GERARDO;;4;1;1;F;0;1;92,5;;
12;;BOGADO, GUSTAVO ARIEL;;4;1;1;F;1;1;100;;
13;;BOGADO, RITA BEATRIZ;;4;1;1;F;1;1;80;;
14;;CABRERA, GUSTAVO;;4;1;1;F;1;1;100;;
15;;CABRERA, MARCOS;0.00 (0.00);4;1;1;F;1;1;80;;
16;;CAMACHO, EXEQUIEL;;3;1;1;F;0;1;95;;
17;;CAMPUZANO, BRENDA;0.00 (0.00);4;1;0;F;1;1;85;;
18;7179;CARDOZO, EZEQUIEL;0.00 (0.00);2;1;0;F;1;1;37;;
19;7180;CARDOZO, JUAN IGNACIO;0.00 (0.00);3;0;1;F;1;1;78;;
20;;CASALES, GUSTAVO;;4;1;1;F;1;1;58;;
21;;CASONES, LUCAS GASTÓN;;3;0;0;F;1;0;;;
22;;CERRIZUELA, CONSTANZA;;4;1;1;F;1;1;53;;
23;;CHAVEZ, EDUARDO RENE;;4;0;1;F;1;1;68;;
24;;CHAVEZ, NAHUEL SAMUEL;;4;1;1;F;1;1;85;;
25;;DORREGO, AGUSTINA;0.00 (0.00);2;0;1;F;1;1;100;;
26;;FERNANDEZ, MARIA;0.00 (0.00);3;1;1;F;1;1;78;;
27;;GALEANO, ENZO RENE;;4;1;1;F;1;1;35,5;;
28;;GALEANO, FABIO EZEQUIEL;;4;1;1;F;1;1;50,5;;
29;;GAMARRA, ROMINA;0.00 (0.00);4;1;1;F;1;1;100;;
30;;GARCÍA, JOEL AXEL;0.00 (0.00);4;1;1;F;1;1;75;;
31;;GARCIA, NOELIA RAQUEL;;0;0;0;F;0;0;;;
32;;GARCIA, SEBASTIAN;;4;1;0;F;0;1;90;;
33;;GAVILÁN, FELIPE OSCAR;;4;1;1;F;1;1;69,5;;
34;;GOMEZ, EDGAR EZEQUIEL;0.00 (0.00);4;1;1;F;1;1;62,5;;
35;;GOMEZ, ENZO AGUSTIN;0.00 (0.00);2;1;1;F;1;1;58;;
36;;GOMEZ, GRISELDA;0.00 (0.00);4;1;1;F;1;1;85;;
37;1300;GON, FACUNDO SEBASTIAN;;3;1;1;F;1;1;80,5;;
38;;INSFRAN, BELEN MONICA;;4;1;0;F;0;1;95;;
39;;JARA, MARCELO LUIS;0.00 (0.00);4;1;1;F;1;1;72,5;;
40;;JURCZUK, IVAN;0.00 (0.00);3;1;1;F;1;1;57;;
41;;KUJARCHI, GUIDO EXEQUIEL;0.00 (0.00);4;1;0;F;1;1;90;;
42;;LARROZA, MATIAS;;0;0;0;F;0;0;;;
43;;LEGUIZAMON, HUGO;;0;0;0;F;0;0;;;
44;;LEIVA, ALEJANDRO IVAN;0.00 (0.00);4;1;1;F;1;1;41;;
45;;LEZCANO, MAXIMILIANO;0.00 (0.00);4;1;1;F;0;0;;;
46;;LOPEZ, CARLOS FEDERICO;;4;0;0;F;1;1;80,5;;
47;1333;LOPEZ, PATRICIA RAMONA;0.00 (0.00);0;0;0;F;0;0;;;
48;1350;MARTINEZ, JOSE IGNACIO;;3;1;1;F;1;1;90;;
49;;MONZON, BRIAN NAHUEL;;4;1;1;F;1;1;100;;
50;;OLIVERA, SOFIA;;4;1;0;F;0;1;60,5;;
51;;ORTOLA GONZALEZ, EDUARDO;0.00 (0.00);3;1;1;F;1;1;75,5;;
52;;OVIEDO, FABRICIO GABRIEL;;4;1;0;F;0;0;;;
53;8085;OZUNA, OCTAVIO ADRIAN;;4;1;1;F;0;1;43;;
54;;PAEZ, LEANDRO SAMUEL;0.00 (0.00);4;1;1;F;1;1;50,5;;
55;;PALACIO, RAMIRO;;4;1;1;F;1;1;100;;
56;;PERNOCHI, DEMIAN;0.00 (0.00);4;1;1;F;1;1;85;;
57;;PINTOS, MARIPAZ;0.00 (0.00);4;0;0;F;1;1;77,5;;
58;;PRANTTE, LUCAS MAURICIO;0.00 (0.00);4;1;1;F;1;1;65,5;;
59;;RAMIREZ, SABA EMILIANO;;3;0;0;F;0;0;;;
60;;RODAS, EDUARDO CESAR;0.00 (0.00);3;0;0;F;0;0;;;
61;1432;ROMERO, JIMENA MAGALI;0.00 (0.00);4;1;1;F;0;1;70,5;;
62;;ROMERO, LUCAS EZEQUIEL;0.00 (0.00);4;1;1;F;1;1;100;;
63;;SILVA, LEONARDO DE;;4;1;1;F;1;1;97,5;;
64;;SILVERO, SERGIO RODOLFO;0.00 (0.00);2;0;0;F;0;0;;;
65;;SOSA, CESAR JAVIER;;3;1;1;F;1;1;47;;
66;1466;TORRES, MILAGROS;0.00 (0.00);4;1;1;F;1;1;65,5;;
67;;VARGAS, PABLO ANDRES;;4;1;1;F;0;1;85;;
68;;VERA, FERNANDO MARTIN;0.00 (0.00);4;1;1;F;1;1;80;;
69;;VILLAGRA, NADIA MARLENE;;3;1;0;F;1;1;63;;
70;;VILLANUEVA, ADRIAN;;4;1;1;F;1;1;97,5;;
;;;;;;;;;;;;
Datos;;;;;07/09;14/09;21/09;28/09;05/10;;;
Tema:;;;;;;;;;;;;
Firma docente:;;;;;;;;;;;;`;

    document.getElementById('rawContentTextarea').value = sample;
}
</script>
@endsection
