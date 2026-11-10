export function initPredictionDetails(root = document) {
    root.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const trigger = event.target.closest?.('[data-prediction-detail]');
        if (!trigger) return;
        const dialog = root.getElementById(trigger.dataset.predictionDetail);
        if (typeof dialog?.showModal !== 'function') return;

        event.preventDefault();
        trigger.focus({ preventScroll: true });
        dialog.showModal();
    });
}
