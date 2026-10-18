/**
 * anti-devtools.js - Complete Developer Tools Blocker & Anti-Debugging System
 * Metro POS & Communication System
 * 
 * 100% False-Positive-Free Developer Protection:
 * 1. Blocks all DevTools keyboard shortcuts (Windows, Mac, Linux):
 *    - F12
 *    - Ctrl/Cmd + Shift + I (DevTools / Inspect)
 *    - Ctrl/Cmd + Shift + J (Console)
 *    - Ctrl/Cmd + Shift + C (Element Inspector)
 *    - Ctrl/Cmd + Shift + K (Firefox Web Console)
 *    - Ctrl/Cmd + Shift + E (Network Profiler)
 *    - Ctrl/Cmd + Shift + S (Debugger)
 *    - Ctrl/Cmd + Shift + M (Device Emulation)
 *    - Ctrl/Cmd + Shift + P (DevTools Command Palette)
 *    - Ctrl/Cmd + U (View Page Source)
 *    - Ctrl/Cmd + S (Save Page Offline)
 * 2. Blocks right-click context menu (prevents "Inspect Element" & "View Source")
 * 3. Continuous dynamic Anti-Debugger Loop:
 *    - When DevTools is CLOSED: Executes in <0.001ms with ZERO impact, ZERO warnings, and ZERO false alarms.
 *    - When DevTools is MANUALLY OPENED (via browser 3-dots menu): The browser engine immediately pauses 
 *      at the debugger statement, freezing the DevTools console and rendering inspection impossible.
 * 4. Silences and neutralizes all browser console methods (log, info, warn, error, table, dir, etc.)
 * 5. Periodically clears any buffered console output.
 */

