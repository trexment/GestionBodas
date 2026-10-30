/**
 * Selector Global de Fecha (dd/mm/yyyy, Lunes primer día) y Hora (24h sin AM/PM) en Español
 * Compatible al 100% con Livewire 3 (mantiene el texto del input siempre visible y sincronizado).
 */
(function () {
    'use strict';

    const MONTHS_ES = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    const WEEKDAYS_ES = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'];

    // 1. Estilos globales: mantener el texto de fecha/hora 100% visible siempre,
    // ocultar únicamente el campo AM/PM en inputs de hora y el icono nativo del navegador.
    const style = document.createElement('style');
    style.id = 'es-datetime-picker-styles';
    style.textContent = `
        input[type="date"],
        input[type="time"] {
            position: relative;
            cursor: pointer;
            padding-right: 32px !important;
            background-repeat: no-repeat !important;
            background-position: right 10px center !important;
            background-size: 15px 15px !important;
            background-image: url("data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'/%3E%3Cline x1='16' y1='2' x2='16' y2='6'/%3E%3Cline x1='8' y1='2' x2='8' y2='6'/%3E%3Cline x1='3' y1='10' x2='21' y2='10'/%3E%3C/svg%3E") !important;
        }
        input[type="time"] {
            background-image: url("data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cpolyline points='12 6 12 12 16 14'/%3E%3C/svg%3E") !important;
        }
        /* Asegurar que el texto interno de fecha y hora sea SIEMPRE visible en modo claro y oscuro */
        input[type="date"]::-webkit-datetime-edit,
        input[type="date"]::-webkit-datetime-edit-fields-wrapper,
        input[type="date"]::-webkit-datetime-edit-text,
        input[type="date"]::-webkit-datetime-edit-month-field,
        input[type="date"]::-webkit-datetime-edit-day-field,
        input[type="date"]::-webkit-datetime-edit-year-field,
        input[type="time"]::-webkit-datetime-edit,
        input[type="time"]::-webkit-datetime-edit-fields-wrapper,
        input[type="time"]::-webkit-datetime-edit-text,
        input[type="time"]::-webkit-datetime-edit-hour-field,
        input[type="time"]::-webkit-datetime-edit-minute-field {
            color: inherit !important;
            opacity: 1 !important;
            -webkit-text-fill-color: currentColor !important;
        }
        /* Ocultar únicamente el selector AM/PM en inputs de hora para forzar 24h */
        input[type="time"]::-webkit-datetime-edit-ampm-field {
            display: none !important;
            width: 0 !important;
            opacity: 0 !important;
        }
        /* Ocultar el icono nativo de calendario/reloj del navegador porque usamos nuestro popover en español */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            opacity: 0 !important;
            cursor: pointer !important;
            position: absolute !important;
            right: 0 !important;
            top: 0 !important;
            bottom: 0 !important;
            width: 34px !important;
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        #es-dt-popover {
            position: fixed;
            z-index: 999999;
            width: 296px;
            border-radius: 14px;
            box-shadow: 0 20px 35px -5px rgba(0, 0, 0, 0.45), 0 8px 15px -6px rgba(0, 0, 0, 0.25);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            user-select: none;
        }
        #es-dt-popover.es-dt-light {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #0f172a;
        }
        #es-dt-popover.es-dt-dark {
            background: #0f172a;
            border: 1px solid #475569;
            color: #f8fafc;
        }
        .es-dt-day-btn {
            height: 34px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.1s ease;
        }
        .es-dt-light .es-dt-day-btn:hover:not(:disabled) {
            background: #e0e7ff;
            color: #3730a3;
        }
        .es-dt-dark .es-dt-day-btn:hover:not(:disabled) {
            background: #1e293b;
            color: #818cf8;
        }
        .es-dt-day-selected {
            background: #4f46e5 !important;
            color: #ffffff !important;
            font-weight: 800 !important;
        }
        .es-dt-day-today {
            border: 1.5px solid #6366f1;
        }
        .es-dt-scroll-col {
            max-height: 190px;
            overflow-y: auto;
            scrollbar-width: thin;
        }
    `;
    document.head.appendChild(style);

    function formatIsoToEsDate(iso) {
        if (!iso || typeof iso !== 'string') return '';
        const m = iso.trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!m) return '';
        return `${m[3]}/${m[2]}/${m[1]}`;
    }

    function parseEsDateToIso(str) {
        if (!str || typeof str !== 'string') return null;
        const clean = str.trim();
        const mEs = clean.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
        if (mEs) {
            const d = parseInt(mEs[1], 10);
            const mo = parseInt(mEs[2], 10);
            const y = parseInt(mEs[3], 10);
            if (mo >= 1 && mo <= 12 && d >= 1 && d <= 31 && y >= 1970 && y <= 2100) {
                return `${y}-${String(mo).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            }
        }
        const mIso = clean.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (mIso) {
            return clean;
        }
        return null;
    }

    function normalizeTime24(val) {
        if (!val || typeof val !== 'string') return '';
        const m = val.trim().match(/^(\d{1,2}):(\d{2})/);
        if (!m) return '';
        return `${String(m[1]).padStart(2, '0')}:${m[2]}`;
    }

    function enhanceAllInputs(root = document) {
        const inputs = root.querySelectorAll
            ? root.querySelectorAll('input[type="date"], input[type="time"]')
            : [];
        inputs.forEach(input => {
            if (!input._esBound) {
                input._esBound = true;
                if (!input.getAttribute('lang')) {
                    input.setAttribute('lang', 'es-ES');
                }
                if (input.getAttribute('type') === 'time' && !input.getAttribute('step')) {
                    input.setAttribute('step', '60');
                }
                // Interceptar showPicker nativo para abrir nuestro selector español con Lunes primero y 24h
                try {
                    input.showPicker = function () {
                        openPickerForInput(input);
                    };
                } catch (e) {}
            }
        });
    }

    function setInputValueAndNotifyLivewire(input, newValue) {
        if (!input) return;
        const nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value')?.set;
        if (nativeInputValueSetter) {
            nativeInputValueSetter.call(input, newValue);
        } else {
            input.value = newValue;
        }
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Popover flotante único
    let activeInput = null;
    let popoverEl = null;
    let viewYear = new Date().getFullYear();
    let viewMonth = new Date().getMonth();

    function ensurePopover() {
        if (popoverEl && popoverEl.isConnected) return popoverEl;
        popoverEl = document.createElement('div');
        popoverEl.id = 'es-dt-popover';
        popoverEl.style.display = 'none';
        document.body.appendChild(popoverEl);
        return popoverEl;
    }

    function closePopover() {
        if (popoverEl) {
            popoverEl.style.display = 'none';
        }
        activeInput = null;
    }

    function positionPopover(input) {
        const pop = ensurePopover();
        const rect = input.getBoundingClientRect();
        const popWidth = 296;
        const popHeight = 340;

        let left = rect.left;
        if (left + popWidth > window.innerWidth - 12) {
            left = Math.max(12, window.innerWidth - popWidth - 12);
        }
        let top = rect.bottom + 6;
        if (top + popHeight > window.innerHeight - 12 && rect.top > popHeight + 12) {
            top = rect.top - popHeight - 6;
        }

        pop.style.left = `${Math.round(left)}px`;
        pop.style.top = `${Math.round(top)}px`;
    }

    function renderDatePopover(input) {
        const pop = ensurePopover();
        const isDark = document.documentElement.classList.contains('dark');
        pop.className = isDark ? 'es-dt-dark' : 'es-dt-light';

        const currentVal = (input.value || '').trim();
        const minVal = (input.getAttribute('min') || '').trim();
        const maxVal = (input.getAttribute('max') || '').trim();

        const today = new Date();
        const todayIso = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

        // Primer día del mes (convertido a Lunes = 0 ... Domingo = 6)
        const firstDayDate = new Date(viewYear, viewMonth, 1);
        const startOffset = (firstDayDate.getDay() + 6) % 7;
        const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();

        const borderCol = isDark ? '#334155' : '#e2e8f0';
        const subtleBg = isDark ? '#1e293b' : '#f8fafc';
        const mutedText = isDark ? '#94a3b8' : '#64748b';

        let daysHtml = '';
        for (let i = 0; i < startOffset; i++) {
            daysHtml += `<div></div>`;
        }
        for (let d = 1; d <= daysInMonth; d++) {
            const iso = `${viewYear}-${String(viewMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const isSelected = iso === currentVal;
            const isToday = iso === todayIso;
            const isDisabled = (minVal && iso < minVal) || (maxVal && iso > maxVal);

            daysHtml += `
                <button type="button"
                    data-es-pick-date="${iso}"
                    ${isDisabled ? 'disabled style="opacity:0.3;cursor:not-allowed;"' : ''}
                    class="es-dt-day-btn ${isSelected ? 'es-dt-day-selected' : ''} ${isToday && !isSelected ? 'es-dt-day-today' : ''}">
                    ${d}
                </button>
            `;
        }

        // Opciones de año amplias (desde hace 10 años hasta +6 años para cubrir fotos de bodas pasadas y eventos futuros)
        let yearOptions = '';
        const baseYear = new Date().getFullYear();
        const minYear = Math.min(baseYear - 10, viewYear - 2);
        const maxYear = Math.max(baseYear + 6, viewYear + 2);
        for (let y = minYear; y <= maxYear; y++) {
            yearOptions += `<option value="${y}" ${y === viewYear ? 'selected' : ''}>${y}</option>`;
        }

        pop.innerHTML = `
            <div style="padding: 12px 14px;">
                <!-- Cabecera Mes / Año -->
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;gap:6px;">
                    <button type="button" data-es-nav="-1" style="padding:5px 9px;border-radius:8px;background:${subtleBg};border:1px solid ${borderCol};font-weight:800;cursor:pointer;">‹</button>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <select data-es-month style="background:${subtleBg};border:1px solid ${borderCol};border-radius:8px;padding:4px 8px;font-size:12px;font-weight:700;cursor:pointer;color:inherit;">
                            ${MONTHS_ES.map((m, idx) => `<option value="${idx}" ${idx === viewMonth ? 'selected' : ''}>${m}</option>`).join('')}
                        </select>
                        <select data-es-year style="background:${subtleBg};border:1px solid ${borderCol};border-radius:8px;padding:4px 6px;font-size:12px;font-weight:700;cursor:pointer;color:inherit;">
                            ${yearOptions}
                        </select>
                    </div>
                    <button type="button" data-es-nav="1" style="padding:5px 9px;border-radius:8px;background:${subtleBg};border:1px solid ${borderCol};font-weight:800;cursor:pointer;">›</button>
                </div>

                <!-- Cabecera Lunes a Domingo -->
                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center;margin-bottom:4px;">
                    ${WEEKDAYS_ES.map(w => `<div style="font-size:10px;font-weight:800;text-transform:uppercase;color:${mutedText};padding:3px 0;">${w}</div>`).join('')}
                </div>

                <!-- Cuadrícula de días -->
                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;">
                    ${daysHtml}
                </div>

                <!-- Entrada manual dd/mm/yyyy + Botones Hoy / Borrar -->
                <div style="margin-top:10px;padding-top:10px;border-top:1px solid ${borderCol};display:flex;align-items:center;justify-content:space-between;gap:6px;">
                    <input type="text" data-es-manual-date placeholder="dd/mm/aaaa" value="${formatIsoToEsDate(currentVal)}"
                        style="width:105px;padding:5px 8px;border-radius:8px;border:1px solid ${borderCol};background:${subtleBg};color:inherit;font-size:12px;font-weight:700;text-align:center;">
                    <div style="display:flex;gap:5px;">
                        <button type="button" data-es-clear style="padding:5px 8px;border-radius:8px;font-size:11px;font-weight:700;color:${mutedText};cursor:pointer;">Borrar</button>
                        <button type="button" data-es-today style="padding:5px 9px;border-radius:8px;font-size:11px;font-weight:800;background:#4f46e5;color:#fff;cursor:pointer;">Hoy</button>
                    </div>
                </div>
            </div>
        `;

        // Eventos del popover de fecha
        pop.querySelectorAll('[data-es-nav]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const dir = parseInt(btn.getAttribute('data-es-nav'), 10);
                viewMonth += dir;
                if (viewMonth < 0) { viewMonth = 11; viewYear--; }
                if (viewMonth > 11) { viewMonth = 0; viewYear++; }
                renderDatePopover(input);
            });
        });

        const monthSel = pop.querySelector('[data-es-month]');
        if (monthSel) {
            monthSel.addEventListener('change', (e) => {
                e.stopPropagation();
                viewMonth = parseInt(e.target.value, 10);
                renderDatePopover(input);
            });
        }

        const yearSel = pop.querySelector('[data-es-year]');
        if (yearSel) {
            yearSel.addEventListener('change', (e) => {
                e.stopPropagation();
                viewYear = parseInt(e.target.value, 10);
                renderDatePopover(input);
            });
        }

        pop.querySelectorAll('[data-es-pick-date]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const iso = btn.getAttribute('data-es-pick-date');
                setInputValueAndNotifyLivewire(input, iso);
                closePopover();
            });
        });

        const manualInput = pop.querySelector('[data-es-manual-date]');
        if (manualInput) {
            const applyManual = () => {
                const iso = parseEsDateToIso(manualInput.value);
                if (iso) {
                    setInputValueAndNotifyLivewire(input, iso);
                    return true;
                }
                return false;
            };
            manualInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    if (applyManual()) {
                        closePopover();
                    }
                }
            });
            manualInput.addEventListener('change', () => {
                applyManual();
            });
        }

        const clearBtn = pop.querySelector('[data-es-clear]');
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                setInputValueAndNotifyLivewire(input, '');
                closePopover();
            });
        }

        const todayBtn = pop.querySelector('[data-es-today]');
        if (todayBtn) {
            todayBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                setInputValueAndNotifyLivewire(input, todayIso);
                closePopover();
            });
        }
    }

    function renderTimePopover(input) {
        const pop = ensurePopover();
        const isDark = document.documentElement.classList.contains('dark');
        pop.className = isDark ? 'es-dt-dark' : 'es-dt-light';

        const currentVal = normalizeTime24(input.value || '') || '18:00';
        const parts = currentVal.split(':');
        let selHour = parts[0] || '18';
        let selMin = parts[1] || '00';

        const borderCol = isDark ? '#334155' : '#e2e8f0';
        const subtleBg = isDark ? '#1e293b' : '#f8fafc';
        const mutedText = isDark ? '#94a3b8' : '#64748b';

        let hoursHtml = '';
        for (let h = 0; h < 24; h++) {
            const hh = String(h).padStart(2, '0');
            const active = hh === selHour;
            hoursHtml += `
                <button type="button" data-es-hour="${hh}"
                    class="es-dt-day-btn ${active ? 'es-dt-day-selected' : ''}"
                    style="width:100%;margin-bottom:2px;height:30px;">
                    ${hh} h
                </button>
            `;
        }

        const minuteSteps = ['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'];
        let minsHtml = '';
        minuteSteps.forEach(mm => {
            const active = mm === selMin;
            minsHtml += `
                <button type="button" data-es-min="${mm}"
                    class="es-dt-day-btn ${active ? 'es-dt-day-selected' : ''}"
                    style="width:100%;margin-bottom:2px;height:30px;">
                    :${mm}
                </button>
            `;
        });

        const presets = ['12:00', '13:00', '17:00', '18:00', '19:00', '20:00', '21:00', '00:00'];

        pop.innerHTML = `
            <div style="padding: 12px 14px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <span style="font-size:11px;font-weight:800;text-transform:uppercase;color:${mutedText};letter-spacing:0.5px;">⏰ Reloj 24 Horas</span>
                    <input type="text" data-es-manual-time value="${selHour}:${selMin}" placeholder="18:00"
                        style="width:74px;padding:3px 6px;border-radius:7px;border:1px solid ${borderCol};background:${subtleBg};color:inherit;font-size:13px;font-weight:800;text-align:center;">
                </div>

                <!-- Horas rápidas -->
                <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px;">
                    ${presets.map(p => `
                        <button type="button" data-es-preset-time="${p}"
                            style="padding:3px 7px;border-radius:6px;font-size:11px;font-weight:700;border:1px solid ${borderCol};background:${p === (selHour + ':' + selMin) ? '#4f46e5' : subtleBg};color:${p === (selHour + ':' + selMin) ? '#fff' : 'inherit'};cursor:pointer;">
                            ${p}
                        </button>
                    `).join('')}
                </div>

                <!-- Columnas Hora (00-23) y Minutos (00-55) -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;border-top:1px solid ${borderCol};padding-top:8px;">
                    <div>
                        <div style="font-size:10px;font-weight:800;text-transform:uppercase;color:${mutedText};text-align:center;margin-bottom:4px;">Hora (00–23)</div>
                        <div class="es-dt-scroll-col" data-es-hour-col>${hoursHtml}</div>
                    </div>
                    <div>
                        <div style="font-size:10px;font-weight:800;text-transform:uppercase;color:${mutedText};text-align:center;margin-bottom:4px;">Minutos</div>
                        <div class="es-dt-scroll-col" data-es-min-col>${minsHtml}</div>
                    </div>
                </div>

                <div style="margin-top:10px;padding-top:8px;border-top:1px solid ${borderCol};display:flex;justify-content:space-between;align-items:center;">
                    <button type="button" data-es-clear-time style="padding:4px 8px;border-radius:8px;font-size:11px;font-weight:700;color:${mutedText};cursor:pointer;">Borrar</button>
                    <button type="button" data-es-done-time style="padding:5px 14px;border-radius:8px;font-size:11px;font-weight:800;background:#4f46e5;color:#fff;cursor:pointer;">Aceptar</button>
                </div>
            </div>
        `;

        setTimeout(() => {
            const selectedHourBtn = pop.querySelector('[data-es-hour-col] .es-dt-day-selected');
            if (selectedHourBtn) selectedHourBtn.scrollIntoView({ block: 'center' });
        }, 10);

        pop.querySelectorAll('[data-es-preset-time]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const val = btn.getAttribute('data-es-preset-time');
                setInputValueAndNotifyLivewire(input, val);
                closePopover();
            });
        });

        pop.querySelectorAll('[data-es-hour]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                selHour = btn.getAttribute('data-es-hour');
                setInputValueAndNotifyLivewire(input, `${selHour}:${selMin}`);
                renderTimePopover(input);
            });
        });

        pop.querySelectorAll('[data-es-min]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                selMin = btn.getAttribute('data-es-min');
                setInputValueAndNotifyLivewire(input, `${selHour}:${selMin}`);
                closePopover();
            });
        });

        const manualTime = pop.querySelector('[data-es-manual-time]');
        if (manualTime) {
            manualTime.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    const m = manualTime.value.trim().match(/^([01]?\d|2[0-3]):([0-5]\d)$/);
                    if (m) {
                        setInputValueAndNotifyLivewire(input, `${String(m[1]).padStart(2, '0')}:${m[2]}`);
                        closePopover();
                    }
                }
            });
        }

        const clearBtn = pop.querySelector('[data-es-clear-time]');
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                setInputValueAndNotifyLivewire(input, '');
                closePopover();
            });
        }

        const doneBtn = pop.querySelector('[data-es-done-time]');
        if (doneBtn) {
            doneBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const m = manualTime ? manualTime.value.trim().match(/^([01]?\d|2[0-3]):([0-5]\d)$/) : null;
                if (m) {
                    setInputValueAndNotifyLivewire(input, `${String(m[1]).padStart(2, '0')}:${m[2]}`);
                } else {
                    setInputValueAndNotifyLivewire(input, `${selHour}:${selMin}`);
                }
                closePopover();
            });
        }
    }

    function openPickerForInput(input) {
        if (!input || input.disabled || input.readOnly) return;
        const type = input.getAttribute('type');
        if (type !== 'date' && type !== 'time') return;

        activeInput = input;
        const pop = ensurePopover();

        if (type === 'date') {
            const m = (input.value || '').trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (m) {
                viewYear = parseInt(m[1], 10);
                viewMonth = parseInt(m[2], 10) - 1;
            } else {
                const now = new Date();
                viewYear = now.getFullYear();
                viewMonth = now.getMonth();
            }
            renderDatePopover(input);
        } else {
            renderTimePopover(input);
        }

        pop.style.display = 'block';
        positionPopover(input);
    }

    // Abrir nuestro calendario español (Lunes primero) o reloj 24h al hacer clic, permitiendo también escribir con el teclado
    document.addEventListener('click', (e) => {
        const target = e.target;
        if (target && target.tagName === 'INPUT') {
            const type = target.getAttribute('type');
            if (type === 'date' || type === 'time') {
                e.preventDefault();
                openPickerForInput(target);
                return;
            }
        }
        if (popoverEl && popoverEl.style.display === 'block' && !popoverEl.contains(target)) {
            closePopover();
        }
    }, true);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && popoverEl && popoverEl.style.display === 'block') {
            closePopover();
        }
    }, true);

    window.addEventListener('scroll', () => {
        if (activeInput && popoverEl && popoverEl.style.display === 'block') {
            positionPopover(activeInput);
        }
    }, true);

    window.addEventListener('resize', () => {
        if (activeInput && popoverEl && popoverEl.style.display === 'block') {
            positionPopover(activeInput);
        }
    });

    let enhanceScheduled = false;
    function scheduleEnhance() {
        if (enhanceScheduled) return;
        enhanceScheduled = true;
        requestAnimationFrame(() => {
            enhanceScheduled = false;
            enhanceAllInputs();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => scheduleEnhance());
    } else {
        scheduleEnhance();
    }

    const observer = new MutationObserver((mutations) => {
        for (let i = 0; i < mutations.length; i++) {
            if (mutations[i].addedNodes && mutations[i].addedNodes.length > 0) {
                scheduleEnhance();
                break;
            }
        }
    });
    if (document.body) {
        observer.observe(document.body, { childList: true, subtree: true });
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            if (document.body) observer.observe(document.body, { childList: true, subtree: true });
        });
    }
})();
