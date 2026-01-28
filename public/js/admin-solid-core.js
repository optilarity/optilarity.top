import { createSignal, createEffect, For, Show } from 'https://esm.sh/solid-js';
import { render } from 'https://esm.sh/solid-js/web';
import html from 'https://esm.sh/solid-js/html';

// --- COMPONENTS ---

const BulkActionUI = (props) => {
    const [isOpen, setIsOpen] = createSignal(false);
    const [selected, setSelected] = createSignal(null);
    const config = props.config;

    const toggle = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setIsOpen(!isOpen());
    };

    const select = (action) => {
        setSelected(action);
        setIsOpen(false);
    };

    const apply = () => {
        const action = selected();
        if (!action) return;

        const container = document.getElementById(props.mountId);
        const form = container.closest('form') || document.querySelector('#posts-filter');

        if (form) {
            let input = form.querySelector(`input[name="${config.name}"]`);
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = config.name;
                form.appendChild(input);
            }
            input.value = action.value;
            form.submit();
        } else {
            console.error("SolidAdmin: Form not found for bulk action");
        }
    };

    // Close on click outside
    document.addEventListener('click', (e) => {
        if (isOpen()) setIsOpen(false);
    });

    return html`
        <div class="solid-bulk-wrapper" onClick=${(e) => e.stopPropagation()}>
            <div class="solid-dropdown">
                <button type="button" class="button action-toggle ${() => isOpen() ? 'open' : ''}" onClick=${toggle}>
                    <span>${() => selected() ? selected().label : 'Bulk Actions'}</span>
                    <span class="dashicons dashicons-arrow-down-alt2 icon ${() => isOpen() ? 'rotate' : ''}"></span>
                </button>
                
                <div class="action-menu ${() => isOpen() ? 'visible' : ''}">
                    <ul>
                        <${For} each=${config.actions}>
                            ${(item) => html`
                                <li 
                                    onClick=${() => select(item)}
                                    class="action-item ${() => selected() && selected().value === item.value ? 'selected' : ''}"
                                >
                                    ${item.label}
                                </li>
                            `}
                        <//>
                    </ul>
                </div>
            </div>
            
            <button type="button" class="button button-primary apply-btn" onClick=${apply} disabled=${() => !selected()}>Apply</button>
        </div>
        
        <style>
            .solid-bulk-wrapper {
                display: inline-flex;
                align-items: center;
                gap: 12px;
                position: relative;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }
            .solid-dropdown {
                position: relative;
            }
            .action-toggle {
                min-width: 160px;
                text-align: left;
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 6px 12px !important;
                background: #fff;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                cursor: pointer;
                transition: all 0.2s ease;
                height: 32px;
                color: #2c3338;
            }
            .action-toggle:hover, .action-toggle.open {
                border-color: #2271b1;
                color: #2271b1;
            }
            .action-toggle .icon {
                font-size: 16px;
                transition: transform 0.2s ease;
            }
            .action-toggle .icon.rotate {
                transform: rotate(180deg);
            }
            
            .action-menu {
                display: block;
                position: absolute;
                top: calc(100% + 5px);
                left: 0;
                background: #fff;
                border: 0;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                z-index: 999;
                min-width: 200px;
                border-radius: 6px;
                padding: 4px;
                opacity: 0;
                transform: translateY(-10px);
                pointer-events: none;
                transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .action-menu.visible {
                opacity: 1;
                transform: translateY(0);
                pointer-events: auto;
            }
            .action-item {
                padding: 8px 12px;
                cursor: pointer;
                border-radius: 4px;
                color: #3c434a;
                font-size: 13px;
                transition: background 0.1s;
            }
            .action-item:hover {
                background-color: #f0f0f1;
                color: #2271b1;
            }
            .action-item.selected {
                background-color: #f0f7fc;
                color: #2271b1;
                font-weight: 500;
            }
            .apply-btn { transition: all 0.2s; }
            .apply-btn:disabled { opacity: 0.6; cursor: not-allowed; }
        </style>
    `;
};

// --- COMPONENT REGISTRY ---
const COMPONENTS = {
    'BulkActions': BulkActionUI
};

// --- MOUNTING LOGIC ---
document.querySelectorAll('[data-solid-component]').forEach((el, index) => {
    const componentName = el.dataset.solidComponent;
    const Component = COMPONENTS[componentName];

    if (Component) {
        let config = {};
        if (el.dataset.config) {
            try {
                config = JSON.parse(el.dataset.config);
            } catch (e) {
                console.error("Invalid config for", componentName, e);
            }
        }

        const id = 'solid-mount-' + componentName + '-' + index;
        el.id = id;

        render(() => html`<${Component} config=${config} mountId=${id} />`, el);
    }
});
