/**
 * test.js — sample/sample submodule. Vue 3 Composition API.
 * Tiny smoke test for the toast wrappers (alert.Success / Error / Warning / Info).
 */

const { useApp } = window.utils;

useApp({
    setup() {
        const clickInfo = () => { alert.Info('Testa', 'Testing to know if this will work'); };
        const clickError = () => { alert.Error('Testa', 'Testing to know if this will work'); };
        const clickSuccess = () => { alert.Success('Testa', 'Testing to know if this will work'); };
        const clickWarning = () => { alert.Warning('Testa', 'Testing to know if this will work'); };
        return { clickInfo, clickError, clickSuccess, clickWarning };
    },
    template: `
        <div>
            <h1>Ipolongo is now Vue compatible</h1>
            <p>
                <button type="button" class="btn round btn-primary" @click="clickSuccess()">Success</button>
                <button type="button" class="btn round btn-danger" @click="clickError()">Error</button>
                <button type="button" class="btn round btn-warning" @click="clickWarning()">Warning</button>
                <button type="button" class="btn round btn-info" @click="clickInfo()">Info</button>
            </p>
        </div>
    `,
}).mount('#app');
