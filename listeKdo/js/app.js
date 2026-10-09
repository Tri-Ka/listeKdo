/* Liste de Kdo : point d’entrée des modules ES natifs, sans build. */

import { initDialogs } from './modules/dialogs.js';
import { initUI } from './modules/ui.js';
import { initOnboarding } from './modules/onboarding.js';
import { initGrid } from './modules/grid.js';
import { initFilters } from './modules/filters.js';
import { initIdeas } from './modules/ideas.js';
import { initNotifications } from './modules/notifications.js';
import { initAccount } from './modules/account.js';
import { initSharing } from './modules/sharing.js';
import { initIdeaForm } from './modules/idea-form.js';
import { initShop } from './modules/shop.js';
import { initGems } from './modules/gems.js';
import { initBadges } from './modules/badges.js';
import { initImages } from './modules/images.js';
import { initNavigation } from './modules/navigation.js';

// Initialiser les outils communs avant les fonctionnalités qui les utilisent.
initDialogs();
initUI();
initOnboarding();
initGrid();
initFilters();
initIdeas();
initNotifications();
initAccount();
initSharing();
initIdeaForm();
initShop();
initGems();
initBadges();
initImages();
initNavigation();
