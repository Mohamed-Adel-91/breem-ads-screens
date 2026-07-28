(function () {
    "use strict";

    var bar = null;
    var timer = null;
    var active = false;

    function progressBar() {
        if (bar) {
            return bar;
        }

        bar = document.getElementById("crm-page-progress");

        if (!bar) {
            bar = document.createElement("div");
            bar.id = "crm-page-progress";
            bar.className = "crm-page-progress";
            bar.setAttribute("role", "progressbar");
            bar.setAttribute("aria-hidden", "true");
            document.body.prepend(bar);
        }

        return bar;
    }

    function setWidth(width) {
        progressBar().style.width = width + "%";
    }

    function clearProgressTimer() {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    function startProgress() {
        var el = progressBar();

        clearProgressTimer();
        active = true;
        el.classList.add("is-active");
        setWidth(8);

        window.requestAnimationFrame(function () {
            setWidth(18);
        });

        timer = window.setInterval(function () {
            var current = parseFloat(el.style.width) || 18;
            var next = current + (85 - current) * 0.18;

            setWidth(Math.min(85, next));
        }, 180);
    }

    function finishProgress() {
        if (!active) {
            return;
        }

        var el = progressBar();

        clearProgressTimer();
        setWidth(100);

        window.setTimeout(function () {
            el.classList.remove("is-active");
        }, 120);

        window.setTimeout(function () {
            active = false;
            setWidth(0);
        }, 280);
    }

    function resetProgress() {
        clearProgressTimer();
        active = false;

        var el = progressBar();
        el.classList.remove("is-active");
        setWidth(0);
    }

    function isModifiedClick(event) {
        return event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;
    }

    function isBootstrapToggle(link) {
        return link.matches(
            "[data-toggle], [data-bs-toggle], [data-dismiss], [data-bs-dismiss], .dropdown-toggle, .collapseSidebar, [aria-controls]"
        );
    }

    function isOptedOut(element) {
        return Boolean(element.closest("[data-no-page-progress], .no-page-progress, .swal2-container"));
    }

    function isExportUrl(url) {
        return /\/export(?:\/)?$/i.test(url.pathname);
    }

    function shouldIgnoreLink(link, event) {
        if (!link || event.defaultPrevented || isModifiedClick(event)) {
            return true;
        }

        if (isOptedOut(link)) {
            return true;
        }

        if (link.target && link.target.toLowerCase() !== "_self") {
            return true;
        }

        if (link.hasAttribute("download") || isBootstrapToggle(link)) {
            return true;
        }

        var href = link.getAttribute("href");

        if (!href || href === "#" || href.charAt(0) === "#" || href.toLowerCase().startsWith("javascript:")) {
            return true;
        }

        var url;

        try {
            url = new URL(href, window.location.href);
        } catch (error) {
            return true;
        }

        if (!["http:", "https:"].includes(url.protocol) || url.origin !== window.location.origin) {
            return true;
        }

        if (isExportUrl(url)) {
            return true;
        }

        return Boolean(
            url.hash &&
            url.pathname === window.location.pathname &&
            url.search === window.location.search
        );
    }

    function shouldIgnoreForm(form) {
        if (!form || form.hasAttribute("data-confirm") || isOptedOut(form)) {
            return true;
        }

        if (form.target && form.target.toLowerCase() !== "_self") {
            return true;
        }

        var action = form.getAttribute("action");

        if (!action) {
            return false;
        }

        try {
            var url = new URL(action, window.location.href);

            return url.origin !== window.location.origin || isExportUrl(url);
        } catch (error) {
            return true;
        }
    }

    document.addEventListener("click", function (event) {
        var link = event.target.closest ? event.target.closest("a[href]") : null;

        if (shouldIgnoreLink(link, event)) {
            return;
        }

        startProgress();
    });

    document.addEventListener("submit", function (event) {
        var form = event.target;

        window.setTimeout(function () {
            if (event.defaultPrevented || shouldIgnoreForm(form)) {
                return;
            }

            startProgress();
        }, 0);
    });

    window.addEventListener("load", finishProgress);
    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            resetProgress();
        } else {
            finishProgress();
        }
    });
})();
