import './layout/hamburger.js';
// Loaded before tab-content.js: it restores the employee's rows-per-page and
// grid/list preference, and the pagination that follows must see the result.
import './display-preference.js';
// v=2 busts cached copies of main.js that still run the old unscoped
// active-link selector (it restyled the sidebar profile block as a menu item).
import './utils/main.js?v=2';
import './layout/tab-content.js';
import './layout/realtime.js';
import './components/dropdown.js';
import './components/learner-modals.js';
