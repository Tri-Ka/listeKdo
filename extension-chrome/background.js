/*
 * Service worker : vérifie au démarrage de Chrome, puis toutes les 6 heures,
 * si une nouvelle version de l'extension est en ligne, et l'indique sur l'icône.
 */

importScripts('update.js');

async function refreshBadge() {
    await showUpdateBadge(await checkUpdate(await currentSite()));
}

chrome.runtime.onInstalled.addListener(() => {
    chrome.alarms.create('check-update', { periodInMinutes: 360 });
    refreshBadge();
});

chrome.runtime.onStartup.addListener(refreshBadge);

chrome.alarms.onAlarm.addListener((alarm) => {
    if (alarm.name === 'check-update') refreshBadge();
});