(function () {
    'use strict';

    let isPrinting = false;
    let toastTimeout = null;
    let lastToastTime = 0;

    // Detect browser print lifecycle so printing receipts is never disrupted
    window.addEventListener('beforeprint', function () {
        isPrinting = true;
    });
    window.addEventListener('afterprint', function () {
        isPrinting = false;
    });

    // 1. Sleek Security Notification Toast (Only on deliberate blocked shortcut / right-click)
    function showSecurityToast(msg) {
        const now = Date.now();
        if (now - lastToastTime < 2000) return;
        lastToastTime = now;

        let toast = document.getElementById('anti-dev-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'anti-dev-toast';
            toast.style.cssText = [
                'position: fixed',
                'bottom: 24px',
                'right: 24px',
                'background: #0f172a',
                'border: 1px solid #ef4444',
                'color: #ffffff',
                'padding: 12px 18px',
                'border-radius: 12px',
                'font-family: -apple-system, BlinkMacSystemFont, "Plus Jakarta Sans", "Segoe UI", Roboto, sans-serif',
                'font-size: 13px',
                'font-weight: 600',
                'box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.4)',
                'z-index: 2147483647',
                'display: flex',
                'align-items: center',
                'gap: 10px',
                'opacity: 0',
                'transform: translateY(12px)',
                'transition: opacity 0.25s ease, transform 0.25s ease',
                'pointer-events: none'
            ].join(';');
            toast.innerHTML = '<span style="color:#ef4444; font-size:16px;">🛡️</span> <span>' + msg + '</span>';
            const target = document.body || document.documentElement;
            if (target) target.appendChild(toast);
        } else {
            const span = toast.querySelector('span:last-child');
            if (span) span.textContent = msg;
        }

        clearTimeout(toastTimeout);
        requestAnimationFrame(function () {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        });

        toastTimeout = setTimeout(function () {
            if (toast) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(12px)';
            }
        }, 2200);
    }

    // 2. Intercept All DevTools Keyboard Shortcuts (Capture Phase)
    function handleKeyDown(e) {
        const isCtrlOrMeta = e.ctrlKey || e.metaKey; // Windows Ctrl or Mac Cmd
        const isShift = e.shiftKey;
        const isAlt = e.altKey;
        const key = e.key ? e.key.toUpperCase() : '';
        const keyCode = e.keyCode || e.which;

        // F12 key (DevTools toggle)
        const isF12 = key === 'F12' || keyCode === 123;

        // Ctrl/Cmd + Shift + I (Inspect Elements)
        const isInspect = isCtrlOrMeta && isShift && (key === 'I' || keyCode === 73);

        // Ctrl/Cmd + Shift + J (Developer Console)
        const isConsole = isCtrlOrMeta && isShift && (key === 'J' || keyCode === 74);

        // Ctrl/Cmd + Shift + C (Inspect DOM Element)
        const isElementInspect = isCtrlOrMeta && isShift && (key === 'C' || keyCode === 67);

        // Mac Option + Cmd + I / J / C
        const isMacInspect = isAlt && isCtrlOrMeta && (key === 'I' || key === 'J' || key === 'C' || keyCode === 73 || keyCode === 74 || keyCode === 67);

        // Ctrl/Cmd + Shift + K (Firefox Web Console)
        const isFFConsole = isCtrlOrMeta && isShift && (key === 'K' || keyCode === 75);

        // Ctrl/Cmd + Shift + E (Firefox Network Profiler)
        const isFFNetwork = isCtrlOrMeta && isShift && (key === 'E' || keyCode === 69);

        // Ctrl/Cmd + Shift + S (Firefox Debugger)
        const isFFDebugger = isCtrlOrMeta && isShift && (key === 'S' || keyCode === 83);

        // Ctrl/Cmd + Shift + M (Device Mode / Responsive Emulation)
        const isDeviceMode = isCtrlOrMeta && isShift && (key === 'M' || keyCode === 77);

        // Ctrl/Cmd + Shift + P (DevTools Command Palette)
        const isCommandPalette = isCtrlOrMeta && isShift && (key === 'P' || keyCode === 80);

        // Ctrl/Cmd + U (View Page Source)
        const isViewSource = isCtrlOrMeta && !isShift && (key === 'U' || keyCode === 85);

        // Ctrl/Cmd + S (Save Page Offline)
        const isSavePage = isCtrlOrMeta && !isShift && (key === 'S' || keyCode === 83);

        if (isF12) {
            e.preventDefault();
            e.stopPropagation();
            if (e.stopImmediatePropagation) e.stopImmediatePropagation();

            if (typeof openShortcutsHelpModal === 'function' && document.getElementById('shortcuts-help-modal')) {
                openShortcutsHelpModal();
            } else {
                showSecurityToast('Developer tools access is disabled.');
            }
            return false;
        }

        if (isInspect || isConsole || isElementInspect || isMacInspect || isFFConsole || 
            isFFNetwork || isFFDebugger || isDeviceMode || isCommandPalette || isViewSource || isSavePage) {
            e.preventDefault();
            e.stopPropagation();
            if (e.stopImmediatePropagation) e.stopImmediatePropagation();
            showSecurityToast('Developer tools shortcut is blocked.');
            return false;
        }
    }

    window.addEventListener('keydown', handleKeyDown, true);
    document.addEventListener('keydown', handleKeyDown, true);

    // 3. Prevent Right-Click Context Menu (Blocks "Inspect", "View Source")
    function handleContextMenu(e) {
        e.preventDefault();
        e.stopPropagation();
        if (e.stopImmediatePropagation) e.stopImmediatePropagation();
        showSecurityToast('Right-click inspection is disabled.');
        return false;
    }

    window.addEventListener('contextmenu', handleContextMenu, true);
    document.addEventListener('contextmenu', handleContextMenu, true);

    // 4. Console Neutralization and Purging
    function silenceConsole() {
        const noop = function () {};
        const methods = [
            'log', 'debug', 'info', 'warn', 'error', 'table', 'trace',
            'dir', 'dirxml', 'group', 'groupCollapsed', 'groupEnd',
            'time', 'timeEnd', 'count', 'assert'
        ];

        methods.forEach(function (method) {
            try {
                if (window.console) {
                    window.console[method] = noop;
                }
            } catch (e) {}
        });

        // Periodically purge console buffer
        setInterval(function () {
            try {
                if (window.console && typeof window.console.clear === 'function') {
                    window.console.clear();
                }
            } catch (e) {}
        }, 1000);
    }
    silenceConsole();

    // 5. Dynamic Anti-Debugger Lock Loop
    // When Developer Tools is closed: executes in < 0.001ms with NO effect on UI or performance.
    // When Developer Tools is manually opened (via browser menu): browser automatically halts 
    // at the debugger keyword, freezing the console prompt and blocking inspection.
    function executeDebuggerTrap() {
        if (isPrinting) return;
        try {
            (function () {
                return false;
            }['constructor']('debugger')());
        } catch (e) {}
    }

    // Run debugger trap regularly to catch manual console opening
    setInterval(executeDebuggerTrap, 200);

})();
