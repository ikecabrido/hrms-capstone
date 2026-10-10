import './config.js';
import './helpers.js';
import './intake.js';
import './case-list.js';
import './case-detail.js';
import './roadmap.js';
import './documents.js';
import './references.js';
import './notes.js';
import { init } from './modals.js';

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
