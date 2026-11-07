<?php
// Iconos propios: una misma familia visual para navegación, indicadores y perfiles.
function icono(string $nombre, string $clase = ''): string
{
    $trazos = [
        'hospital' => '<path d="M8 21V5a2 2 0 012-2h4a2 2 0 012 2v16M3 21h18M4 21V10h4m8 0h4v11M10 8h4m-2-2v4m-2 11v-5h4v5"/><path d="M5 14h1m-1 3h1m12-3h1m-1 3h1"/>',
        'inicio' => '<path d="M3 10l9-7 9 7v10a1 1 0 01-1 1h-5v-7H9v7H4a1 1 0 01-1-1z"/><path class="icon-accent" d="M9 9h6"/>',
        'agenda' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4m10-4v4M3 10h18"/><path class="icon-accent" d="M7 14h3m-3 3h3m5-3h2m-2 3h2"/>',
        'pacientes' => '<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0112 0v3M16 4a3 3 0 010 6m2 4a5 5 0 013 4v3"/><path class="icon-accent" d="M6 18h6m-3-3v6"/>',
        'perfil' => '<path d="M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/><circle cx="10" cy="9" r="3"/><path d="M5 18a5 5 0 0110 0"/><path class="icon-accent" d="M16 8h2m-2 4h2m-2 4h2"/>',
        'medico' => '<path d="M4 3v5a5 5 0 0010 0V3M3 3h3m6 0h3M9 13v2a5 5 0 0010 0v-2"/><circle class="icon-accent" cx="19" cy="10" r="3"/>',
        'reloj' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l4 2"/><path class="icon-accent" d="M3 3l2 2m14 14l2 2"/>',
        'atendida' => '<path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9zM14 3v6h6"/><path class="icon-accent" d="M8 15l3 3 5-6"/>',
        'ausencia' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4m10-4v4M3 10h18"/><path class="icon-accent" d="M9 14l6 4m0-4l-6 4"/>',
        'campana' => '<path d="M5 10a7 7 0 0114 0v5l2 3H3l2-3zM10 21h4"/><circle class="icon-accent" cx="18" cy="5" r="3"/>',
        'flecha' => '<path d="M5 12h14m-6-6l6 6-6 6"/>',
        'volver' => '<path d="M19 12H5m6-6l-6 6 6 6"/>',
        'buscar' => '<circle cx="10" cy="10" r="6"/><path d="M15 15l6 6"/>',
        'escudo' => '<path d="M12 3l8 3v6c0 5-8 9-8 9s-8-4-8-9V6z"/><path class="icon-accent" d="M8 12l3 3 5-6"/>',
        'ubicacion' => '<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 0114 0z"/><circle cx="12" cy="10" r="2"/>',
        'editar' => '<path d="M14 5l5 5M4 20l5-1L21 7l-5-5L4 14z"/>',
        'historial' => '<path d="M3 11a9 9 0 119 10M3 4v7h7m2-4v5l4 2"/>',
    ];
    $contenido = $trazos[$nombre] ?? $trazos['hospital'];
    return '<svg class="app-icon ' . htmlspecialchars($clase, ENT_QUOTES, 'UTF-8') . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $contenido . '</svg>';
}
