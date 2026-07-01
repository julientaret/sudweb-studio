(function () {
    var duration = parseInt(document.currentScript.dataset.aosDuration, 10);
    AOS.init({ once: true, duration: isNaN(duration) ? 800 : duration });
})();
