(() => {
    'use strict';

    const opened = new Set();
    const months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    const weekdays = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'];

    const closeAll = (except = null) => {
        opened.forEach((close) => {
            if (close !== except) close();
        });
    };

    const positionPopup = (trigger, popup, preferredWidth) => {
        const rect = trigger.getBoundingClientRect();
        const width = Math.min(preferredWidth || rect.width, window.innerWidth - 24);
        let left = Math.min(Math.max(12, rect.left), window.innerWidth - width - 12);
        let top = rect.bottom + 8;
        const estimatedHeight = Math.min(popup.scrollHeight || 330, window.innerHeight * 0.65);

        if (top + estimatedHeight > window.innerHeight - 12 && rect.top > estimatedHeight + 12) {
            top = rect.top - estimatedHeight - 8;
        }

        popup.style.left = `${left}px`;
        popup.style.top = `${Math.max(12, top)}px`;
        popup.style.width = `${width}px`;
    };

    const enhanceSelect = (select) => {
        if (select.dataset.enhanced === 'true') return;
        select.dataset.enhanced = 'true';

        const wrapper = document.createElement('div');
        wrapper.className = 'pretty-select';
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        select.classList.add('pretty-native-control');

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'pretty-control-trigger';
        trigger.disabled = select.disabled;
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.innerHTML = '<span class="pretty-control-value"></span><svg class="pretty-control-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>';
        wrapper.appendChild(trigger);

        const menu = document.createElement('div');
        menu.className = 'pretty-select-menu';
        menu.setAttribute('role', 'listbox');
        menu.hidden = true;
        document.body.appendChild(menu);

        const value = trigger.querySelector('.pretty-control-value');
        const refresh = () => {
            const selected = select.options[select.selectedIndex];
            value.textContent = selected ? selected.textContent.trim() : 'Seleccionar';
            trigger.disabled = select.disabled;
            menu.replaceChildren();

            Array.from(select.options).forEach((option) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'pretty-select-option';
                item.textContent = option.textContent.trim();
                item.disabled = option.disabled;
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', option.selected ? 'true' : 'false');
                item.addEventListener('click', () => {
                    select.value = option.value;
                    refresh();
                    close();
                    select.dispatchEvent(new Event('input', { bubbles: true }));
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    trigger.focus();
                });
                menu.appendChild(item);
            });
        };

        const close = () => {
            menu.hidden = true;
            wrapper.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
        };
        opened.add(close);

        trigger.addEventListener('click', () => {
            const shouldOpen = menu.hidden;
            closeAll(close);
            if (!shouldOpen) return close();
            refresh();
            menu.hidden = false;
            wrapper.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            positionPopup(trigger, menu, trigger.getBoundingClientRect().width);
        });

        select.addEventListener('change', refresh);
        select.addEventListener('ui:refresh', refresh);
        refresh();
    };

    const parseDate = (value) => {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return null;
        const [year, month, day] = value.split('-').map(Number);
        const date = new Date(year, month - 1, day);
        return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day ? date : null;
    };

    const isoDate = (date) => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
    const displayDate = (date) => date ? new Intl.DateTimeFormat('es-BO', { day: '2-digit', month: 'long', year: 'numeric' }).format(date) : 'Seleccionar fecha';

    const enhanceDate = (input) => {
        if (input.dataset.enhanced === 'true') return;
        input.dataset.enhanced = 'true';

        const wrapper = document.createElement('div');
        wrapper.className = 'pretty-date';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        input.classList.add('pretty-native-control');

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'pretty-control-trigger';
        trigger.setAttribute('aria-haspopup', 'dialog');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.innerHTML = '<span class="pretty-control-value"></span><svg class="pretty-control-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"></rect><line x1="16" y1="3" x2="16" y2="7"></line><line x1="8" y1="3" x2="8" y2="7"></line><line x1="3" y1="11" x2="21" y2="11"></line></svg>';
        wrapper.appendChild(trigger);

        const calendar = document.createElement('div');
        calendar.className = 'pretty-calendar';
        calendar.setAttribute('role', 'dialog');
        calendar.setAttribute('aria-label', 'Seleccionar fecha');
        calendar.hidden = true;
        calendar.innerHTML = `
            <div class="calendar-header">
                <button type="button" class="calendar-nav calendar-prev" aria-label="Mes anterior">‹</button>
                <div class="calendar-period">
                    <select class="calendar-month" aria-label="Mes"></select>
                    <select class="calendar-year" aria-label="Año"></select>
                </div>
                <button type="button" class="calendar-nav calendar-next" aria-label="Mes siguiente">›</button>
            </div>
            <div class="calendar-weekdays">${weekdays.map((day) => `<span>${day}</span>`).join('')}</div>
            <div class="calendar-days"></div>
            <div class="calendar-actions">
                <button type="button" class="calendar-action calendar-clear">Limpiar</button>
                <button type="button" class="calendar-action primary calendar-today">Hoy</button>
            </div>`;
        document.body.appendChild(calendar);

        const selectedAtStart = parseDate(input.value);
        let viewDate = selectedAtStart || new Date();
        viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1);

        const minDate = () => parseDate(input.min);
        const maxDate = () => parseDate(input.max);
        const allowed = (date) => (!minDate() || date >= minDate()) && (!maxDate() || date <= maxDate());
        const value = trigger.querySelector('.pretty-control-value');
        const monthSelect = calendar.querySelector('.calendar-month');
        const yearSelect = calendar.querySelector('.calendar-year');
        const previousButton = calendar.querySelector('.calendar-prev');
        const nextButton = calendar.querySelector('.calendar-next');

        months.forEach((month, index) => {
            const option = document.createElement('option');
            option.value = String(index);
            option.textContent = month.charAt(0).toUpperCase() + month.slice(1);
            monthSelect.appendChild(option);
        });

        const refreshYears = () => {
            const minimum = minDate();
            const maximum = maxDate();
            const currentYear = new Date().getFullYear();
            const firstYear = minimum ? minimum.getFullYear() : 1900;
            const lastYear = maximum ? maximum.getFullYear() : currentYear + 10;
            const previousValue = Number(yearSelect.value) || viewDate.getFullYear();
            yearSelect.replaceChildren();

            for (let year = lastYear; year >= firstYear; year -= 1) {
                const option = document.createElement('option');
                option.value = String(year);
                option.textContent = String(year);
                yearSelect.appendChild(option);
            }

            yearSelect.value = String(Math.min(lastYear, Math.max(firstYear, previousValue)));
        };

        const refreshValue = () => {
            value.textContent = displayDate(parseDate(input.value));
            trigger.disabled = input.disabled;
        };

        const choose = (date) => {
            if (!allowed(date)) return;
            input.value = isoDate(date);
            refreshValue();
            close();
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            trigger.focus();
        };

        const render = () => {
            refreshYears();
            monthSelect.value = String(viewDate.getMonth());
            yearSelect.value = String(viewDate.getFullYear());
            const minimum = minDate();
            const maximum = maxDate();
            const previousMonth = new Date(viewDate.getFullYear(), viewDate.getMonth() - 1, 1);
            const nextMonth = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 1);
            previousButton.disabled = Boolean(minimum && previousMonth < new Date(minimum.getFullYear(), minimum.getMonth(), 1));
            nextButton.disabled = Boolean(maximum && nextMonth > new Date(maximum.getFullYear(), maximum.getMonth(), 1));
            const days = calendar.querySelector('.calendar-days');
            days.replaceChildren();
            const firstWeekday = (new Date(viewDate.getFullYear(), viewDate.getMonth(), 1).getDay() + 6) % 7;
            const count = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 0).getDate();
            const selected = parseDate(input.value);
            const today = new Date();

            for (let index = 0; index < firstWeekday; index += 1) {
                const spacer = document.createElement('span');
                spacer.className = 'calendar-day is-outside';
                days.appendChild(spacer);
            }

            for (let day = 1; day <= count; day += 1) {
                const date = new Date(viewDate.getFullYear(), viewDate.getMonth(), day);
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'calendar-day';
                button.textContent = String(day);
                button.disabled = !allowed(date);
                if (isoDate(date) === isoDate(today)) button.classList.add('is-today');
                if (selected && isoDate(date) === isoDate(selected)) button.classList.add('is-selected');
                button.addEventListener('click', () => choose(date));
                days.appendChild(button);
            }
        };

        const close = () => {
            calendar.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
        };
        opened.add(close);

        trigger.addEventListener('click', () => {
            const shouldOpen = calendar.hidden;
            closeAll(close);
            if (!shouldOpen) return close();
            const selected = parseDate(input.value) || new Date();
            viewDate = new Date(selected.getFullYear(), selected.getMonth(), 1);
            render();
            calendar.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            positionPopup(trigger, calendar, 324);
        });

        previousButton.addEventListener('click', () => {
            viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() - 1, 1);
            render();
        });
        nextButton.addEventListener('click', () => {
            viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 1);
            render();
        });
        monthSelect.addEventListener('change', () => {
            viewDate = new Date(Number(yearSelect.value), Number(monthSelect.value), 1);
            render();
        });
        yearSelect.addEventListener('change', () => {
            viewDate = new Date(Number(yearSelect.value), Number(monthSelect.value), 1);
            render();
        });
        calendar.querySelector('.calendar-today').addEventListener('click', () => choose(new Date()));
        calendar.querySelector('.calendar-clear').addEventListener('click', () => {
            if (input.required) return close();
            input.value = '';
            refreshValue();
            close();
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        input.addEventListener('change', refreshValue);
        refreshValue();
    };

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.pretty-select, .pretty-select-menu, .pretty-date, .pretty-calendar')) closeAll();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeAll();
    });
    window.addEventListener('resize', () => closeAll());
    window.addEventListener('scroll', (event) => {
        if (event.target === document || event.target === document.documentElement || event.target === document.body) {
            closeAll();
        }
    }, true);

    document.querySelectorAll('select.form-select').forEach(enhanceSelect);
    document.querySelectorAll('input.form-input[type="date"]').forEach(enhanceDate);
})();
